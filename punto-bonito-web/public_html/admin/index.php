<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../../app/bootstrap.php') ? __DIR__ . '/../../app/bootstrap.php' : __DIR__ . '/../app/bootstrap.php';
requiere_login();

$estado = (string) ($_GET['estado'] ?? '');
$estado = isset(ESTADOS[$estado]) ? $estado : '';
$categoria  = (string) ($_GET['categoria'] ?? '');
$categoria  = isset(CATEGORIAS[$categoria]) ? $categoria : '';
$q      = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 60));
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 30;

/* ---------- Resumen ---------- */
$diasApartado = (int) cfg('dias_apartado', 7);
$limiteApartado = date('Y-m-d H:i:s', time() - $diasApartado * 86400);
$st = db()->prepare("SELECT
    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) AS disponibles,
    SUM(CASE WHEN estado = 'disponible' THEN cantidad ELSE 0 END) AS piezas,
    SUM(CASE WHEN estado = 'apartado' THEN 1 ELSE 0 END) AS apartados,
    SUM(CASE WHEN estado = 'apartado' AND apartado_en < :lim_ap THEN 1 ELSE 0 END) AS vencidos,
    SUM(CASE WHEN estado = 'vendido' AND vendido_en >= :semana THEN 1 ELSE 0 END) AS vendidas_semana
    FROM productos");
$st->execute([':lim_ap' => $limiteApartado, ':semana' => date('Y-m-d H:i:s', time() - 7 * 86400)]);
$resumen = array_map('intval', $st->fetch() ?: []);

/* ---------- Lista ---------- */
$where = ['1=1'];
$params = [];
if ($estado !== '') {
    $where[] = 'estado = :estado';
    $params[':estado'] = $estado;
}
if ($categoria !== '') {
    $where[] = 'categoria = :categoria';
    $params[':categoria'] = $categoria;
}
if ($q !== '') {
    $where[] = "(nombre LIKE :q1 ESCAPE '!' OR color LIKE :q2 ESCAPE '!' OR codigo LIKE :q3 ESCAPE '!')";
    $params[':q1'] = $params[':q2'] = $params[':q3'] = like($q);
}
$condicion = implode(' AND ', $where);
$st = db()->prepare("SELECT COUNT(*) FROM productos WHERE $condicion");
$st->execute($params);
$total = (int) $st->fetchColumn();
$paginas = max(1, (int) ceil($total / $porPagina));

// Apartados: los más antiguos primero (los que están por vencer).
$orden = $estado === 'apartado' ? 'apartado_en ASC' : ($estado === 'vendido' ? 'vendido_en DESC' : 'creado_en DESC, id DESC');
$offset = ($pagina - 1) * $porPagina;
$st = db()->prepare("SELECT * FROM productos WHERE $condicion ORDER BY $orden LIMIT $porPagina OFFSET $offset");
$st->execute($params);
$productos = array_map('normaliza_producto', $st->fetchAll());

function enlace_admin(array $cambios): string
{
    global $estado, $categoria, $q;
    $p = array_merge(['estado' => $estado, 'categoria' => $categoria, 'q' => $q], $cambios);
    $p = array_filter($p, fn ($v) => $v !== '' && $v !== null && $v !== 1);
    return '/admin/' . ($p ? '?' . http_build_query($p) : '');
}
$aqui = $_SERVER['REQUEST_URI'] ?? '/admin/';
$repetidos = productos_repetidos();

$titulo = 'Mis productos';
require APP_DIR . '/vistas/admin-encabezado.php';
?>
<?php if (!whatsapp_configurado()): ?>
  <p class="aviso-wa">⚠️ Falta el número de WhatsApp de la tienda: los botones de la tienda todavía no llegan a nadie. Se configura en <b>app/config.php</b> (<code>whatsapp</code>).</p>
<?php endif; ?>
<a class="bulk-link" href="/admin/carga-masiva.php">📚 Subir varios productos a la vez</a>

