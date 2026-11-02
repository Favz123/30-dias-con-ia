<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../app/bootstrap.php') ? __DIR__ . '/../app/bootstrap.php' : __DIR__ . '/app/bootstrap.php';

/*
 * Dos modos:
 *  - Portada: 4 secciones (una por talla) con las prendas más nuevas.
 *  - Listado: /talla/chica, búsqueda o filtro de marca, con paginación.
 */
$slug   = (string) ($_GET['talla'] ?? '');
$talla  = $slug !== '' ? talla_por_slug($slug) : null;
if ($slug !== '' && !$talla) {
    http_response_code(404);
}
$q      = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 60));
$marca  = trim(mb_substr((string) ($_GET['marca'] ?? ''), 0, 60));
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = (int) cfg('por_pagina', 24);
$listado = $talla || $q !== '' || $marca !== '';

$conteo = contar_por_talla();
$totalVisibles = array_sum($conteo);
$marcas = marcas_visibles($talla);

if ($listado) {
    [$prendas, $total] = buscar_prendas($talla, $q, $marca, $pagina, $porPagina);
    $paginas = max(1, (int) ceil($total / $porPagina));
} else {
    $porTalla = [];
    foreach (array_keys(TALLAS) as $t) {
        [$porTalla[$t]] = buscar_prendas($t, '', '', 1, 8);
    }
}

/* ---------- SEO ---------- */
if ($talla) {
    $nombreTalla = TALLAS[$talla]['nombre'];
    $titulo = "Ropa talla $nombreTalla de segunda mano en Campeche | En Busca de Closet";
    $descripcion = "Prendas de marca talla $nombreTalla: shorts, tops, playeras y más a precio de closet. Apártalas por WhatsApp. Envíos a todo México.";
    $canonical = '/talla/' . TALLAS[$talla]['slug'] . ($pagina > 1 ? '?pagina=' . $pagina : '');
} else {
    $titulo = 'En Busca de Closet | Ropa de paca y segunda mano en Campeche';
    $descripcion = 'Catálogo de ropa de marca de segunda mano en Campeche: Nike, Adidas y más en tallas Extra chica, Chica, Mediana, Grande y Extragrande. Envíos a todo México y tienda física en Col. San José.';
    $canonical = '/';
}
// Las búsquedas no se indexan para no duplicar contenido.
$noindex = $q !== '' || $marca !== '' || ($slug !== '' && !$talla);
$jsonld = $listado ? [jsonld_tienda()] : [jsonld_tienda(), jsonld_preguntas()];

/** Arma la URL del listado conservando los filtros. */
function enlace_listado(?string $talla, string $q, string $marca, int $pagina = 1): string
{
    $base = $talla ? '/talla/' . TALLAS[$talla]['slug'] : '/';
    $params = array_filter(['q' => $q, 'marca' => $marca, 'pagina' => $pagina > 1 ? $pagina : null], fn ($v) => $v !== '' && $v !== null);
    return $base . ($params ? '?' . http_build_query($params) : '') . '#catalogo';
}

// Fotos para el collage de la portada: las 3 más recientes disponibles.
$collage = [];
if (!$listado) {
    foreach ($porTalla as $lista) {
        foreach ($lista as $p) {
            if ($p['estado'] === 'disponible' && $p['foto'] !== '') {
                $collage[] = $p;
                break;
            }
        }
    }
    $collage = array_slice($collage, 0, 3);
}

require APP_DIR . '/vistas/encabezado.php';
?>

<?php if (!$listado): ?>
  <section class="hero" style="padding:0" aria-labelledby="titulo">
    <div class="wrap hero-grid">
      <div>
        <span class="eyebrow">📍 Campeche · Envíos a todo México</span>
        <h1 id="titulo">En Busca de Closet <span class="script">ropa con segunda historia</span></h1>
        <p class="lead">Prendas de marca seleccionadas una por una. Elige tu talla, mándanos WhatsApp y apártala antes que nadie.</p>
        <div class="hero-actions">
          <a class="btn btn-dark" href="#catalogo">Ver catálogo</a>
          <a class="btn btn-light" href="#como-comprar">¿Cómo comprar?</a>
        </div>
        <ul class="size-chips" aria-label="Ir a una talla">
          <?php foreach (TALLAS as $clave => $t): ?>
            <li><a href="/talla/<?= e($t['slug']) ?>#catalogo" title="Talla <?= e($t['nombre']) ?>"><?= e($clave) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="collage" aria-hidden="true">
        <?php foreach ($collage as $i => $p): ?>
          <figure class="polaroid p<?= $i + 1 ?>"><img src="<?= e(foto_url($p, true)) ?>" width="600" height="600" alt=""><figcaption>talla <?= e($p['talla']) ?></figcaption></figure>
        <?php endforeach; ?>
        <img class="hero-logo" src="/assets/img/logo.webp" width="512" height="512" alt="">
      </div>
    </div>
  </section>

  <div class="ticker" aria-hidden="true">
    <div class="ticker-track">
      <?php for ($i = 0; $i < 2; $i++): ?>
      <span>Ropa de marca</span><span>Precios de closet</span><span>Envíos a todo México</span><span>Tienda física en Campeche</span><span>Apartados con 50%</span>
      <?php endfor; ?>
    </div>
  </div>
