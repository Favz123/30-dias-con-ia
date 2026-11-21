<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../app/bootstrap.php') ? __DIR__ . '/../app/bootstrap.php' : __DIR__ . '/app/bootstrap.php';

/*
 * Dos modos:
 *  - Portada: mosaico de categorías y una sección por categoría con lo más nuevo.
 *  - Listado: /categoria/bolsos, búsqueda o filtro de color, con orden y paginación.
 */
$slug      = (string) ($_GET['categoria'] ?? '');
$categoria = $slug !== '' ? categoria_por_slug($slug) : null;
if ($slug !== '' && !$categoria) {
    http_response_code(404);
}
$q      = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 60));
$color  = trim(mb_substr((string) ($_GET['color'] ?? ''), 0, 60));
$orden  = (string) ($_GET['orden'] ?? 'nuevos');
$orden  = isset(ORDENES[$orden]) ? $orden : 'nuevos';
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = (int) cfg('por_pagina', 24);
$listado = $categoria || $q !== '' || $color !== '' || $orden !== 'nuevos';

$conteo = contar_por_categoria();
$totalVisibles = array_sum($conteo);
$colores = colores_visibles($categoria);

if ($listado) {
    [$productos, $total] = buscar_productos($categoria, $q, $color, $pagina, $porPagina, $orden);
    $paginas = max(1, (int) ceil($total / $porPagina));
} else {
    $porCategoria = [];
    foreach (array_keys(CATEGORIAS) as $c) {
        if ($conteo[$c] > 0) {
            [$porCategoria[$c]] = buscar_productos($c, '', '', 1, 4);
        }
    }
}

/* ---------- SEO ---------- */
if ($categoria) {
    $cat = CATEGORIAS[$categoria];
    $titulo = $cat['nombre'] . ' bonitos y originales | Punto Bonito';
    $descripcion = $cat['nombre'] . ' de Punto Bonito: mira las fotos y pide el tuyo por WhatsApp. Apártalo o cómpralo con un mensaje.';
    $canonical = '/categoria/' . $cat['slug'] . ($pagina > 1 ? '?pagina=' . $pagina : '');
} else {
    $titulo = 'Punto Bonito | Bolsos, monederos, tazas, termos, aretes y más';
    $descripcion = 'Catálogo de Punto Bonito: bolsos, monederos, cosmetiqueras, lapiceras, tazas, termos, vasos, llaveros, plumas, aretes, espejos y mandiles. Aparta o compra por WhatsApp.';
    $canonical = '/';
}
// Las búsquedas y los órdenes no se indexan para no duplicar contenido.
$noindex = $q !== '' || $color !== '' || $orden !== 'nuevos' || ($slug !== '' && !$categoria);
if ($listado) {
    $jsonld = [jsonld_tienda()];
    if ($productos) {
        $jsonld[] = jsonld_lista($productos, $categoria ? CATEGORIAS[$categoria]['nombre'] : 'Resultados de búsqueda');
    }
} else {
    $jsonld = [jsonld_sitio(), jsonld_tienda(), jsonld_preguntas()];
}

/** Arma la URL del listado conservando los filtros. */
function enlace_listado(?string $categoria, string $q, string $color, string $orden = 'nuevos', int $pagina = 1): string
{
    $base = $categoria ? '/categoria/' . CATEGORIAS[$categoria]['slug'] : '/';
    $params = array_filter([
        'q' => $q,
        'color' => $color,
        'orden' => $orden !== 'nuevos' ? $orden : null,
        'pagina' => $pagina > 1 ? $pagina : null,
    ], fn ($v) => $v !== '' && $v !== null);
    return $base . ($params ? '?' . http_build_query($params) : '') . '#catalogo';
}

require APP_DIR . '/vistas/encabezado.php';
?>

