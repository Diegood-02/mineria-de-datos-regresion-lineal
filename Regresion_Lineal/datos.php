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

// Actualizar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id        = (int)($_POST['id'] ?? 0);
    $mes       = trim($_POST['mes'] ?? '');
    $inversion = (float)($_POST['inversion'] ?? 0);
    $ventas    = (float)($_POST['ventas']    ?? 0);

    if ($id > 0 && $mes && $inversion > 0 && $ventas > 0) {
        $stmt = $conn->prepare("UPDATE inversion_ventas SET mes = ?, inversion = ?, ventas = ? WHERE id = ?");
        $stmt->bind_param('sddi', $mes, $inversion, $ventas, $id);
        $stmt->execute();
        $stmt->close();
        $msg      = 'Registro actualizado correctamente.';
        $msg_tipo = 'success';
    } else {
        $msg      = 'No se pudo actualizar: revisa los datos (inversion y ventas mayores a 0).';
        $msg_tipo = 'error';
    }
}

// Importar CSV (columnas: mes, inversion, ventas; con o sin encabezado / columna id)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import') {
    if (!empty($_FILES['csv']['tmp_name']) && is_uploaded_file($_FILES['csv']['tmp_name'])) {
        $fh = fopen($_FILES['csv']['tmp_name'], 'r');
        $insertados = 0; $omitidos = 0; $fila_n = 0;
        $stmt = $conn->prepare("INSERT INTO inversion_ventas (mes, inversion, ventas) VALUES (?, ?, ?)");
        while (($col = fgetcsv($fh)) !== false) {
            $fila_n++;
            if (count($col) < 3) { $omitidos++; continue; }
            // Si hay 4 columnas asumimos id al inicio (formato de exportacion)
            if (count($col) >= 4) { array_shift($col); }
            $mes = trim($col[0]);
            // Saltar encabezado
            if ($fila_n === 1 && strtolower($mes) === 'mes') { continue; }
            $inv = (float)$col[1];
            $ven = (float)$col[2];
            if ($mes === '' || $inv <= 0 || $ven <= 0) { $omitidos++; continue; }
            $stmt->bind_param('sdd', $mes, $inv, $ven);
            $stmt->execute();
            $insertados++;
        }
        $stmt->close();
        fclose($fh);
        $msg      = "Importacion completada: $insertados registros agregados, $omitidos omitidos.";
        $msg_tipo = $insertados > 0 ? 'success' : 'warn';
    } else {
        $msg      = 'No se recibio ningun archivo CSV valido.';
        $msg_tipo = 'error';
    }
}

// Obtener datos con id
$res   = $conn->query("SELECT id, mes, inversion, ventas FROM inversion_ventas ORDER BY id");
$datos = [];
while ($fila = $res->fetch_assoc()) $datos[] = $fila;
$conn->close();

// Calcular residuos para marcar outliers en la tabla
$reg      = calcular_regresion($datos);
$residuos = calcular_residuos($datos, $reg['b0'], $reg['b1']);
$outlier_por_idx = array_column($residuos, 'es_outlier'); // mismo orden que $datos

// Registro en edicion (si aplica)
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_reg = null;
if ($edit_id > 0) {
    foreach ($datos as $d) {
        if ((int)$d['id'] === $edit_id) { $edit_reg = $d; break; }
    }
}

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
    <p style="color:var(--text-faint)">No hay registros. Agrega el primero abajo.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Mes</th>
        <th class="num">Inversion (X)</th>
        <th class="num">Ventas (Y)</th>
        <th style="text-align:center">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($datos as $i => $d): $es_out = $outlier_por_idx[$i] ?? false; ?>
      <tr class="<?= $es_out ? 'outlier-row' : '' ?>">
        <td style="color:var(--text-faint)"><?= $d['id'] ?></td>
        <td><?= htmlspecialchars($d['mes']) ?><?= $es_out ? ' &#9888;' : '' ?></td>
        <td class="num"><?= number_format($d['inversion'], 2) ?></td>
        <td class="num"><?= number_format($d['ventas'],    2) ?></td>
        <td>
          <div class="acciones-cell">
            <a class="btn-sm edit" href="datos.php?edit=<?= $d['id'] ?>#editar">Editar</a>
            <form method="POST">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id"     value="<?= $d['id'] ?>">
              <button type="submit" class="btn-sm del"
                onclick="return confirm('¿Eliminar el registro de <?= htmlspecialchars($d['mes'], ENT_QUOTES) ?>?')">
                Eliminar
              </button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p style="color:var(--text-faint); font-size:0.8rem; margin-top:10px">
    Las filas resaltadas (&#9888;) son outliers segun el analisis de residuos (&gt; 2.5&sigma;).
  </p>
  <?php endif; ?>
</div>

<!-- Importar / Exportar -->
<div class="panel">
  <h2>Importar / Exportar CSV</h2>
  <div class="btn-row" style="margin-bottom:14px">
    <a class="btn ghost" href="export.php">&#11015; Exportar CSV</a>
  </div>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="import">
    <label class="field-label">Archivo CSV (columnas: mes, inversion, ventas)</label>
    <input type="file" name="csv" accept=".csv" required>
    <button type="submit" class="btn">&#11014; Importar CSV</button>
  </form>
</div>

<!-- Formulario agregar / editar -->
<div class="panel" id="editar">
  <h2><?= $edit_reg ? 'Editar Registro #' . (int)$edit_reg['id'] : 'Agregar Nuevo Registro' ?></h2>
  <form method="POST">
    <input type="hidden" name="action" value="<?= $edit_reg ? 'update' : 'add' ?>">
    <?php if ($edit_reg): ?>
      <input type="hidden" name="id" value="<?= (int)$edit_reg['id'] ?>">
    <?php endif; ?>
    <label class="field-label">Mes</label>
    <input type="text"   name="mes"       placeholder="Mes (ej. Abril)"  required
           value="<?= $edit_reg ? htmlspecialchars($edit_reg['mes'], ENT_QUOTES) : '' ?>">
    <label class="field-label">Inversion (X)</label>
    <input type="number" name="inversion" placeholder="Inversion (X)"    step="0.01" min="0.01" required
           value="<?= $edit_reg ? htmlspecialchars($edit_reg['inversion'], ENT_QUOTES) : '' ?>">
    <label class="field-label">Ventas (Y)</label>
    <input type="number" name="ventas"    placeholder="Ventas (Y)"        step="0.01" min="0.01" required
           value="<?= $edit_reg ? htmlspecialchars($edit_reg['ventas'], ENT_QUOTES) : '' ?>">
    <div class="btn-row">
      <button type="submit" class="btn <?= $edit_reg ? '' : 'green' ?>">
        <?= $edit_reg ? 'Guardar cambios' : 'Agregar a la BD' ?>
      </button>
      <?php if ($edit_reg): ?>
        <a class="btn ghost" href="datos.php">Cancelar</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php require __DIR__ . '/footer.php'; ?>
