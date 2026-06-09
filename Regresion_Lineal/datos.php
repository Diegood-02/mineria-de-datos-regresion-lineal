<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/funciones.php';

$msg      = '';
$msg_tipo = 'info';

// Eliminar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)$_POST['id'];
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM inversion_ventas WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $msg = 'Registro eliminado correctamente.';
        $msg_tipo = 'warn';
    }
}

// Agregar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $mes       = trim($_POST['mes'] ?? '');
    $inversion = (float)($_POST['inversion'] ?? 0);
    $ventas    = (float)($_POST['ventas']    ?? 0);

    if ($mes && $inversion > 0 && $ventas > 0) {
        $prev = $conn->query("SELECT inversion FROM inversion_ventas");
        $vals = [];
        while ($f = $prev->fetch_assoc()) $vals[] = (float)$f['inversion'];
        $aviso = detectar_outlier($inversion, $vals);

        $stmt = $conn->prepare("INSERT INTO inversion_ventas (mes, inversion, ventas) VALUES (?, ?, ?)");
        $stmt->bind_param('sdd', $mes, $inversion, $ventas);
        $stmt->execute();
        $stmt->close();

        if ($aviso) {
            $msg      = 'Registro agregado.' . $aviso;
            $msg_tipo = 'warn';
        } else {
            $msg      = 'Registro agregado correctamente.';
            $msg_tipo = 'success';
        }
    } else {
        $msg      = 'Completa todos los campos correctamente (inversion y ventas deben ser mayores a 0).';
        $msg_tipo = 'error';
    }
}

// Obtener datos con id para el boton de eliminar
$res   = $conn->query("SELECT id, mes, inversion, ventas FROM inversion_ventas ORDER BY id");
$datos = [];
while ($fila = $res->fetch_assoc()) $datos[] = $fila;
$conn->close();

$pagina_activa = 'datos';
$titulo_pagina = 'Gestion de Datos';
require __DIR__ . '/header.php';
?>

<div class="page-header">
  <h1>Gestion de Datos</h1>
</div>

<?php if ($msg): ?>
<div class="alert <?= $msg_tipo ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Tabla -->
<div class="panel">
  <h2>Registros en la Base de Datos (<?= count($datos) ?>)</h2>
  <?php if (empty($datos)): ?>
    <p style="color:#64748b">No hay registros. Agrega el primero abajo.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Mes</th>
        <th>Inversion (X)</th>
        <th>Ventas (Y)</th>
        <th style="text-align:center">Eliminar</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($datos as $d): ?>
      <tr>
        <td style="color:#64748b"><?= $d['id'] ?></td>
        <td><?= htmlspecialchars($d['mes']) ?></td>
        <td><?= number_format($d['inversion'], 2) ?></td>
        <td><?= number_format($d['ventas'],    2) ?></td>
        <td style="text-align:center">
          <form method="POST" style="margin:0">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id"     value="<?= $d['id'] ?>">
            <button type="submit" class="btn-sm"
              onclick="return confirm('¿Eliminar el registro de <?= htmlspecialchars($d['mes'], ENT_QUOTES) ?>?')">
              Eliminar
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- Formulario agregar -->
<div class="panel">
  <h2>Agregar Nuevo Registro</h2>
  <form method="POST">
    <input type="hidden" name="action" value="add">
    <input type="text"   name="mes"       placeholder="Mes (ej. Abril)"  required>
    <input type="number" name="inversion" placeholder="Inversion (X)"    step="0.01" min="0.01" required>
    <input type="number" name="ventas"    placeholder="Ventas (Y)"        step="0.01" min="0.01" required>
    <button type="submit" class="btn green">Agregar a la BD</button>
  </form>
</div>

<?php require __DIR__ . '/footer.php'; ?>
