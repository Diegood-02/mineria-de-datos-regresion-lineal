<?php
// Exporta la tabla inversion_ventas como archivo CSV descargable.
require_once __DIR__ . '/db.php';

$res = $conn->query("SELECT id, mes, inversion, ventas FROM inversion_ventas ORDER BY id");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="inversion_ventas.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['id', 'mes', 'inversion', 'ventas']);
while ($fila = $res->fetch_assoc()) {
    fputcsv($out, [$fila['id'], $fila['mes'], $fila['inversion'], $fila['ventas']]);
}
fclose($out);
$conn->close();
