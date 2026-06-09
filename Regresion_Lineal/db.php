<?php
require_once __DIR__ . '/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die('<div style="color:#ef4444;padding:24px;font-family:sans-serif;background:#0f172a">
         Error de conexion: ' . htmlspecialchars($conn->connect_error) . '</div>');
}
