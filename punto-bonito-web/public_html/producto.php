<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../app/bootstrap.php') ? __DIR__ . '/../app/bootstrap.php' : __DIR__ . '/app/bootstrap.php';

$codigo = (string) ($_GET['c'] ?? '');
$p = preg_match('/^[A-Za-z]{1,3}-\d{1,6}$/', $codigo) ? producto_por_codigo($codigo) : null;

if (!$p || !producto_visible($p)) {
    http_response_code($p ? 410 : 404); // 410: ya se vendió hace tiempo
    $titulo = 'Este producto ya no está disponible | Punto Bonito';
    $descripcion = 'El producto que buscas ya se vendió. Mira el catálogo con los productos disponibles.';
    $canonical = '/';
    $noindex = true;
    require APP_DIR . '/vistas/encabezado.php';
    ?>
    <section>
      <div class="wrap">
        <div class="empty" style="max-width:640px;margin:40px auto">
          <?php if ($codigo !== ''): ?>
          <strong>Este producto ya encontró nuevo hogar 💖</strong>
          <p>Ya se vendió o no existe. Tenemos muchos más esperándote.</p>
          <?php else: ?>
          <strong>No encontramos esta página</strong>
          <p>Puede que el enlace esté mal escrito. Revisa nuestro catálogo.</p>
          <?php endif; ?>
          <p><a class="btn btn-dark" href="/#catalogo">Ver catálogo</a></p>
        </div>
      </div>
    </section>
    <?php
    require APP_DIR . '/vistas/pie.php';
    exit;
}

$categoriaP = CATEGORIAS[$p['categoria']];
$titulo = sprintf('%s%s | Punto Bonito', $p['nombre'], $p['precio'] !== null ? ' ' . precio($p['precio']) : '');
$descripcion = $p['descripcion'] !== ''
    ? mb_substr($p['descripcion'], 0, 150)
    : sprintf('%s%s de Punto Bonito%s. Mira la foto y apártalo o cómpralo por WhatsApp.', $p['nombre'], $p['precio'] !== null ? ' a ' . precio($p['precio']) : '', en_ciudad());
$canonical = producto_url($p);
$og_imagen = url(foto_url($p));
$og_imagen_alt = texto_alt($p);
// Medidas reales de la foto, para que la página no "brinque" al cargar.
$medidas = $p['foto'] !== '' ? @getimagesize(rtrim((string) cfg('carpeta_fotos'), '/') . '/' . $p['foto']) : false;
[$fotoAncho, $fotoAlto] = $medidas ? [$medidas[0], $medidas[1]] : [1200, 1200];
$noindex = $p['estado'] === 'vendido';

$disponibilidad = [
    'disponible' => 'https://schema.org/InStock',
    'apartado'   => 'https://schema.org/LimitedAvailability',
    'vendido'    => 'https://schema.org/SoldOut',
][$p['estado']];
$producto = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $p['nombre'],
    'sku' => $p['codigo'],
    'image' => [url(foto_url($p))],
    'description' => $descripcion,
    'category' => $categoriaP['nombre'],
    'itemCondition' => 'https://schema.org/NewCondition',
    'brand' => ['@type' => 'Brand', 'name' => 'Punto Bonito'],
];
if ($p['color'] !== '') {
    $producto['color'] = $p['color'];
}
if ($p['precio'] !== null) {
    $producto['offers'] = [
        '@type' => 'Offer',
        'price' => $p['precio'],
        'priceCurrency' => 'MXN',
        'availability' => $disponibilidad,
        'inventoryLevel' => ['@type' => 'QuantitativeValue', 'value' => $p['estado'] === 'disponible' ? $p['cantidad'] : 0],
        'itemCondition' => 'https://schema.org/NewCondition',
        'url' => producto_url($p, true),
        'seller' => ['@type' => 'Organization', 'name' => 'Punto Bonito'],
    ];
}
$migas = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $categoriaP['nombre'], 'item' => url('/categoria/' . $categoriaP['slug'])],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $p['nombre']],
    ],
];
$jsonld = [$producto, $migas];

// Más productos disponibles de la misma categoría.
[$similares] = buscar_productos($p['categoria'], '', '', 1, 9);
$similares = array_slice(array_values(array_filter($similares, fn ($s) => $s['id'] !== $p['id'] && $s['estado'] === 'disponible')), 0, 4);

