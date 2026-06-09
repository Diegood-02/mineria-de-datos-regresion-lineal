<?php
$titulo_pagina = $titulo_pagina ?? 'Sistema';
$pagina_activa = $pagina_activa ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($titulo_pagina) ?> &mdash; Regresion Lineal</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; }

  /* ── Navbar ── */
  .navbar {
    background: #0d1526;
    border-bottom: 1px solid #1e293b;
    padding: 0 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 64px;
    position: sticky;
    top: 0;
    z-index: 100;
  }
  .navbar-brand { display: flex; align-items: center; gap: 14px; }
  .brand-icon { font-size: 1.8rem; line-height: 1; }
  .brand-title { font-size: 1.05rem; font-weight: 700; color: #e2e8f0; }
  .brand-sub { font-size: 0.73rem; color: #64748b; margin-top: 2px; }
  .nav-links { display: flex; list-style: none; gap: 4px; }
  .nav-links a {
    color: #94a3b8;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: background 0.18s, color 0.18s;
    white-space: nowrap;
  }
  .nav-links a:hover { background: #1e293b; color: #e2e8f0; }
  .nav-links a.active { background: #1d4ed8; color: #fff; font-weight: 600; }

  @media (max-width: 720px) {
    .navbar { flex-direction: column; align-items: flex-start; padding: 14px 16px; gap: 10px; }
    .nav-links { flex-wrap: wrap; }
  }

  /* ── Layout ── */
  .container { max-width: 1100px; margin: 0 auto; padding: 28px 20px; }
  .page-header { margin-bottom: 24px; padding-bottom: 14px; border-bottom: 1px solid #1e293b; }
  .page-header h1 { font-size: 1.35rem; font-weight: 700; color: #e2e8f0; }

  /* ── Cards ── */
  .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px; }
  .card { background: #1e293b; border-radius: 12px; padding: 20px; border-left: 4px solid #3b82f6; }
  .card.green  { border-color: #10b981; }
  .card.purple { border-color: #8b5cf6; }
  .card.orange { border-color: #f59e0b; }
  .card-label  { font-size: 0.78rem; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em; margin-bottom: 6px; }
  .card-value  { font-size: 1.7rem; font-weight: 700; }

  /* ── Grid ── */
  .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
  @media (max-width: 720px) { .grid2 { grid-template-columns: 1fr; } }

  /* ── Panels ── */
  .panel { background: #1e293b; border-radius: 12px; padding: 22px; margin-bottom: 20px; }
  .panel h2 { font-size: 1rem; font-weight: 600; color: #93c5fd; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.05em; }

  canvas { width: 100% !important; }

  /* ── Table ── */
  table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
  thead th { background: #334155; padding: 10px 14px; text-align: left; color: #94a3b8; font-weight: 600; }
  tbody td { padding: 10px 14px; border-bottom: 1px solid #334155; vertical-align: middle; }
  tbody tr:hover { background: #263348; }

  /* ── Equation & Metrics ── */
  .ecuacion {
    background: #0f172a; border-radius: 8px; padding: 14px 18px;
    font-size: 1.15rem; font-weight: 700; color: #60a5fa;
    text-align: center; margin-bottom: 16px; border: 1px solid #1d4ed8;
  }
  .coef { display: flex; gap: 16px; margin-bottom: 12px; flex-wrap: wrap; }
  .coef div { flex: 1; min-width: 80px; background: #0f172a; border-radius: 8px; padding: 10px 14px; text-align: center; }
  .coef small { color: #94a3b8; font-size: 0.78rem; }
  .coef strong { display: block; font-size: 1.3rem; color: #a78bfa; }

  /* ── Forms ── */
  form input, form select {
    width: 100%; background: #0f172a; border: 1px solid #334155;
    color: #e2e8f0; border-radius: 8px; padding: 9px 12px;
    font-size: 0.95rem; margin-bottom: 10px;
  }
  form input:focus { outline: none; border-color: #3b82f6; }

  /* ── Buttons ── */
  .btn {
    width: 100%; padding: 10px; background: #3b82f6; color: white;
    border: none; border-radius: 8px; font-size: 1rem; cursor: pointer;
    font-weight: 600; transition: background 0.2s;
  }
  .btn:hover { background: #2563eb; }
  .btn.green { background: #10b981; }
  .btn.green:hover { background: #059669; }
  .btn-sm {
    padding: 4px 10px; background: #ef4444; color: white;
    border: none; border-radius: 6px; font-size: 0.78rem;
    cursor: pointer; font-weight: 600; transition: background 0.2s;
  }
  .btn-sm:hover { background: #dc2626; }

  /* ── Prediction result ── */
  .pred-result {
    margin-top: 14px; background: #0f172a; border-radius: 8px;
    padding: 20px; text-align: center; border: 1px solid #10b981;
  }
  .pred-result span { font-size: 2rem; font-weight: 700; color: #34d399; display: block; margin-top: 6px; }

  /* ── Alerts ── */
  .alert { background: #1e3a5f; border-left: 4px solid #3b82f6; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
  .alert.warn    { background: #422006; border-left-color: #f59e0b; }
  .alert.error   { background: #450a0a; border-left-color: #ef4444; }
  .alert.success { background: #052e16; border-left-color: #10b981; }
</style>
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
  <ul class="nav-links">
    <li><a href="index.php"      class="<?= $pagina_activa === 'dashboard'  ? 'active' : '' ?>">Dashboard</a></li>
    <li><a href="datos.php"      class="<?= $pagina_activa === 'datos'      ? 'active' : '' ?>">Gestion de Datos</a></li>
    <li><a href="modelo.php"     class="<?= $pagina_activa === 'modelo'     ? 'active' : '' ?>">Modelo</a></li>
    <li><a href="prediccion.php" class="<?= $pagina_activa === 'prediccion' ? 'active' : '' ?>">Prediccion</a></li>
  </ul>
</nav>

<div class="container">
