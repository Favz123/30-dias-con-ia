<?php
/**
 * Botones de WhatsApp de un producto: Comprar, Apartar y Más información.
 * Cada uno abre WhatsApp con un mensaje ya escrito que incluye nombre, código, precio y enlace.
 * Variable: $p (producto).
 */
?>
<?php if ($p['estado'] === 'disponible'): ?>
  <div class="acciones">
    <a class="btn btn-wa" href="<?= e(whatsapp_link(mensaje_producto($p, 'comprar'))) ?>" target="_blank" rel="noopener">
      <svg class="ico" aria-hidden="true"><use href="#i-wa"/></svg> Comprar
    </a>
    <a class="btn btn-apartar" href="<?= e(whatsapp_link(mensaje_producto($p, 'apartar'))) ?>" target="_blank" rel="noopener">💖 Apartar</a>
    <a class="btn btn-info" href="<?= e(whatsapp_link(mensaje_producto($p, 'info'))) ?>" target="_blank" rel="noopener">💬 Más info</a>
  </div>
<?php elseif ($p['estado'] === 'apartado'): ?>
  <div class="acciones">
    <span class="btn btn-off" aria-disabled="true" style="grid-column:1/-1">Apartado por alguien más</span>
    <a class="btn btn-info" style="grid-column:1/-1" href="<?= e(whatsapp_link(mensaje_producto($p, 'info'))) ?>" target="_blank" rel="noopener">💬 ¿Tienen otro igual?</a>
  </div>
<?php else: ?>
  <span class="btn btn-off" aria-disabled="true">¡Ya se vendió!</span>
<?php endif; ?>
