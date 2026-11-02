<?php
/**
 * Variables esperadas:
 * $titulo, $descripcion, $canonical (ruta), $og_imagen (URL absoluta, opcional),
 * $noindex (bool, opcional), $jsonld (lista de arreglos, opcional), $ruta_menu (prefijo de anclas)
 */
$og_imagen = $og_imagen ?? url('/assets/img/og-image.jpg');
$ruta_menu = $ruta_menu ?? '';
$waGeneral = whatsapp_link('¡Hola! Vi su catálogo en línea y tengo una duda.');
?><!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8">
<script>document.documentElement.classList.add("js")</script>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?></title>
<meta name="description" content="<?= e($descripcion) ?>">
<?php if (!empty($noindex)): ?>
<meta name="robots" content="noindex, follow">
<?php else: ?>
<meta name="robots" content="index, follow, max-image-preview:large">
<link rel="canonical" href="<?= e(url($canonical)) ?>">
<?php endif; ?>
<meta name="theme-color" content="#b897ea">
<meta name="geo.region" content="MX-CAM">
<meta name="geo.placename" content="San Francisco de Campeche">
<meta property="og:type" content="website">
<meta property="og:locale" content="es_MX">
<meta property="og:site_name" content="En Busca de Closet">
<meta property="og:title" content="<?= e($titulo) ?>">
<meta property="og:description" content="<?= e($descripcion) ?>">
<meta property="og:url" content="<?= e(url($canonical)) ?>">
<meta property="og:image" content="<?= e($og_imagen) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/img/logo-32.png">
<link rel="icon" type="image/png" sizes="64x64" href="/assets/img/logo-64.png">
<link rel="apple-touch-icon" href="/assets/img/logo-192.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Nunito:wght@400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/site.css?v=4">
<?php foreach (($jsonld ?? []) as $bloque): ?>
<script type="application/ld+json"><?= json_encode($bloque, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endforeach; ?>
</head>
<body>
<a class="skip" href="#contenido">Saltar al contenido</a>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="i-wa" viewBox="0 0 32 32"><path d="M16.04 3C8.86 3 3.03 8.82 3.03 16c0 2.3.6 4.53 1.74 6.5L3 29l6.68-1.75A12.97 12.97 0 0 0 16.04 29C23.2 29 29 23.18 29 16S23.2 3 16.04 3zm0 23.8c-2 0-3.95-.54-5.65-1.55l-.4-.24-3.96 1.04 1.06-3.86-.26-.4A10.77 10.77 0 0 1 5.2 16c0-5.97 4.86-10.83 10.84-10.83 5.97 0 10.8 4.86 10.8 10.83 0 5.98-4.83 10.8-10.8 10.8zm5.94-8.1c-.33-.16-1.93-.95-2.23-1.06-.3-.11-.52-.16-.74.17-.22.32-.85 1.06-1.04 1.28-.19.22-.38.24-.71.08-.33-.16-1.38-.51-2.63-1.62-.97-.87-1.63-1.94-1.82-2.27-.19-.33-.02-.5.14-.67.15-.15.33-.38.49-.57.16-.19.22-.33.33-.54.11-.22.05-.41-.03-.57-.08-.16-.74-1.78-1.01-2.44-.27-.64-.54-.55-.74-.56h-.63c-.22 0-.57.08-.87.41-.3.33-1.14 1.11-1.14 2.71s1.17 3.15 1.33 3.36c.16.22 2.3 3.5 5.56 4.91.78.34 1.39.54 1.86.69.78.25 1.49.21 2.05.13.63-.09 1.93-.79 2.2-1.55.27-.76.27-1.41.19-1.55-.08-.14-.3-.22-.63-.38z"/></symbol>
  <symbol id="i-ig" viewBox="0 0 24 24"><path d="M12 2.2c3.2 0 3.6 0 4.8.1 3.3.1 4.8 1.7 4.9 4.9.1 1.3.1 1.6.1 4.8s0 3.6-.1 4.8c-.1 3.2-1.7 4.8-4.9 4.9-1.3.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-3.3-.1-4.8-1.7-4.9-4.9C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.8C2.4 3.9 3.9 2.4 7.2 2.3 8.4 2.2 8.8 2.2 12 2.2zm0 4.7a5.1 5.1 0 1 0 0 10.2 5.1 5.1 0 0 0 0-10.2zm0 8.4a3.3 3.3 0 1 1 0-6.6 3.3 3.3 0 0 1 0 6.6zm5.3-9.8a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4z"/></symbol>
</svg>

<header class="site-header">
  <nav class="wrap nav" aria-label="Principal">
    <a class="brand" href="/" aria-label="En Busca de Closet, inicio">
      <img src="/assets/img/logo-64.png" width="48" height="48" alt="Logo de En Busca de Closet: gancho de ropa">
      <span>En Busca de Closet</span>
    </a>
    <button class="menu-toggle" aria-expanded="false" aria-controls="menu" aria-label="Abrir menú"><span></span><span></span><span></span></button>
    <ul class="menu" id="menu">
      <li><a href="/#catalogo">Catálogo</a></li>
      <li><a href="/#como-comprar">Cómo comprar</a></li>
      <li><a href="/#envios">Envíos</a></li>
      <li><a href="/#preguntas">Preguntas</a></li>
      <li><a href="/#tienda">Visítanos</a></li>
    </ul>
    <a class="btn btn-dark btn-header" href="<?= e($waGeneral) ?>" target="_blank" rel="noopener">
      <svg class="ico" aria-hidden="true"><use href="#i-wa"/></svg> Escríbenos
    </a>
  </nav>
</header>
<main id="contenido">