require APP_DIR . '/vistas/encabezado.php';
?>
  <section style="padding-top:0">
    <div class="wrap">
      <nav class="crumbs" aria-label="Ruta">
        <a href="/">Inicio</a> › <a href="/categoria/<?= e($categoriaP['slug']) ?>#catalogo"><?= e($categoriaP['nombre']) ?></a> › <?= e($p['nombre']) ?>
      </nav>
      <div class="product c-<?= e($categoriaP['color']) ?> estado-<?= e($p['estado']) ?>">
        <figure class="product-photo">
          <img src="<?= e(foto_url($p)) ?>" width="<?= $fotoAncho ?>" height="<?= $fotoAlto ?>" alt="<?= e(texto_alt($p)) ?>" fetchpriority="high">
          <?php if ($p['estado'] !== 'disponible'): ?><span class="ribbon"><?= e(ESTADOS_TIENDA[$p['estado']]) ?></span><?php endif; ?>
          <figcaption><?= e($p['nombre']) ?> · Código <?= e($p['codigo']) ?></figcaption>
        </figure>
        <div class="product-info">
          <div class="chips">
            <span class="chip dark"><?= e($categoriaP['icono'] . ' ' . $categoriaP['singular']) ?></span>
            <?php if ($p['color'] !== ''): ?><span class="chip"><?= e($p['color']) ?></span><?php endif; ?>
            <?php if ($p['detalle'] !== ''): ?><span class="chip"><?= e($p['detalle']) ?></span><?php endif; ?>
            <?php if (es_nuevo($p)): ?><span class="chip">✨ Nuevo</span><?php endif; ?>
            <?php if ($p['estado'] === 'apartado'): ?><span class="chip warn">Apartado</span><?php endif; ?>
          </div>
          <h1><?= e($p['nombre']) ?></h1>
          <p class="price"><?= $p['precio'] !== null ? e(precio($p['precio'])) : '<span class="price ask">Precio por WhatsApp</span>' ?></p>
          <p class="code">Código: <?= e($p['codigo']) ?></p>
          <?php if ($p['estado'] === 'disponible'): ?>
            <p class="stock grande<?= $p['cantidad'] <= 2 ? ' pocas' : '' ?>">Disponibles: <b><?= $p['cantidad'] ?></b><?= $p['cantidad'] === 1 ? ' · ¡es la última!' : '' ?></p>
          <?php elseif ($p['estado'] === 'vendido'): ?>
            <p class="stock grande agotado">Agotado</p>
          <?php endif; ?>
          <?php if ($p['descripcion'] !== ''): ?><p class="desc"><?= nl2br(e($p['descripcion'])) ?></p><?php endif; ?>

          <?php require APP_DIR . '/vistas/botones.php'; ?>
          <?php if ($p['estado'] === 'apartado'): ?>
            <p class="desc">Si el apartado se libera, vuelve a estar disponible aquí mismo.</p>
          <?php endif; ?>
          <button class="compartir" type="button" data-compartir data-titulo="<?= e($p['nombre']) ?>" data-url="<?= e(producto_url($p, true)) ?>">🔗 Compartir este producto</button>

          <div class="mini-faq">
            <strong>¿Cómo funciona?</strong>
            <ul>
              <li><strong>Comprar:</strong> te confirmamos disponibilidad, forma de pago y entrega.</li>
              <li><strong>Apartar:</strong> te lo guardamos y te decimos el anticipo y el plazo.</li>
              <li><strong>Más info:</strong> pídenos más fotos, medidas o colores. <a href="/#preguntas">Preguntas frecuentes</a></li>
            </ul>
          </div>
        </div>
      </div>

      <?php if ($similares): ?>
        <div class="size-title c-<?= e($categoriaP['color']) ?>" style="margin-top:40px"><span class="size-badge" aria-hidden="true"><?= e($categoriaP['icono']) ?></span><h2 style="font-size:clamp(1.6rem,4vw,2.2rem)">Más <?= e(mb_strtolower($categoriaP['nombre'])) ?></h2></div>
        <div class="grid">
          <?php $nivelTitulo = 3; foreach ($similares as $p) { require APP_DIR . '/vistas/tarjeta.php'; } ?>
        </div>
        <div class="size-more"><a class="btn btn-dark" href="/categoria/<?= e($categoriaP['slug']) ?>#catalogo">Ver todos los <?= e(mb_strtolower($categoriaP['nombre'])) ?> →</a></div>
      <?php endif; ?>
    </div>
  </section>
<?php require APP_DIR . '/vistas/pie.php';
