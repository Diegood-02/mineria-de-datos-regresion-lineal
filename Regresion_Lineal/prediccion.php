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
$x_pred = isset($_GET['x']) ? (float)$_GET['x'] : 40;
$y_pred = $b0 + $b1 * $x_pred;

$pagina_activa = 'prediccion';
$titulo_pagina = 'Prediccion';
require __DIR__ . '/header.php';
?>

<div class="page-header">
  <h1>Calculadora de Prediccion</h1>
</div>

<div style="max-width:520px; margin:0 auto;">

  <!-- Ecuacion del modelo -->
  <div class="panel">
    <h2>Modelo activo</h2>
    <div class="ecuacion">
      Y &nbsp;=&nbsp; <?= number_format($b0, 4) ?> &nbsp;+&nbsp; <?= number_format($b1, 4) ?> &nbsp;&times;&nbsp; X
    </div>
    <p style="color:#94a3b8; font-size:0.88rem; text-align:center">
      Basado en <?= $reg['n'] ?> registros de la base de datos.
    </p>
  </div>

  <!-- Formulario -->
  <div class="panel">
    <h2>Ingresar valor de Inversion</h2>
    <form method="GET">
      <input type="number" name="x" placeholder="Valor de Inversion (X)"
             value="<?= $x_pred ?>" step="0.01" min="0" required>
      <button type="submit" class="btn">Calcular Ventas Estimadas</button>
    </form>
  </div>

  <!-- Resultado -->
  <div class="pred-result">
    <p style="color:#94a3b8; font-size:0.9rem">Con Inversion X = <strong style="color:#e2e8f0"><?= number_format($x_pred, 2) ?></strong>, las ventas estimadas son:</p>
    <span><?= number_format($y_pred, 2) ?></span>
    <p style="color:#64748b; font-size:0.8rem; margin-top:8px">
      <?= number_format($b0,4) ?> + <?= number_format($b1,4) ?> &times; <?= number_format($x_pred,2) ?> = <?= number_format($y_pred,4) ?>
    </p>
  </div>

</div>

<?php require __DIR__ . '/footer.php'; ?>