<?php if (!$listado): ?>
  <section class="hero" style="padding:0" aria-labelledby="titulo">
    <div class="wrap hero-grid">
      <div>
        <span class="eyebrow">💖 Cosas bonitas para todos los días</span>
        <h1 id="titulo">Punto Bonito <span class="script">detalles que enamoran</span></h1>
        <p class="lead">Bolsos, monederos, tazas, termos, aretes y mucho más. Elige tu favorito y apártalo o cómpralo por WhatsApp con un solo toque.</p>
        <div class="hero-actions">
          <a class="btn btn-dark" href="#categorias">Ver categorías</a>
          <a class="btn btn-light" href="#como-comprar">¿Cómo comprar?</a>
        </div>
      </div>
      <div class="hero-mandala">
        <img src="/assets/img/logo.webp" width="512" height="512" alt="Logo de Punto Bonito: mandala de colores con un bolso al centro" fetchpriority="high">
        <span class="burbuja b1" aria-hidden="true">👜</span>
        <span class="burbuja b2" aria-hidden="true">☕</span>
        <span class="burbuja b3" aria-hidden="true">💎</span>
        <span class="burbuja b4" aria-hidden="true">👛</span>
      </div>
    </div>
  </section>

  <div class="ticker" aria-hidden="true">
    <div class="ticker-track">
      <?php for ($i = 0; $i < 2; $i++): ?>
      <span>Bolsos</span><span>Monederos</span><span>Tazas y termos</span><span>Aretes</span><span>Aparta por WhatsApp</span><span>Regalos bonitos</span><span>Novedades cada semana</span>
      <?php endfor; ?>
    </div>
  </div>

  <section id="categorias" aria-labelledby="cats-title" style="padding-bottom:0">
    <div class="wrap">
      <header class="section-head">
        <h2 id="cats-title">Categorías</h2>
        <span class="script">¿qué se te antoja hoy?</span>
      </header>
      <ul class="cat-grid">
        <?php foreach (CATEGORIAS as $clave => $c): ?>
          <li><a class="cat-tile c-<?= e($c['color']) ?><?= $conteo[$clave] === 0 ? ' vacia' : '' ?>" href="/categoria/<?= e($c['slug']) ?>#catalogo">
            <span class="ico-cat" aria-hidden="true"><?= e($c['icono']) ?></span>
            <strong><?= e($c['nombre']) ?></strong>
            <small><?= $conteo[$clave] === 0 ? 'Muy pronto' : ($conteo[$clave] === 1 ? '1 producto' : $conteo[$clave] . ' productos') ?></small>
          </a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
