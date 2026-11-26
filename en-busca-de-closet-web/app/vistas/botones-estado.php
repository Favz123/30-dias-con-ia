<?php
/**
 * Botones de estado de una prenda en el panel. Variables: $p (prenda normalizada),
 * $volverA (ruta /admin/... a la que se regresa), $ancho (bool, opcional: versión de la ficha).
 * Con varias piezas, "Vendí 1" y "Apartaron 1" descuentan una pieza; "+1" suma una.
 */
$varias = $p['estado'] === 'disponible' && $p['cantidad'] > 1;
?>
<form class="states<?= !empty($ancho) ? ' wide' : '' ?>" method="post" action="/admin/accion.php">
  <?= csrf_campo() ?>
  <input type="hidden" name="id" value="<?= $p['id'] ?>">
  <input type="hidden" name="volver" value="<?= e($volverA) ?>">
  <button name="accion" value="disponible" class="st st-disp"<?= $p['estado'] === 'disponible' ? ' aria-pressed="true"' : '' ?>><?= $varias ? e(texto_piezas($p['cantidad'])) : 'Disponible' ?></button>
  <button name="accion" value="apartado" class="st st-apar"<?= $p['estado'] === 'apartado' ? ' aria-pressed="true"' : '' ?>><?= $varias ? 'Apartaron 1' : 'Apartada' ?></button>
  <button name="accion" value="vendido" class="st st-vend"<?= $p['estado'] === 'vendido' ? ' aria-pressed="true"' : '' ?>><?= $varias ? 'Vendí 1' : 'Vendida' ?></button>
  <button name="accion" value="sumar" class="st st-mas" aria-label="Llegó otra pieza igual: sumar 1" title="Llegó otra pieza igual">+1</button>
</form>
