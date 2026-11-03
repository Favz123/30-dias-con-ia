<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../app/bootstrap.php') ? __DIR__ . '/../app/bootstrap.php' : __DIR__ . '/app/bootstrap.php';

$codigo = (string) ($_GET['c'] ?? '');
$p = preg_match('/^[A-Za-z]{1,3}-\d{1,6}$/', $codigo) ? prenda_por_codigo($codigo) : null;

if (!$p || !prenda_visible($p)) {
    http_response_code($p ? 410 : 404); // 410: ya se vendió hace tiempo
    $titulo = 'Esta prenda ya no está disponible | En Busca de Closet';
    $descripcion = 'La prenda que buscas ya se vendió. Mira el catálogo con las prendas disponibles.';
    $canonical = '/';
    $noindex = true;
    require APP_DIR . '/vistas/encabezado.php';
    ?>
    <section>
      <div class="wrap">
        <div class="empty" style="max-width:640px;margin:40px auto">
          <?php if ($codigo !== ''): ?>
          <strong>Esta prenda ya encontró nuevo closet 💜</strong>
          <p>Ya se vendió o no existe. Tenemos muchas más esperándote.</p>
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

$tallaP = TALLAS[$p['talla']];
$titulo = sprintf('%s talla %s%s | En Busca de Closet', $p['nombre'], $tallaP['nombre'], $p['precio'] !== null ? ' ' . precio($p['precio']) : '');
$descripcion = $p['descripcion'] !== ''
    ? mb_substr($p['descripcion'], 0, 150)
    : sprintf('%s %s talla %s de segunda mano en Campeche. Apártala por WhatsApp; envíos a todo México.', $p['nombre'], $p['marca'], $tallaP['nombre']);
$canonical = prenda_url($p);
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
    'size' => $tallaP['nombre'],
    'itemCondition' => 'https://schema.org/UsedCondition',
];
if ($p['marca'] !== '') {
    $producto['brand'] = ['@type' => 'Brand', 'name' => $p['marca']];
}
if ($p['precio'] !== null) {
    $producto['offers'] = [
        '@type' => 'Offer',
        'price' => $p['precio'],
        'priceCurrency' => 'MXN',
        'availability' => $disponibilidad,
        'itemCondition' => 'https://schema.org/UsedCondition',
        'url' => prenda_url($p, true),
        'seller' => ['@type' => 'Organization', 'name' => 'En Busca de Closet'],
    ];
}
$migas = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Talla ' . $tallaP['nombre'], 'item' => url('/talla/' . $tallaP['slug'])],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $p['nombre']],
    ],
];
$jsonld = [$producto, $migas];

// Más prendas disponibles de la misma talla.
[$similares] = buscar_prendas($p['talla'], '', '', 1, 9);
$similares = array_slice(array_values(array_filter($similares, fn ($s) => $s['id'] !== $p['id'] && $s['estado'] === 'disponible')), 0, 4);

require APP_DIR . '/vistas/encabezado.php';
?>
  <section style="padding-top:0">
    <div class="wrap">
      <nav class="crumbs" aria-label="Ruta">
        <a href="/">Inicio</a> › <a href="/talla/<?= e($tallaP['slug']) ?>#catalogo">Talla <?= e($tallaP['nombre']) ?></a> › <?= e($p['nombre']) ?>
      </nav>
      <div class="product estado-<?= e($p['estado']) ?>">
        <figure class="product-photo">
          <img src="<?= e(foto_url($p)) ?>" width="<?= $fotoAncho ?>" height="<?= $fotoAlto ?>" alt="<?= e(texto_alt($p)) ?>" fetchpriority="high">
          <?php if ($p['estado'] !== 'disponible'): ?><span class="ribbon"><?= e(ESTADOS[$p['estado']]) ?></span><?php endif; ?>
          <figcaption>Foto real de la prenda · <?= e($p['nombre']) ?>, talla <?= e(mb_strtolower($tallaP['nombre'])) ?> · Código <?= e($p['codigo']) ?></figcaption>
        </figure>
        <div class="product-info">
          <div class="chips">
            <span class="chip dark">Talla <?= e($tallaP['nombre']) ?></span>
            <?php if ($p['marca'] !== ''): ?><span class="chip"><?= e($p['marca']) ?></span><?php endif; ?>
            <?php if ($p['detalle'] !== ''): ?><span class="chip"><?= e($p['detalle']) ?></span><?php endif; ?>
            <?php if ($p['estado'] === 'apartado'): ?><span class="chip warn">Apartada</span><?php endif; ?>
          </div>
          <h1><?= e($p['nombre']) ?></h1>
          <p class="price"><?= $p['precio'] !== null ? e(precio($p['precio'])) : '<span class="price ask">Precio por WhatsApp</span>' ?></p>
          <p class="code">Código: <?= e($p['codigo']) ?></p>
          <?php if ($p['descripcion'] !== ''): ?><p class="desc"><?= nl2br(e($p['descripcion'])) ?></p><?php endif; ?>

          <?php if ($p['estado'] === 'disponible'): ?>
            <a class="btn btn-wa" href="<?= e(whatsapp_link(mensaje_prenda($p))) ?>" target="_blank" rel="noopener"><svg class="ico" aria-hidden="true"><use href="#i-wa"/></svg> Me interesa</a>
          <?php elseif ($p['estado'] === 'apartado'): ?>
            <span class="btn btn-off" aria-disabled="true">Apartada por alguien más</span>
            <p class="desc">Si el apartado se libera, vuelve a estar disponible aquí mismo.</p>
          <?php else: ?>
            <span class="btn btn-off" aria-disabled="true">¡Ya se vendió!</span>
          <?php endif; ?>

          <div class="mini-faq">
            <strong>Antes de comprar</strong>
            <ul>
              <li>Envío por Correos de México <strong>$80</strong> o Estafeta desde <strong>$160</strong>.</li>
              <li>Apartas con el <strong>50%</strong> y tienes una semana para liquidar.</li>
              <li>En Campeche: entrega en tienda o a domicilio. <a href="/#preguntas">Más preguntas</a></li>
            </ul>
          </div>
        </div>
      </div>

      <?php if ($similares): ?>
        <div class="size-title" style="margin-top:40px"><span class="size-badge" aria-hidden="true"><?= e($p['talla']) ?></span><h2 style="font-size:clamp(1.6rem,4vw,2.2rem)">Más en talla <?= e($tallaP['nombre']) ?></h2></div>
        <div class="grid">
          <?php $nivelTitulo = 3; foreach ($similares as $p) { require APP_DIR . '/vistas/tarjeta.php'; } ?>
        </div>
        <div class="size-more"><a class="btn btn-dark" href="/talla/<?= e($tallaP['slug']) ?>#catalogo">Ver toda la talla <?= e($tallaP['nombre']) ?> →</a></div>
      <?php endif; ?>
    </div>
  </section>
<?php require APP_DIR . '/vistas/pie.php';
