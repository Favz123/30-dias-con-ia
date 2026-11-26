<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../../app/bootstrap.php') ? __DIR__ . '/../../app/bootstrap.php' : __DIR__ . '/../app/bootstrap.php';
requiere_login();

$titulo = 'Subir varios productos';
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
      <p>Si el archivo se llama, por ejemplo, <b>Bolso tejido rosa - 350.jpg</b>, se llenan solos la categoría (por la primera palabra: bolso, taza, termo…), el color, el nombre y el precio. También sirve la clave al inicio: <b>BOL - Bolso tejido rosa - 350.jpg</b>. Si tienes varias iguales, agrega <b>x3</b> (o <b>3pz</b>): <b>Taza corazón - 120 x3.jpg</b>.</p>
      <p><b>Varias piezas iguales = una sola foto.</b> Sube una foto y pon cuántas piezas hay en “Piezas”.</p>
      <p>Desde <b>Google Drive</b>: en la computadora, descarga la carpeta (se baja como ZIP), descomprímela y aquí selecciona todas las fotos con Ctrl+A. En el celular, al elegir fotos toca “Explorar” o “Archivos” y entra a Drive.</p>
    </details>
  </section>

  <section class="panel" data-paso="todas" hidden>
    <h2>Aplicar a todas las fotos</h2>
    <p class="hint">Llena sólo lo que se repite y toca “Aplicar”. No borra lo que ya escribiste en cada foto.</p>
    <fieldset class="sizes">
      <legend>Categoría</legend>
      <?php foreach (CATEGORIAS as $clave => $t): ?>
        <label><input type="radio" name="todas_categoria" value="<?= e($clave) ?>"><span><b><?= e($t['icono']) ?></b><?= e($t['nombre']) ?></span></label>
      <?php endforeach; ?>
    </fieldset>
    <div class="two">
      <label>Color <input data-todas="color" list="colores" placeholder="Rosa"></label>
      <label>Precio ($) <input data-todas="precio" inputmode="numeric" placeholder="250"></label>
    </div>
    <label>Piezas de cada una <input data-todas="cantidad" type="number" min="1" max="<?= CANTIDAD_MAXIMA ?>" inputmode="numeric" placeholder="1"></label>
    <label>Detalle <input data-todas="detalle" list="detalles" placeholder="Ej. Hecho a mano"></label>
    <button class="btn block" type="button" data-aplicar>Aplicar a las que estén vacías</button>
  </section>

  <ul class="lote" data-lote></ul>

  <div class="barra" data-barra hidden>
    <div class="progreso"><span data-progreso></span></div>
    <p data-resumen class="hint"></p>
    <button class="btn primary block" type="button" data-subir>Subir productos</button>
  </div>

  <datalist id="colores"><?php foreach (colores_sugeridos() as $m): ?><option value="<?= e($m) ?>"><?php endforeach; ?></datalist>
  <datalist id="detalles"><option value="Hecho a mano"><option value="Pieza única"><option value="Personalizable"><option value="Con cierre"><option value="Térmico"><option value="Incluye caja de regalo"><option value="Edición limitada"></datalist>

  <template data-plantilla>
    <li class="lote-item">
      <img alt="" data-mini>
      <div class="lote-campos">
        <div class="lote-cats" role="radiogroup" aria-label="Categoría">
          <?php foreach (CATEGORIAS as $clave => $t): ?><button type="button" data-categoria="<?= e($clave) ?>" title="<?= e($t['nombre']) ?>"><?= e($t['icono']) ?><small><?= e($t['nombre']) ?></small></button><?php endforeach; ?>
        </div>
        <input data-campo="nombre" placeholder="Nombre del producto" maxlength="120" aria-label="Nombre">
        <div class="two">
          <input data-campo="color" list="colores" placeholder="Color" maxlength="60" aria-label="Color">
          <input data-campo="precio" inputmode="numeric" placeholder="Precio" aria-label="Precio">
        </div>
        <label class="lote-piezas">Piezas <input data-campo="cantidad" type="number" min="1" max="<?= CANTIDAD_MAXIMA ?>" inputmode="numeric" placeholder="1" aria-label="Cantidad de piezas"></label>
        <input data-campo="detalle" list="detalles" placeholder="Detalle (opcional)" maxlength="60" aria-label="Detalle">
        <p class="lote-estado" data-estado></p>
      </div>
      <button type="button" class="lote-quitar" data-quitar aria-label="Quitar esta foto">✕</button>
    </li>
  </template>
</div>
<?php
// Palabras con las que se reconoce cada categoría en el nombre del archivo: "BOL", "bolso", "bolsos"…
$palabras = [];
foreach (CATEGORIAS as $clave => $t) {
    $palabras[$clave] = array_values(array_unique([slug($t['slug']), slug($t['singular'])]));
}
?>
<script>window.PB_COLORES = <?= json_encode(colores_sugeridos(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
window.PB_CATEGORIAS = <?= json_encode($palabras, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
<script src="/admin/carga-masiva.js?v=2" defer></script>
<?php require APP_DIR . '/vistas/admin-pie.php';