<section class="stats" aria-label="Resumen">
  <a class="stat" href="<?= e(enlace_admin(['estado' => 'disponible', 'pagina' => 1])) ?>"><b><?= $resumen['disponibles'] ?? 0 ?></b><span>Disponibles<?php if (($resumen['piezas'] ?? 0) > ($resumen['disponibles'] ?? 0)): ?> · <?= $resumen['piezas'] ?> piezas<?php endif; ?></span></a>
  <a class="stat amber" href="<?= e(enlace_admin(['estado' => 'apartado', 'pagina' => 1])) ?>"><b><?= $resumen['apartados'] ?? 0 ?></b><span>Apartados<?php if (!empty($resumen['vencidos'])): ?> · <em><?= $resumen['vencidos'] ?> vencidos</em><?php endif; ?></span></a>
  <a class="stat dark" href="<?= e(enlace_admin(['estado' => 'vendido', 'pagina' => 1])) ?>"><b><?= $resumen['vendidas_semana'] ?? 0 ?></b><span>Vendidos (7 días)</span></a>
</section>

<?php if ($repetidos): ?>
  <section class="panel repetidos">
    <h2>🔁 Posibles repetidos</h2>
    <p class="hint">Estos productos tienen el mismo nombre, categoría y precio. Si son la misma pieza, únelos: queda <b>una sola foto</b> con la cantidad sumada y se borran las demás fichas.</p>
    <ul>
      <?php foreach ($repetidos as $g): ?>
        <li>
          <span><strong><?= e($g['nombre']) ?></strong> · <?= count($g['ids']) ?> fichas · <?= $g['piezas'] ?> piezas en total</span>
          <form method="post" action="/admin/accion.php" data-confirm="¿Unir las <?= count($g['ids']) ?> fichas de “<?= e($g['nombre']) ?>” en una sola con <?= $g['piezas'] ?> piezas? Se conserva la más antigua y se borran las otras.">
            <?= csrf_campo() ?>
            <input type="hidden" name="ids" value="<?= e(implode(',', $g['ids'])) ?>">
            <input type="hidden" name="volver" value="<?= e($aqui) ?>">
            <button class="btn primary" name="accion" value="unir">Unir en 1 con <?= $g['piezas'] ?> piezas</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

<form class="filters" method="get" action="/admin/">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar código, nombre o color" aria-label="Buscar">
  <select name="categoria" aria-label="Categoría" data-autosubmit>
    <option value="">Todas las categorías</option>
    <?php foreach (CATEGORIAS as $clave => $t): ?>
      <option value="<?= e($clave) ?>"<?= $categoria === $clave ? ' selected' : '' ?>><?= e($t['icono'] . ' ' . $t['nombre']) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if ($estado !== ''): ?><input type="hidden" name="estado" value="<?= e($estado) ?>"><?php endif; ?>
  <button class="btn" type="submit">Buscar</button>
</form>

<nav class="tabs" aria-label="Estado">
  <a href="<?= e(enlace_admin(['estado' => '', 'pagina' => 1])) ?>"<?= $estado === '' ? ' aria-current="page"' : '' ?>>Todos</a>
  <?php foreach (ESTADOS as $clave => $nombre): ?>
    <a href="<?= e(enlace_admin(['estado' => $clave, 'pagina' => 1])) ?>"<?= $estado === $clave ? ' aria-current="page"' : '' ?>><?= e($nombre) ?>s</a>
  <?php endforeach; ?>
</nav>

<p class="count"><?= $total === 1 ? '1 producto' : "$total productos" ?><?php if ($q !== '' || $categoria !== '' || $estado !== ''): ?> · <a href="/admin/">Quitar filtros</a><?php endif; ?></p>

<?php if (!$productos): ?>
  <div class="empty">
    <?php if ($total === 0 && $q === '' && $categoria === '' && $estado === ''): ?>
      <strong>Aún no hay productos</strong>
      <p>Toca el botón <b>+</b> para subir la primera.</p>
    <?php else: ?>
      <strong>Sin resultados</strong>
      <p>Prueba con otra búsqueda.</p>
    <?php endif; ?>
  </div>
<?php endif; ?>

