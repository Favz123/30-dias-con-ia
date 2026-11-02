<?php
/** Encabezado del panel. Variables: $titulo, $volver (opcional: URL del botón atrás). */
$aviso = tomar_aviso();
$usuarioSesion = usuario_actual();
?><!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title><?= e($titulo) ?> · Panel En Busca de Closet</title>
<meta name="theme-color" content="#141018">
<link rel="manifest" href="/admin/manifest.webmanifest">
<link rel="icon" type="image/png" sizes="64x64" href="/assets/img/logo-64.png">
<link rel="apple-touch-icon" href="/assets/img/logo-192.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Closet Panel">
<link rel="stylesheet" href="/admin/admin.css?v=1">
</head>
<body>
<header class="top">
  <?php if (!empty($volver)): ?>
    <a class="top-btn" href="<?= e($volver) ?>" aria-label="Regresar">←</a>
  <?php else: ?>
    <img src="/assets/img/logo-64.png" width="36" height="36" alt="">
  <?php endif; ?>
  <h1><?= e($titulo) ?></h1>
  <?php if ($usuarioSesion): ?>
    <a class="top-btn small" href="/" target="_blank" rel="noopener" title="Ver la tienda">Tienda ↗</a>
  <?php endif; ?>
</header>
<?php if ($aviso): ?>
  <div class="toast <?= e($aviso['tipo']) ?>" role="status"><?= e($aviso['texto']) ?></div>
<?php endif; ?>
<main class="page">
