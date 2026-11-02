<?php
/** Tarjeta de una prenda. Variable esperada: $p (fila normalizada de "prendas"). */
$tallaP = TALLAS[$p['talla']];
$alt = trim($p['nombre'] . ' ' . $p['marca']) . ', talla ' . mb_strtolower($tallaP['nombre']);
?>
<article class="card reveal estado-<?= e($p['estado']) ?>">
  <a class="card-media" href="<?= e(prenda_url($p)) ?>" tabindex="-1" aria-hidden="true">
    <img src="<?= e(foto_url($p, true)) ?>" width="600" height="600" loading="lazy" decoding="async" alt="<?= e($alt) ?>">
    <?php if ($p['marca'] !== ''): ?><span class="tag"><?= e($p['marca']) ?></span><?php endif; ?>
    <span class="tag size"><?= e($p['talla']) ?></span>
    <?php if ($p['estado'] !== 'disponible'): ?><span class="ribbon"><?= e(ESTADOS[$p['estado']]) ?></span><?php endif; ?>
  </a>
  <div class="card-body">
    <h3><a href="<?= e(prenda_url($p)) ?>"><?= e($p['nombre']) ?></a></h3>
    <div class="meta-row">
      <?php if ($p['precio'] !== null): ?>
        <span class="price"><?= e(precio($p['precio'])) ?></span>
      <?php else: ?>
        <span class="price ask">Precio por WhatsApp</span>
      <?php endif; ?>
      <span class="code"><?= e($p['codigo']) ?></span>
    </div>
    <?php if ($p['detalle'] !== ''): ?><span class="feature"><?= e($p['detalle']) ?></span><?php endif; ?>
    <?php if ($p['estado'] === 'disponible'): ?>
      <a class="btn btn-wa" href="<?= e(whatsapp_link(mensaje_prenda($p))) ?>" target="_blank" rel="noopener">
        <svg class="ico" aria-hidden="true"><use href="#i-wa"/></svg> Me interesa
      </a>
    <?php elseif ($p['estado'] === 'apartado'): ?>
      <span class="btn btn-off" aria-disabled="true">Apartada por alguien más</span>
    <?php else: ?>
      <span class="btn btn-off" aria-disabled="true">¡Ya se vendió!</span>
    <?php endif; ?>
  </div>
</article>
