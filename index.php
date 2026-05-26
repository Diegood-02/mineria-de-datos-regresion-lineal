<?php
$conn = new mysqli("127.0.0.1", "root", "", "mineria_datos", 3306);
if ($conn->connect_error) die("Error de conexion: " . $conn->connect_error);

// Insertar nuevo registro
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mes'])) {
    $mes      = $conn->real_escape_string(trim($_POST['mes']));
    $inversion = (float)$_POST['inversion'];
    $ventas    = (float)$_POST['ventas'];
    if ($mes && $inversion > 0 && $ventas > 0) {
        $conn->query("INSERT INTO inversion_ventas (mes, inversion, ventas) VALUES ('$mes', $inversion, $ventas)");
        $msg = "Registro agregado correctamente.";
    } else {
        $msg = "Por favor completa todos los campos correctamente.";
    }
}

// Obtener datos
$res   = $conn->query("SELECT mes, inversion, ventas FROM inversion_ventas ORDER BY id");
$datos = [];
while ($fila = $res->fetch_assoc()) $datos[] = $fila;
$conn->close();

// Regresion lineal
$n = count($datos);
$sx = $sy = $sxy = $sx2 = 0;
foreach ($datos as $d) {
    $x = (float)$d['inversion']; $y = (float)$d['ventas'];
    $sx += $x; $sy += $y; $sxy += $x * $y; $sx2 += $x * $x;
}
$b1 = ($n * $sxy - $sx * $sy) / ($n * $sx2 - $sx * $sx);
$b0 = ($sy - $b1 * $sx) / $n;

// Prediccion
$x_pred = isset($_GET['x']) ? (float)$_GET['x'] : 40;
$y_pred = $b0 + $b1 * $x_pred;

