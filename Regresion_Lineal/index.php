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

$puntos_x = array_map(fn($d) => (float)$d['inversion'], $datos);
$puntos_y = array_map(fn($d) => (float)$d['ventas'],    $datos);
if ($n > 0) {
    $x_min = min($puntos_x) - 5;
    $x_max = max($puntos_x) + 15;
} else {
    $x_min = 0; $x_max = 100;
}
$linea = [['x' => $x_min, 'y' => $b0 + $b1 * $x_min],
          ['x' => $x_max, 'y' => $b0 + $b1 * $x_max]];

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
    <div class="card-label">Registros en BD</div>
    <div class="card-value"><?= $n ?></div>
  </div>
  <div class="card green">
    <div class="card-label">Intercepto (b0)</div>
    <div class="card-value"><?= number_format($b0, 4) ?></div>
  </div>
  <div class="card purple">
    <div class="card-label">Pendiente (b1)</div>
    <div class="card-value"><?= number_format($b1, 4) ?></div>
  </div>
  <div class="card orange">
    <div class="card-label">Prediccion (X = 40)</div>
    <div class="card-value"><?= number_format($y_pred, 2) ?></div>
  </div>
  <div class="card green">
    <div class="card-label">R&sup2; Bondad de ajuste</div>
    <div class="card-value"><?= number_format($r2, 4) ?></div>
  </div>
</div>

<!-- Grafica -->
<div class="panel">
  <h2>Grafica de Regresion Lineal</h2>
  <canvas id="grafica" height="90"></canvas>
</div>

<script>
const puntosX = <?= json_encode($puntos_x) ?>;
const puntosY = <?= json_encode($puntos_y) ?>;
const meses   = <?= json_encode(array_column($datos, 'mes')) ?>;
const linea   = <?= json_encode($linea) ?>;
const scatter = puntosX.map((x, i) => ({ x, y: puntosY[i] }));

new Chart(document.getElementById('grafica'), {
  data: {
    datasets: [
      {
        type: 'scatter',
        label: 'Datos reales',
        data: scatter,
        backgroundColor: '#60a5fa',
        pointRadius: 8,
        pointHoverRadius: 11,
      },
      {
        type: 'line',
        label: 'Linea de regresion',
        data: linea,
        borderColor: '#f59e0b',
        borderWidth: 2.5,
        pointRadius: 0,
        tension: 0,
        fill: false,
      }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { labels: { color: '#cbd5e1' } },
      tooltip: {
        callbacks: {
          label: ctx => {
            if (ctx.datasetIndex === 0)
              return `${meses[ctx.dataIndex]}: (${ctx.raw.x}, ${ctx.raw.y})`;
            return `Y = ${ctx.raw.y.toFixed(2)}`;
          }
        }
      }
    },
    scales: {
      x: { title: { display: true, text: 'Inversion (X)', color: '#94a3b8' }, ticks: { color: '#94a3b8' }, grid: { color: '#1e293b' } },
      y: { title: { display: true, text: 'Ventas (Y)',    color: '#94a3b8' }, ticks: { color: '#94a3b8' }, grid: { color: '#1e293b' } }
    }
  }
});
</script>

<?php require __DIR__ . '/footer.php'; ?>