<?php endif; ?>

  <section id="catalogo" aria-labelledby="cat-title"<?= $listado ? '' : ' style="padding-top:0"' ?>>
    <div class="wrap">
      <?php if ($listado): ?>
        <nav class="crumbs" aria-label="Ruta"><a href="/">Inicio</a> › <?= $categoria ? e(CATEGORIAS[$categoria]['nombre']) : 'Búsqueda' ?></nav>
      <?php endif; ?>
      <header class="section-head">
        <?php if ($categoria): ?>
          <h1 id="cat-title" style="font-size:clamp(2.4rem,6vw,4.2rem);font-weight:900"><?= e(CATEGORIAS[$categoria]['icono'] . ' ' . CATEGORIAS[$categoria]['nombre']) ?></h1>
          <span class="script">encuentra tu favorito</span>
        <?php elseif ($listado): ?>
          <h1 id="cat-title" style="font-size:clamp(2.4rem,6vw,4.2rem);font-weight:900">Catálogo</h1>
          <span class="script">resultados</span>
        <?php else: ?>
          <h2 id="cat-title">Catálogo</h2>
          <span class="script">lo más nuevo</span>
          <p>Toca <strong>“Comprar”</strong>, <strong>“Apartar”</strong> o <strong>“Más info”</strong> y te llevamos a WhatsApp con el mensaje listo.</p>
        <?php endif; ?>
      </header>

      <nav class="filters" aria-label="Filtrar por categoría">
        <a class="filter" href="/#catalogo"<?= !$categoria ? ' aria-current="page"' : '' ?>>Todo <span class="count"><?= $totalVisibles ?></span></a>
        <?php foreach (CATEGORIAS as $clave => $c): ?>
          <?php if ($conteo[$clave] === 0 && $categoria !== $clave) continue; ?>
          <a class="filter" href="/categoria/<?= e($c['slug']) ?>#catalogo"<?= $categoria === $clave ? ' aria-current="page"' : '' ?>><span class="fi" aria-hidden="true"><?= e($c['icono']) ?></span><?= e($c['nombre']) ?> <span class="count"><?= $conteo[$clave] ?></span></a>
        <?php endforeach; ?>
      </nav>

      <form class="search" role="search" action="<?= $categoria ? '/categoria/' . e(CATEGORIAS[$categoria]['slug']) : '/' ?>#catalogo" method="get">
        <label class="sr-only" for="q">Buscar producto</label>
        <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar: bolso, taza, rosa…">
        <?php if ($colores): ?>
          <label class="sr-only" for="color">Color</label>
          <select id="color" name="color" data-autosubmit>
            <option value="">Todos los colores</option>
            <?php foreach ($colores as $col): ?>
              <option value="<?= e($col) ?>"<?= $col === $color ? ' selected' : '' ?>><?= e($col) ?></option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
        <label class="sr-only" for="orden">Ordenar</label>
        <select id="orden" name="orden" data-autosubmit>
          <?php foreach (ORDENES as $clave => $nombre): ?>
            <option value="<?= e($clave) ?>"<?= $clave === $orden ? ' selected' : '' ?>><?= e($nombre) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-dark" type="submit">Buscar</button>
      </form>

      <?php if ($listado): ?>
        <p class="results-info">
          <?= $total === 1 ? '1 producto' : $total . ' productos' ?>
          <?php if ($q !== '' || $color !== '' || $orden !== 'nuevos'): ?> · <a href="<?= e(enlace_listado($categoria, '', '')) ?>">Quitar filtros</a><?php endif; ?>
        </p>
        <?php if ($productos): ?>
          <div class="grid">
            <?php $nivelTitulo = 2; foreach ($productos as $p) { require APP_DIR . '/vistas/tarjeta.php'; } ?>
          </div>
          <?php if ($paginas > 1): ?>
            <nav class="pager" aria-label="Páginas">
              <?php if ($pagina > 1): ?><a class="btn btn-light" rel="prev" href="<?= e(enlace_listado($categoria, $q, $color, $orden, $pagina - 1)) ?>">← Anterior</a><?php endif; ?>
              <span>Página <?= $pagina ?> de <?= $paginas ?></span>
              <?php if ($pagina < $paginas): ?><a class="btn btn-dark" rel="next" href="<?= e(enlace_listado($categoria, $q, $color, $orden, $pagina + 1)) ?>">Siguiente →</a><?php endif; ?>
            </nav>
          <?php endif; ?>
        <?php else: ?>
          <div class="empty"><strong><?= $categoria && $q === '' && $color === '' ? 'Muy pronto' : 'No encontramos productos' ?></strong>
            <?= $categoria && $q === '' && $color === '' ? 'Estamos preparando las fotos de esta categoría. ¡Escríbenos por WhatsApp y pregunta qué hay!' : 'Prueba con otra palabra o revisa las demás categorías.' ?></div>
        <?php endif; ?>

      <?php elseif (!$porCategoria): ?>
        <div class="empty"><strong>Muy pronto</strong>Estamos subiendo las fotos de nuestros productos. ¡Escríbenos por WhatsApp para ver lo que hay!</div>
      <?php else: ?>
        <?php foreach ($porCategoria as $clave => $lista): $c = CATEGORIAS[$clave]; ?>
          <div class="size-block c-<?= e($c['color']) ?>" id="cat-<?= e($c['slug']) ?>">
            <div class="size-title">
              <span class="size-badge" aria-hidden="true"><?= e($c['icono']) ?></span>
              <h3><a href="/categoria/<?= e($c['slug']) ?>#catalogo" style="text-decoration:none"><?= e($c['nombre']) ?></a>
                <small><?= $conteo[$clave] === 1 ? '1 producto' : $conteo[$clave] . ' productos' ?></small></h3>
            </div>
            <div class="grid">
              <?php $nivelTitulo = 4; foreach ($lista as $p) { require APP_DIR . '/vistas/tarjeta.php'; } ?>
            </div>
            <?php if ($conteo[$clave] > count($lista)): ?>
              <div class="size-more"><a class="btn btn-dark" href="/categoria/<?= e($c['slug']) ?>#catalogo">Ver los <?= $conteo[$clave] ?> <?= e(mb_strtolower($c['nombre'])) ?> →</a></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <p class="catalog-note">¿Buscas algo en especial? <a href="<?= e(whatsapp_link('¡Hola, Punto Bonito! 👋 Busco algo en especial:')) ?>" target="_blank" rel="noopener"><strong>Pregúntanos por WhatsApp</strong></a> y te mandamos fotos.</p>
    </div>
  </section>

<?php
if (!$listado) {
    require APP_DIR . '/vistas/info.php';
}
require APP_DIR . '/vistas/pie.php';
