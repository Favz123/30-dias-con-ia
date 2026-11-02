<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../../app/bootstrap.php') ? __DIR__ . '/../../app/bootstrap.php' : __DIR__ . '/../app/bootstrap.php';
requiere_login();

$titulo = 'Subir varias prendas';
$volver = '/admin/';
require APP_DIR . '/vistas/admin-encabezado.php';
?>
<div id="carga" data-csrf="<?= e(csrf_token()) ?>">
  <section class="panel" data-paso="elegir">
    <label class="photo-pick multi">
      <span class="photo-cta">📷 Elegir fotos<br><small>Puedes seleccionar varias a la vez (máximo 40 por tanda)</small></span>
      <input type="file" accept="image/*" multiple data-fotos>
    </label>
    <details class="tips">
      <summary>💡 Tip: nombra las fotos y se llenan solas</summary>
      <p>Si el archivo se llama, por ejemplo, <b>CH - Short Nike negro - 250.jpg</b>, se llenan la talla (CH, M, G, XG), la marca, el nombre y el precio automáticamente.</p>
      <p>Desde <b>Google Drive</b>: en la computadora, descarga la carpeta (se baja como ZIP), descomprímela y aquí selecciona todas las fotos con Ctrl+A. En el celular, al elegir fotos toca “Explorar” o “Archivos” y entra a Drive.</p>
    </details>
  </section>

  <section class="panel" data-paso="todas" hidden>
    <h2>Aplicar a todas las fotos</h2>
    <p class="hint">Llena sólo lo que se repite y toca “Aplicar”. No borra lo que ya escribiste en cada foto.</p>
    <fieldset class="sizes">
      <legend>Talla</legend>
      <?php foreach (TALLAS as $clave => $t): ?>
        <label><input type="radio" name="todas_talla" value="<?= e($clave) ?>"><span><b><?= e($clave) ?></b><?= e($t['nombre']) ?></span></label>
      <?php endforeach; ?>
    </fieldset>
    <div class="two">
      <label>Marca <input data-todas="marca" list="marcas" placeholder="Nike"></label>
      <label>Precio ($) <input data-todas="precio" inputmode="numeric" placeholder="250"></label>
    </div>
    <label>Detalle <input data-todas="detalle" list="detalles" placeholder="Ej. Copas removibles"></label>
    <button class="btn block" type="button" data-aplicar>Aplicar a las que estén vacías</button>
  </section>

  <ul class="lote" data-lote></ul>

  <div class="barra" data-barra hidden>
    <div class="progreso"><span data-progreso></span></div>
    <p data-resumen class="hint"></p>
    <button class="btn primary block" type="button" data-subir>Subir prendas</button>
  </div>

  <datalist id="marcas"><?php foreach (marcas_sugeridas() as $m): ?><option value="<?= e($m) ?>"><?php endforeach; ?></datalist>
  <datalist id="detalles"><option value="Copas removibles"><option value="Con licra interior"><option value="Nueva con etiqueta"><option value="Como nueva"><option value="Con bolsillos"></datalist>

  <template data-plantilla>
    <li class="lote-item">
      <img alt="" data-mini>
      <div class="lote-campos">
        <div class="lote-sizes" role="radiogroup" aria-label="Talla">
          <?php foreach (TALLAS as $clave => $t): ?><button type="button" data-talla="<?= e($clave) ?>"><?= e($clave) ?></button><?php endforeach; ?>
        </div>
        <input data-campo="nombre" placeholder="Nombre de la prenda" maxlength="120" aria-label="Nombre">
        <div class="two">
          <input data-campo="marca" list="marcas" placeholder="Marca" maxlength="60" aria-label="Marca">
          <input data-campo="precio" inputmode="numeric" placeholder="Precio" aria-label="Precio">
        </div>
        <input data-campo="detalle" list="detalles" placeholder="Detalle (opcional)" maxlength="60" aria-label="Detalle">
        <p class="lote-estado" data-estado></p>
      </div>
      <button type="button" class="lote-quitar" data-quitar aria-label="Quitar esta foto">✕</button>
    </li>
  </template>
</div>
<script>window.CLOSET_MARCAS = <?= json_encode(marcas_sugeridas(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
<script src="/admin/carga-masiva.js?v=2" defer></script>
<?php require APP_DIR . '/vistas/admin-pie.php';
