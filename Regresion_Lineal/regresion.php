<?php
header('Location: modelo.php');
exit;
// Conexion a la base de datos (credenciales externas en config.php)
require_once __DIR__ . '/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die("Error de conexion: " . $conn->connect_error);
}

// Obtener datos
$resultado = $conn->query("SELECT mes, inversion, ventas FROM inversion_ventas ORDER BY id");
$datos = [];
while ($fila = $resultado->fetch_assoc()) {
    $datos[] = $fila;
}
$conn->close();

// Regresion lineal
function regresion_lineal($datos) {
    $n = count($datos);
    $suma_x = $suma_y = $suma_xy = $suma_x2 = 0;

    foreach ($datos as $fila) {
        $x = (float)$fila['inversion'];
        $y = (float)$fila['ventas'];
        $suma_x  += $x;
        $suma_y  += $y;
        $suma_xy += $x * $y;
        $suma_x2 += $x * $x;
    }

    $b1 = ($n * $suma_xy - $suma_x * $suma_y) / ($n * $suma_x2 - $suma_x ** 2);
    $b0 = ($suma_y - $b1 * $suma_x) / $n;

    // Metricas de bondad de ajuste
    $y_media = $suma_y / $n;
    $ss_tot = $ss_res = 0;
    foreach ($datos as $fila) {
        $y     = (float)$fila['ventas'];
        $y_est = $b0 + $b1 * (float)$fila['inversion'];
        $ss_tot += ($y - $y_media) ** 2;
        $ss_res += ($y - $y_est) ** 2;
    }
    $r2   = $ss_tot > 0 ? 1 - $ss_res / $ss_tot : 1;
    $mse  = $ss_res / $n;
    $rmse = sqrt($mse);

    return ['b0' => $b0, 'b1' => $b1, 'r2' => $r2, 'mse' => $mse, 'rmse' => $rmse];
}

$reg = regresion_lineal($datos);
$b0  = $reg['b0'];
$b1  = $reg['b1'];
$r2   = $reg['r2'];
$mse  = $reg['mse'];
$rmse = $reg['rmse'];

// Prediccion para el valor que venga por GET (default 40)
$x_pred = isset($_GET['x']) ? (float)$_GET['x'] : 40;
$y_pred = $b0 + $b1 * $x_pred;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Regresion Lineal - Mineria de Datos</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; background: #f5f5f5; color: #333; }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 8px; }
        h2 { color: #34495e; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: #3498db; color: white; padding: 10px; }
        td { padding: 9px 12px; border-bottom: 1px solid #ddd; text-align: center; }
        tr:hover { background: #eaf4fb; }
        .resultado { background: white; padding: 20px; border-left: 4px solid #3498db; margin: 15px 0; }
        .ecuacion { font-size: 1.2em; font-weight: bold; color: #2980b9; }
        form { background: white; padding: 15px; margin-top: 20px; }
        input[type=number] { padding: 8px; font-size: 1em; width: 120px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 8px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }
        button:hover { background: #2980b9; }
        .pred { font-size: 1.1em; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Regresion Lineal - Inversion vs Ventas</h1>

    <h2>Datos de la base de datos</h2>
    <table>
        <tr><th>Mes</th><th>Inversion (X)</th><th>Ventas (Y)</th></tr>
        <?php foreach ($datos as $fila): ?>
        <tr>
            <td><?= htmlspecialchars($fila['mes']) ?></td>
            <td><?= number_format($fila['inversion'], 2) ?></td>
            <td><?= number_format($fila['ventas'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Modelo de Regresion</h2>
    <div class="resultado">
        <p>Intercepto (b0): <strong><?= number_format($b0, 4) ?></strong></p>
        <p>Pendiente  (b1): <strong><?= number_format($b1, 4) ?></strong></p>
        <p class="ecuacion">Y = <?= number_format($b0, 2) ?> + <?= number_format($b1, 2) ?> * X</p>
    </div>

    <h2>Bondad de Ajuste</h2>
    <div class="resultado">
        <p>Coeficiente de determinacion (R&sup2;): <strong><?= number_format($r2, 4) ?></strong>
           &nbsp;&mdash;&nbsp; el modelo explica el <strong><?= number_format($r2 * 100, 2) ?>%</strong> de la variabilidad de las ventas.</p>
        <p>Error cuadratico medio (MSE): <strong><?= number_format($mse, 2) ?></strong></p>
        <p>Raiz del error cuadratico medio (RMSE): <strong><?= number_format($rmse, 2) ?></strong></p>
    </div>

    <h2>Prediccion</h2>
    <form method="GET">
        <label>Ingresa un valor de Inversion (X):
            <input type="number" name="x" value="<?= $x_pred ?>" step="0.01">
        </label>
        <button type="submit">Calcular</button>
    </form>
    <div class="pred">
        Con Inversion = <strong><?= $x_pred ?></strong>,
        las Ventas estimadas son: <strong><?= number_format($y_pred, 2) ?></strong>
    </div>
</body>
</html>