<?php endif; ?>

  <section id="catalogo" aria-labelledby="cat-title">
    <div class="wrap">
      <?php if ($listado): ?>
        <nav class="crumbs" aria-label="Ruta"><a href="/">Inicio</a> › <?= $talla ? 'Talla ' . e(TALLAS[$talla]['nombre']) : 'Búsqueda' ?></nav>
      <?php endif; ?>
      <header class="section-head">
        <?php if ($talla): ?>
          <h1 id="cat-title" style="font-size:clamp(2.4rem,6vw,4.2rem);font-weight:900">Talla <?= e(TALLAS[$talla]['nombre']) ?></h1>
          <span class="script">encuentra tu prenda</span>
        <?php elseif ($listado): ?>
          <h1 id="cat-title" style="font-size:clamp(2.4rem,6vw,4.2rem);font-weight:900">Catálogo</h1>
          <span class="script">resultados de búsqueda</span>
        <?php else: ?>
          <h2 id="cat-title">Catálogo</h2>
          <span class="script">encuentra tu talla</span>
          <p>Cada prenda es pieza única. Toca <strong>“Me interesa”</strong> y te llevamos a WhatsApp con el mensaje listo para verificar disponibilidad.</p>
        <?php endif; ?>
      </header>

      <nav class="filters" aria-label="Filtrar por talla">
        <a class="filter" href="/#catalogo"<?= !$talla ? ' aria-current="page"' : '' ?>>Todas <span class="count"><?= $totalVisibles ?></span></a>
        <?php foreach (TALLAS as $clave => $t): ?>
          <a class="filter" href="/talla/<?= e($t['slug']) ?>#catalogo"<?= $talla === $clave ? ' aria-current="page"' : '' ?>><?= e($t['nombre']) ?> <span class="count"><?= $conteo[$clave] ?></span></a>
        <?php endforeach; ?>
      </nav>

      <form class="search" role="search" action="<?= $talla ? '/talla/' . e(TALLAS[$talla]['slug']) : '/' ?>#catalogo" method="get">
        <label class="sr-only" for="q">Buscar prenda</label>
        <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar: short, top, Nike, CH-0012…">
        <?php if ($marcas): ?>
          <label class="sr-only" for="marca">Marca</label>
          <select id="marca" name="marca" data-autosubmit>
            <option value="">Todas las marcas</option>
            <?php foreach ($marcas as $m): ?>
              <option value="<?= e($m) ?>"<?= $m === $marca ? ' selected' : '' ?>><?= e($m) ?></option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
        <button class="btn btn-dark" type="submit">Buscar</button>
      </form>

      <?php if ($listado): ?>
        <p class="results-info">
          <?= $total === 1 ? '1 prenda' : $total . ' prendas' ?>
          <?php if ($q !== '' || $marca !== ''): ?> · <a href="<?= e(enlace_listado($talla, '', '')) ?>">Quitar filtros</a><?php endif; ?>
        </p>
        <?php if ($prendas): ?>
          <div class="grid">
            <?php foreach ($prendas as $p) { require APP_DIR . '/vistas/tarjeta.php'; } ?>
          </div>
          <?php if ($paginas > 1): ?>
            <nav class="pager" aria-label="Páginas">
              <?php if ($pagina > 1): ?><a class="btn btn-light" rel="prev" href="<?= e(enlace_listado($talla, $q, $marca, $pagina - 1)) ?>">← Anterior</a><?php endif; ?>
              <span>Página <?= $pagina ?> de <?= $paginas ?></span>
              <?php if ($pagina < $paginas): ?><a class="btn btn-dark" rel="next" href="<?= e(enlace_listado($talla, $q, $marca, $pagina + 1)) ?>">Siguiente →</a><?php endif; ?>
            </nav>
          <?php endif; ?>
        <?php else: ?>
          <div class="empty"><strong>No encontramos prendas</strong>Prueba con otra palabra o revisa las demás tallas.</div>
        <?php endif; ?>

      <?php else: ?>
        <?php foreach (TALLAS as $clave => $t): ?>
          <div class="size-block" id="talla-<?= e($t['slug']) ?>">
            <div class="size-title">
              <span class="size-badge" aria-hidden="true"><?= e($clave) ?></span>
              <h3><a href="/talla/<?= e($t['slug']) ?>#catalogo" style="text-decoration:none">Talla <?= e($t['nombre']) ?></a>
                <small><?= $conteo[$clave] === 1 ? '1 prenda' : $conteo[$clave] . ' prendas' ?></small></h3>
            </div>
            <?php if ($porTalla[$clave]): ?>
              <div class="grid">
                <?php foreach ($porTalla[$clave] as $p) { require APP_DIR . '/vistas/tarjeta.php'; } ?>
              </div>
              <?php if ($conteo[$clave] > count($porTalla[$clave])): ?>
                <div class="size-more"><a class="btn btn-dark" href="/talla/<?= e($t['slug']) ?>#catalogo">Ver las <?= $conteo[$clave] ?> prendas talla <?= e($t['nombre']) ?> →</a></div>
              <?php endif; ?>
            <?php else: ?>
              <div class="empty"><strong>Pronto habrá más</strong>Síguenos en Instagram para ver las actualizaciones.</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <p class="catalog-note">¿Buscas algo en especial? Síguenos en <a href="https://www.instagram.com/enbuscadecloset/" target="_blank" rel="noopener"><strong>@enbuscadecloset</strong></a> para ver las actualizaciones primero.</p>
    </div>
  </section>

<?php
if (!$listado) {
    require APP_DIR . '/vistas/info.php';
}
require APP_DIR . '/vistas/pie.php';
