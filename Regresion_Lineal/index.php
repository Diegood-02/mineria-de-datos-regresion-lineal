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
$r2     = $reg['r2'];
$n      = $reg['n'];
$y_pred = $b0 + $b1 * 40; // prediccion fija para la tarjeta

// Puntos con bandera de outlier (segun residuos)
$residuos = calcular_residuos($datos, $b0, $b1);
$puntos = array_map(fn($rw) => ['x'=>$rw['x'], 'y'=>$rw['y_real'], 'mes'=>$rw['mes'], 'outlier'=>$rw['es_outlier']], $residuos);

$puntos_x = array_column($residuos, 'x');
if ($n > 0) {
    $x_min = min($puntos_x) - 5;
    $x_max = max($puntos_x) + 15;
} else {
    $x_min = 0; $x_max = 100;
}
$linea = [['x' => $x_min, 'y' => $b0 + $b1 * $x_min],
          ['x' => $x_max, 'y' => $b0 + $b1 * $x_max]];

// Banda de prediccion al 95% a lo largo del rango
$banda = [];
if ($n >= 3) {
    $pasos = 24;
    for ($i = 0; $i <= $pasos; $i++) {
        $x = $x_min + ($x_max - $x_min) * $i / $pasos;
        $bp = banda_prediccion($x, $reg);
        $banda[] = ['x'=>$x, 'yInf'=>$bp['y_inf'], 'ySup'=>$bp['y_sup']];
    }
}

// Calidad del ajuste para el badge
if ($r2 >= 0.9)      { $cal_clase = 'good'; $cal_txt = 'Excelente'; }
elseif ($r2 >= 0.7)  { $cal_clase = 'mid';  $cal_txt = 'Aceptable'; }
else                 { $cal_clase = 'bad';  $cal_txt = 'Debil'; }

$pagina_activa = 'dashboard';
$titulo_pagina = 'Dashboard';
require __DIR__ . '/header.php';
?>

<div class="page-header">
  <h1>Dashboard</h1>
</div>

<!-- Tarjetas resumen -->
<div class="cards">
  <div class="card">
    <div class="card-label"><span class="card-icon">&#128202;</span> Registros en BD</div>
    <div class="card-value"><?= $n ?></div>
  </div>
  <div class="card green">
    <div class="card-label"><span class="card-icon">&#8530;</span> Intercepto (b0)</div>
    <div class="card-value"><?= number_format($b0, 4) ?></div>
  </div>
  <div class="card purple">
    <div class="card-label"><span class="card-icon">&#128200;</span> Pendiente (b1)</div>
    <div class="card-value"><?= number_format($b1, 4) ?></div>
  </div>
  <div class="card orange">
    <div class="card-label"><span class="card-icon">&#127919;</span> Prediccion (X = 40)</div>
    <div class="card-value"><?= number_format($y_pred, 2) ?></div>
  </div>
  <div class="card green">
    <div class="card-label"><span class="card-icon">&#10003;</span> R&sup2; Bondad de ajuste</div>
    <div class="card-value"><?= number_format($r2, 4) ?></div>
    <span class="badge <?= $cal_clase ?>"><?= $cal_txt ?></span>
  </div>
  <div class="card purple">
    <div class="card-label"><span class="card-icon">&#128279;</span> Correlacion (r)</div>
    <div class="card-value"><?= number_format($reg['r'], 4) ?></div>
  </div>
</div>

<!-- Grafica -->
<div class="panel">
  <h2>Grafica de Regresion Lineal</h2>
  <canvas id="grafica" height="90"></canvas>
  <p style="color:var(--text-faint); font-size:0.8rem; margin-top:10px">
    La zona sombreada es el intervalo de prediccion al 95%. Los puntos en rojo son outliers (residuo &gt; 2.5&sigma;).
  </p>
</div>

<script>
const CFG = {
  puntos: <?= json_encode($puntos) ?>,
  linea:  <?= json_encode($linea) ?>,
  banda:  <?= json_encode($banda) ?>,
  ejeX: 'Inversion (X)',
  ejeY: 'Ventas (Y)',
};
crearGraficaRegresion('grafica', CFG);
</script>

<?php require __DIR__ . '/footer.php'; ?>