// Datos para Chart.js
$puntos_x   = array_map(fn($d) => (float)$d['inversion'], $datos);
$puntos_y   = array_map(fn($d) => (float)$d['ventas'],    $datos);
$x_min = min($puntos_x) - 5;
$x_max = max($puntos_x) + 15;
$linea  = [['x' => $x_min, 'y' => $b0 + $b1 * $x_min],
           ['x' => $x_max, 'y' => $b0 + $b1 * $x_max]];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - Regresion Lineal</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; }

  header {
    background: linear-gradient(135deg, #1e40af, #7c3aed);
    padding: 24px 32px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.4);
  }
  header h1 { font-size: 1.6rem; font-weight: 700; }
  header span { font-size: 0.9rem; opacity: 0.75; }

  .container { max-width: 1100px; margin: 0 auto; padding: 28px 20px; }

  .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px; }
  .card {
    background: #1e293b; border-radius: 12px; padding: 20px;
    border-left: 4px solid #3b82f6;
  }
  .card.green  { border-color: #10b981; }
  .card.purple { border-color: #8b5cf6; }
  .card.orange { border-color: #f59e0b; }
  .card-label  { font-size: 0.78rem; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em; margin-bottom: 6px; }
  .card-value  { font-size: 1.7rem; font-weight: 700; }

  .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
  @media (max-width: 720px) { .grid2 { grid-template-columns: 1fr; } }

  .panel { background: #1e293b; border-radius: 12px; padding: 22px; }
  .panel h2 { font-size: 1rem; font-weight: 600; color: #93c5fd; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.05em; }

  canvas { width: 100% !important; }

  table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
  thead th { background: #334155; padding: 10px 14px; text-align: left; color: #94a3b8; font-weight: 600; }
  tbody td { padding: 10px 14px; border-bottom: 1px solid #334155; }
  tbody tr:hover { background: #263348; }

  .ecuacion {
    background: #0f172a; border-radius: 8px; padding: 14px 18px;
    font-size: 1.15rem; font-weight: 700; color: #60a5fa;
    text-align: center; margin-bottom: 16px;
    border: 1px solid #1d4ed8;
  }
  .coef { display: flex; gap: 16px; margin-bottom: 12px; }
  .coef div { flex: 1; background: #0f172a; border-radius: 8px; padding: 10px 14px; text-align: center; }
  .coef small { color: #94a3b8; font-size: 0.78rem; }
  .coef strong { display: block; font-size: 1.3rem; color: #a78bfa; }

  form input, form select {
    width: 100%; background: #0f172a; border: 1px solid #334155;
    color: #e2e8f0; border-radius: 8px; padding: 9px 12px;
    font-size: 0.95rem; margin-bottom: 10px;
  }
  form input:focus { outline: none; border-color: #3b82f6; }
  .btn {
    width: 100%; padding: 10px; background: #3b82f6; color: white;
    border: none; border-radius: 8px; font-size: 1rem; cursor: pointer;
    font-weight: 600; transition: background 0.2s;
  }
  .btn:hover { background: #2563eb; }
  .btn.green { background: #10b981; }
  .btn.green:hover { background: #059669; }

  .pred-result {
    margin-top: 14px; background: #0f172a; border-radius: 8px;
    padding: 14px; text-align: center; border: 1px solid #10b981;
  }
  .pred-result span { font-size: 1.6rem; font-weight: 700; color: #34d399; }

  .alert { background: #1e3a5f; border-left: 4px solid #3b82f6; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
</style>
</head>
<body>

<header>
  <div>
    <h1>Dashboard &mdash; Regresion Lineal</h1>
    <span>Inversion vs Ventas &nbsp;|&nbsp; Mineria de Datos &nbsp;|&nbsp; IPN</span>
  </div>
</header>

<div class="container">

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
      <div class="card-label">Prediccion (X=<?= $x_pred ?>)</div>
      <div class="card-value"><?= number_format($y_pred, 2) ?></div>
    </div>
  </div>

  <!-- Grafica + Tabla -->
  <div class="grid2">
    <div class="panel">
      <h2>Grafica de Regresion</h2>
      <canvas id="grafica"></canvas>
    </div>

    <div class="panel">
      <h2>Datos de la Base de Datos</h2>
      <table>
        <thead><tr><th>Mes</th><th>Inversion (X)</th><th>Ventas (Y)</th></tr></thead>
        <tbody>
          <?php foreach ($datos as $d): ?>
          <tr>
            <td><?= htmlspecialchars($d['mes']) ?></td>
            <td><?= number_format($d['inversion'], 2) ?></td>
            <td><?= number_format($d['ventas'],    2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Ecuacion + Prediccion + Agregar -->
  <div class="grid2">
    <div class="panel">
      <h2>Modelo</h2>
      <div class="ecuacion">Y = <?= number_format($b0,2) ?> + <?= number_format($b1,2) ?> &times; X</div>
      <div class="coef">
        <div><small>Intercepto b0</small><strong><?= number_format($b0,4) ?></strong></div>
        <div><small>Pendiente b1</small><strong><?= number_format($b1,4) ?></strong></div>
      </div>

      <h2 style="margin-top:20px">Calcular Prediccion</h2>
      <form method="GET">
        <input type="number" name="x" placeholder="Valor de Inversion (X)" value="<?= $x_pred ?>" step="0.01" required>
        <button type="submit" class="btn">Calcular</button>
      </form>
      <div class="pred-result">
        Con X = <strong><?= $x_pred ?></strong>, Ventas estimadas:<br>
        <span><?= number_format($y_pred, 2) ?></span>
      </div>
    </div>

    <div class="panel">
      <h2>Agregar Nuevo Registro</h2>
      <?php if ($msg): ?><div class="alert"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
      <form method="POST">
        <input type="text"   name="mes"       placeholder="Mes (ej. Abril)"   required>
        <input type="number" name="inversion" placeholder="Inversion (X)"     step="0.01" min="0" required>
        <input type="number" name="ventas"    placeholder="Ventas (Y)"         step="0.01" min="0" required>
        <button type="submit" class="btn green">Agregar a la BD</button>
      </form>
    </div>
  </div>

</div><!-- /container -->

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
</body>
</html>
