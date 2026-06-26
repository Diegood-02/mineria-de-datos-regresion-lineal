<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/funciones.php';

$res   = $conn->query("SELECT mes, inversion, ventas FROM inversion_ventas ORDER BY id");
$datos = [];
while ($fila = $res->fetch_assoc()) $datos[] = $fila;
$conn->close();

$reg    = calcular_regresion($datos);
$b0     = $reg['b0'];
$b1     = $reg['b1'];
$n      = $reg['n'];
$x_pred = isset($_GET['x']) ? (float)$_GET['x'] : 40;

$residuos = calcular_residuos($datos, $b0, $b1);
$puntos = array_map(fn($rw) => ['x'=>$rw['x'], 'y'=>$rw['y_real'], 'mes'=>$rw['mes'], 'outlier'=>$rw['es_outlier']], $residuos);
$puntos_x = array_column($residuos, 'x');
if ($n > 0) {
    $x_min = max(0, min($puntos_x) - 5);
    $x_max = max($puntos_x) + 30;
} else {
    $x_min = 0; $x_max = 100;
}
$linea = [['x' => $x_min, 'y' => $b0 + $b1 * $x_min],
          ['x' => $x_max, 'y' => $b0 + $b1 * $x_max]];

$banda = [];
if ($n >= 3) {
    $pasos = 24;
    for ($i = 0; $i <= $pasos; $i++) {
        $x = $x_min + ($x_max - $x_min) * $i / $pasos;
        $bp = banda_prediccion($x, $reg);
        $banda[] = ['x'=>$x, 'yInf'=>$bp['y_inf'], 'ySup'=>$bp['y_sup']];
    }
}

// Datos para el calculo del intervalo en el cliente
$modelo_js = [
    'b0'=>$b0, 'b1'=>$b1, 'n'=>$n,
    'se'=>$reg['se'], 'x_media'=>$reg['x_media'], 'sxx'=>$reg['sxx'],
    't'=> $n > 2 ? valor_t($n - 2) : 0,
];

$pagina_activa = 'prediccion';
$titulo_pagina = 'Prediccion';
require __DIR__ . '/header.php';
?>

<div class="page-header">
  <h1>Calculadora de Prediccion</h1>
</div>

<!-- Modelo activo -->
<div class="panel">
  <h2>Modelo activo</h2>
  <div class="ecuacion">
    Y &nbsp;=&nbsp; <?= number_format($b0, 4) ?> &nbsp;+&nbsp; <?= number_format($b1, 4) ?> &nbsp;&times;&nbsp; X
  </div>
  <p style="color:var(--text-muted); font-size:0.88rem; text-align:center">
    Basado en <?= $n ?> registros de la base de datos.
  </p>
</div>

<div class="grid2">
  <!-- Prediccion directa interactiva -->
  <div class="panel">
    <h2>Prediccion: Inversion &rarr; Ventas</h2>
    <label class="field-label">Valor de Inversion (X)</label>
    <input type="number" id="x-input" value="<?= htmlspecialchars((string)$x_pred) ?>" step="0.5" min="<?= $x_min ?>">
    <input type="range" id="x-range" value="<?= htmlspecialchars((string)$x_pred) ?>"
           min="<?= $x_min ?>" max="<?= $x_max ?>" step="0.5" style="width:100%; margin-bottom:6px">
    <div class="pred-result">
      <p style="color:var(--text-muted); font-size:0.9rem">Ventas estimadas:</p>
      <span id="y-out">&mdash;</span>
      <p class="pred-interval" id="intervalo-out"></p>
      <p style="color:var(--text-faint); font-size:0.8rem; margin-top:8px" id="formula-out"></p>
    </div>
  </div>

  <!-- Prediccion inversa -->
  <div class="panel">
    <h2>Prediccion inversa: Ventas &rarr; Inversion</h2>
    <label class="field-label">Ventas objetivo (Y)</label>
    <input type="number" id="y-target" placeholder="Ej. 120" step="0.5">
    <div class="pred-result" style="border-color:var(--accent)">
      <p style="color:var(--text-muted); font-size:0.9rem">Inversion necesaria estimada:</p>
      <span id="x-out" style="color:var(--accent-soft)">&mdash;</span>
      <p style="color:var(--text-faint); font-size:0.8rem; margin-top:8px" id="formula-inv-out"></p>
    </div>
  </div>
</div>

<!-- Grafica con el punto de prediccion -->
<div class="panel">
  <h2>Visualizacion de la Prediccion</h2>
  <canvas id="grafica" height="80"></canvas>
</div>

<script>
const MODELO = <?= json_encode($modelo_js) ?>;
const X_INI  = <?= json_encode((float)$x_pred) ?>;

const chart = crearGraficaRegresion('grafica', {
  puntos: <?= json_encode($puntos) ?>,
  linea:  <?= json_encode($linea) ?>,
  banda:  <?= json_encode($banda) ?>,
  ejeX: 'Inversion (X)',
  ejeY: 'Ventas (Y)',
  prediccion: { x: X_INI, y: MODELO.b0 + MODELO.b1 * X_INI },
});

const dsPrediccion = chart ? chart.data.datasets.find(d => d.label === 'Prediccion') : null;

function intervaloPrediccion(x) {
  if (MODELO.n < 3 || MODELO.se <= 0 || MODELO.sxx <= 0) return null;
  const m = MODELO.t * MODELO.se *
    Math.sqrt(1 + 1 / MODELO.n + Math.pow(x - MODELO.x_media, 2) / (MODELO.n * MODELO.sxx));
  return m;
}

function fmt(v, d = 2) { return v.toLocaleString('es-MX', { minimumFractionDigits: d, maximumFractionDigits: d }); }

function actualizar(x) {
  const y = MODELO.b0 + MODELO.b1 * x;
  document.getElementById('y-out').textContent = fmt(y);
  document.getElementById('formula-out').textContent =
    `${fmt(MODELO.b0, 4)} + ${fmt(MODELO.b1, 4)} × ${fmt(x)} = ${fmt(y, 4)}`;

  const m = intervaloPrediccion(x);
  document.getElementById('intervalo-out').textContent =
    m !== null ? `Intervalo de prediccion 95%: [${fmt(y - m)} , ${fmt(y + m)}]` : '';

  if (dsPrediccion) { dsPrediccion.data = [{ x, y }]; chart.update('none'); }
}

function actualizarInversa(yObjetivo) {
  const out = document.getElementById('x-out');
  const formula = document.getElementById('formula-inv-out');
  if (yObjetivo === '' || isNaN(yObjetivo)) { out.textContent = '—'; formula.textContent = ''; return; }
  if (MODELO.b1 === 0) { out.textContent = 'N/D'; formula.textContent = 'La pendiente es 0; no es invertible.'; return; }
  const x = (yObjetivo - MODELO.b0) / MODELO.b1;
  out.textContent = fmt(x);
  formula.textContent = `(${fmt(yObjetivo)} − ${fmt(MODELO.b0, 4)}) ÷ ${fmt(MODELO.b1, 4)} = ${fmt(x, 4)}`;
}

const xInput = document.getElementById('x-input');
const xRange = document.getElementById('x-range');
xInput.addEventListener('input', () => { xRange.value = xInput.value; actualizar(parseFloat(xInput.value) || 0); });
xRange.addEventListener('input', () => { xInput.value = xRange.value; actualizar(parseFloat(xRange.value) || 0); });
document.getElementById('y-target').addEventListener('input', e => actualizarInversa(parseFloat(e.target.value)));

actualizar(X_INI);
</script>

<?php require __DIR__ . '/footer.php'; ?>
