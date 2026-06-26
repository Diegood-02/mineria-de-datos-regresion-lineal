<?php
$titulo_pagina = $titulo_pagina ?? 'Sistema';
$pagina_activa = $pagina_activa ?? '';
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($titulo_pagina) ?> &mdash; Regresion Lineal</title>
<script>
  // Aplica el tema guardado antes de pintar para evitar parpadeo.
  try {
    var t = localStorage.getItem('tema');
    if (t) document.documentElement.setAttribute('data-theme', t);
  } catch (e) {}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/styles.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/app.js"></script>
</head>
<body>

<nav class="navbar">
  <div class="navbar-brand">
    <div class="brand-icon">&#128202;</div>
    <div>
      <div class="brand-title">Regresion Lineal</div>
      <div class="brand-sub">Inversion vs Ventas &nbsp;&middot;&nbsp; Mineria de Datos &nbsp;&middot;&nbsp; IPN</div>
    </div>
  </div>
  <div class="navbar-right">
    <ul class="nav-links">
      <li><a href="index.php"      class="<?= $pagina_activa === 'dashboard'  ? 'active' : '' ?>">Dashboard</a></li>
      <li><a href="datos.php"      class="<?= $pagina_activa === 'datos'      ? 'active' : '' ?>">Gestion de Datos</a></li>
      <li><a href="modelo.php"     class="<?= $pagina_activa === 'modelo'     ? 'active' : '' ?>">Modelo</a></li>
      <li><a href="prediccion.php" class="<?= $pagina_activa === 'prediccion' ? 'active' : '' ?>">Prediccion</a></li>
    </ul>
    <button type="button" id="theme-toggle" class="theme-toggle" title="Cambiar tema" aria-label="Cambiar tema">☀️</button>
  </div>
</nav>

<div class="container">
