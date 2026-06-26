<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/funciones.php';

$res   = $conn->query("SELECT mes, inversion, ventas FROM inversion_ventas ORDER BY id");
$datos = [];
while ($fila = $res->fetch_assoc()) $datos[] = $fila;
$conn->close();

$reg  = calcular_regresion($datos);
$b0   = $reg['b0'];
$b1   = $reg['b1'];
$r2   = $reg['r2'];
$mse  = $reg['mse'];
$rmse = $reg['rmse'];
$n    = $reg['n'];

$residuos = calcular_residuos($datos, $b0, $b1);
$puntos = array_map(fn($rw) => ['x'=>$rw['x'], 'y'=>$rw['y_real'], 'mes'=>$rw['mes'], 'outlier'=>$rw['es_outlier']], $residuos);
$puntos_residuo = array_map(fn($rw) => ['x'=>$rw['x'], 'residuo'=>$rw['residuo'], 'mes'=>$rw['mes'], 'outlier'=>$rw['es_outlier']], $residuos);

$puntos_x = array_column($residuos, 'x');
if ($n > 0) {
    $x_min = min($puntos_x) - 5;
    $x_max = max($puntos_x) + 15;
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

$pagina_activa = 'modelo';
$titulo_pagina = 'Modelo de Regresion';
require __DIR__ . '/header.php';
?>

<div class="page-header">
  <h1>Modelo de Regresion Lineal</h1>
</div>

<!-- Ecuacion -->
<div class="panel">
  <h2>Ecuacion del Modelo</h2>
  <div class="ecuacion">
    Y &nbsp;=&nbsp; <?= number_format($b0, 4) ?> &nbsp;+&nbsp; <?= number_format($b1, 4) ?> &nbsp;&times;&nbsp; X
  </div>
  <div class="coef">
    <div><small>Intercepto b0</small><strong><?= number_format($b0, 4) ?></strong></div>
    <div><small>Pendiente b1</small><strong><?= number_format($b1, 4) ?></strong></div>
    <div><small>Registros (n)</small><strong><?= $n ?></strong></div>
  </div>
</div>

<!-- Metricas -->
<div class="panel">
  <h2>Bondad de Ajuste</h2>
  <div class="coef">
    <div><small>R&sup2;</small><strong><?= number_format($r2, 4) ?></strong></div>
    <div><small>Correlacion r</small><strong><?= number_format($reg['r'], 4) ?></strong></div>
    <div><small>MSE</small><strong><?= number_format($mse, 4) ?></strong></div>
    <div><small>RMSE</small><strong><?= number_format($rmse, 4) ?></strong></div>
  </div>
  <div class="alert" style="margin-top:10px">
    El modelo explica el <strong><?= number_format($r2 * 100, 2) ?>%</strong>
    de la variabilidad de las ventas (R&sup2; = <?= number_format($r2, 4) ?>).
    La correlacion lineal es <strong><?= number_format($reg['r'], 4) ?></strong>.
    Error promedio de prediccion (RMSE): <strong><?= number_format($rmse, 2) ?></strong>.
  </div>
</div>

<!-- Grafica -->
<div class="panel">
  <h2>Grafica de Dispersion con Linea de Regresion</h2>
  <canvas id="grafica" height="70"></canvas>
</div>

<!-- Analisis de residuos -->
<div class="panel">
  <h2>Analisis de Residuos</h2>
  <?php if ($n < 2): ?>
    <p style="color:var(--text-faint)">Se necesitan al menos 2 registros para analizar residuos.</p>
  <?php else: ?>
  <div class="grid2">
    <div>
      <table>
        <thead>
          <tr>
            <th>Mes</th>
            <th class="num">X</th>
            <th class="num">Y real</th>
            <th class="num">Y estimado</th>
            <th class="num">Residuo</th>
          </tr>
        </thead>
        <tbody>
          <?php $suma_res = 0; foreach ($residuos as $rw): $suma_res += $rw['residuo']; ?>
          <tr class="<?= $rw['es_outlier'] ? 'outlier-row' : '' ?>">
            <td><?= htmlspecialchars($rw['mes']) ?><?= $rw['es_outlier'] ? ' &#9888;' : '' ?></td>
            <td class="num"><?= number_format($rw['x'], 2) ?></td>
            <td class="num"><?= number_format($rw['y_real'], 2) ?></td>
            <td class="num"><?= number_format($rw['y_est'], 2) ?></td>
            <td class="num <?= $rw['residuo'] >= 0 ? 'pos' : 'neg' ?>"><?= number_format($rw['residuo'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" style="text-align:right; color:var(--text-muted)">Suma de residuos</td>
            <td class="num"><?= number_format($suma_res, 4) ?></td>
          </tr>
        </tfoot>
      </table>
      <p style="color:var(--text-faint); font-size:0.8rem; margin-top:10px">
        La suma de residuos debe ser cercana a 0 si el modelo esta bien ajustado.
      </p>
    </div>
    <div>
      <canvas id="grafica-residuos" height="160"></canvas>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
crearGraficaRegresion('grafica', {
  puntos: <?= json_encode($puntos) ?>,
  linea:  <?= json_encode($linea) ?>,
  banda:  <?= json_encode($banda) ?>,
  ejeX: 'Inversion (X)',
  ejeY: 'Ventas (Y)',
});
<?php if ($n >= 2): ?>
crearGraficaResiduos('grafica-residuos', <?= json_encode($puntos_residuo) ?>);
<?php endif; ?>
</script>

<?php require __DIR__ . '/footer.php'; ?>
