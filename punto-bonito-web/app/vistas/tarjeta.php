<?php
/**
 * Tarjeta de un producto. Variables: $p (fila normalizada de "productos"),
 * $nivelTitulo (opcional, 2–4: nivel del título según la sección donde va; 3 por omisión).
 */
$categoriaP = CATEGORIAS[$p['categoria']];
$alt = texto_alt($p);
$h = 'h' . max(2, min(4, (int) ($nivelTitulo ?? 3)));
?>
<article class="card reveal c-<?= e($categoriaP['color']) ?> estado-<?= e($p['estado']) ?>">
  <a class="card-media" href="<?= e(producto_url($p)) ?>" tabindex="-1" aria-hidden="true">
    <img src="<?= e(foto_url($p, true)) ?>" width="600" height="600" loading="lazy" decoding="async" alt="<?= e($alt) ?>">
    <span class="tag"><?= e($categoriaP['icono'] . ' ' . $categoriaP['singular']) ?></span>
    <?php if ($p['color'] !== ''): ?><span class="tag size"><?= e($p['color']) ?></span><?php endif; ?>
    <?php if (es_nuevo($p)): ?><span class="tag nuevo">✨ Nuevo</span><?php endif; ?>
    <?php if ($p['estado'] !== 'disponible'): ?><span class="ribbon"><?= e(ESTADOS[$p['estado']]) ?></span><?php endif; ?>
  </a>
  <div class="card-body">
    <<?= $h ?> class="card-title"><a href="<?= e(producto_url($p)) ?>"><?= e($p['nombre']) ?></a></<?= $h ?>>
    <div class="meta-row">
      <?php if ($p['precio'] !== null): ?>
        <span class="price"><?= e(precio($p['precio'])) ?></span>
      <?php else: ?>
        <span class="price ask">Precio por WhatsApp</span>
      <?php endif; ?>
      <span class="code"><?= e($p['codigo']) ?></span>
    </div>
    <?php if ($p['detalle'] !== ''): ?><span class="feature"><?= e($p['detalle']) ?></span><?php endif; ?>
    <?php require APP_DIR . '/vistas/botones.php'; ?>
  </div>
</article>