<ul class="items">
  <?php foreach ($productos as $p): ?>
    <?php
      $vencido = $p['estado'] === 'apartado' && $p['apartado_en'] && $p['apartado_en'] < $limiteApartado;
      $oculta = $p['estado'] === 'vendido' && !producto_visible($p);
    ?>
    <li class="item estado-<?= e($p['estado']) ?><?= $vencido ? ' vencido' : '' ?>">
      <a class="item-main" href="/admin/editar.php?id=<?= $p['id'] ?>&amp;volver=<?= e(rawurlencode($aqui)) ?>">
        <img src="<?= e(foto_url($p, true)) ?>" width="72" height="72" alt="" loading="lazy">
        <span class="item-text">
          <span class="item-code"><?= e($p['codigo']) ?> · <?= e(CATEGORIAS[$p['categoria']]['icono'] . ' ' . CATEGORIAS[$p['categoria']]['nombre']) ?></span>
          <strong><?= e($p['nombre']) ?></strong>
          <span class="item-meta">
            <?= $p['precio'] !== null ? e(precio($p['precio'])) : 'Sin precio' ?><?= $p['color'] !== '' ? ' · ' . e($p['color']) : '' ?>
          </span>
          <?php if ($p['estado'] === 'apartado' && $p['apartado_en']): ?>
            <span class="item-note<?= $vencido ? ' bad' : '' ?>">Apartado <?= e(hace($p['apartado_en'])) ?><?= $vencido ? ' · ¡venció el plazo!' : '' ?></span>
          <?php elseif ($p['estado'] === 'vendido' && $p['vendido_en']): ?>
            <span class="item-note">Vendido <?= e(hace($p['vendido_en'])) ?> · <?= $oculta ? 'ya no se muestra' : 'visible como “Vendido”' ?></span>
          <?php endif; ?>
        </span>
      </a>
      <form class="states" method="post" action="/admin/accion.php">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <input type="hidden" name="volver" value="<?= e($aqui) ?>">
        <button name="accion" value="disponible" class="st st-disp"<?= $p['estado'] === 'disponible' ? ' aria-pressed="true"' : '' ?>>Disponible</button>
        <button name="accion" value="apartado" class="st st-apar"<?= $p['estado'] === 'apartado' ? ' aria-pressed="true"' : '' ?>>Apartado</button>
        <button name="accion" value="vendido" class="st st-vend"<?= $p['estado'] === 'vendido' ? ' aria-pressed="true"' : '' ?>>Vendido</button>
      </form>
      <form class="qty-row" method="post" action="/admin/accion.php">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <input type="hidden" name="volver" value="<?= e($aqui) ?>">
        <button class="btn" name="accion" value="vender_uno"<?= $p['estado'] === 'vendido' ? ' disabled' : '' ?>>− Vendí 1</button>
        <span><?= $p['estado'] === 'vendido' ? 'Agotado' : e(texto_existencias($p)) ?></span>
        <button class="btn" name="accion" value="sumar_uno">+ Llegó 1</button>
      </form>
    </li>
  <?php endforeach; ?>
</ul>

<?php if ($paginas > 1): ?>
  <nav class="pager">
    <?php if ($pagina > 1): ?><a class="btn" href="<?= e(enlace_admin(['pagina' => $pagina - 1])) ?>">← Anterior</a><?php endif; ?>
    <span><?= $pagina ?> / <?= $paginas ?></span>
    <?php if ($pagina < $paginas): ?><a class="btn" href="<?= e(enlace_admin(['pagina' => $pagina + 1])) ?>">Siguiente →</a><?php endif; ?>
  </nav>
<?php endif; ?>

<form class="logout" method="post" action="/admin/salir.php">
  <?= csrf_campo() ?>
  <span>Sesión: <?= e(usuario_actual()['usuario']) ?></span>
  <a href="/admin/cuenta.php">Mi cuenta y contraseña</a>
  <button class="link" type="submit">Cerrar sesión</button>
</form>

<a class="fab" href="/admin/editar.php" aria-label="Subir producto nuevo">＋</a>
<?php require APP_DIR . '/vistas/admin-pie.php';
