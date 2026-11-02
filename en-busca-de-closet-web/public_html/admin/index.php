<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../../app/bootstrap.php') ? __DIR__ . '/../../app/bootstrap.php' : __DIR__ . '/../app/bootstrap.php';
requiere_login();

$estado = (string) ($_GET['estado'] ?? '');
$estado = isset(ESTADOS[$estado]) ? $estado : '';
$talla  = (string) ($_GET['talla'] ?? '');
$talla  = isset(TALLAS[$talla]) ? $talla : '';
$q      = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 60));
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 30;

/* ---------- Resumen ---------- */
$diasApartado = (int) cfg('dias_apartado', 7);
$limiteApartado = date('Y-m-d H:i:s', time() - $diasApartado * 86400);
$st = db()->prepare("SELECT
    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) AS disponibles,
    SUM(CASE WHEN estado = 'apartado' THEN 1 ELSE 0 END) AS apartados,
    SUM(CASE WHEN estado = 'apartado' AND apartado_en < :lim_ap THEN 1 ELSE 0 END) AS vencidos,
    SUM(CASE WHEN estado = 'vendido' AND vendido_en >= :semana THEN 1 ELSE 0 END) AS vendidas_semana
    FROM prendas");
$st->execute([':lim_ap' => $limiteApartado, ':semana' => date('Y-m-d H:i:s', time() - 7 * 86400)]);
$resumen = array_map('intval', $st->fetch() ?: []);

/* ---------- Lista ---------- */
$where = ['1=1'];
$params = [];
if ($estado !== '') {
    $where[] = 'estado = :estado';
    $params[':estado'] = $estado;
}
if ($talla !== '') {
    $where[] = 'talla = :talla';
    $params[':talla'] = $talla;
}
if ($q !== '') {
    $where[] = "(nombre LIKE :q1 ESCAPE '\\' OR marca LIKE :q2 ESCAPE '\\' OR codigo LIKE :q3 ESCAPE '\\')";
    $params[':q1'] = $params[':q2'] = $params[':q3'] = like($q);
}
$condicion = implode(' AND ', $where);
$st = db()->prepare("SELECT COUNT(*) FROM prendas WHERE $condicion");
$st->execute($params);
$total = (int) $st->fetchColumn();
$paginas = max(1, (int) ceil($total / $porPagina));

// Apartados: los más antiguos primero (los que están por vencer).
$orden = $estado === 'apartado' ? 'apartado_en ASC' : ($estado === 'vendido' ? 'vendido_en DESC' : 'creado_en DESC, id DESC');
$offset = ($pagina - 1) * $porPagina;
$st = db()->prepare("SELECT * FROM prendas WHERE $condicion ORDER BY $orden LIMIT $porPagina OFFSET $offset");
$st->execute($params);
$prendas = array_map('normaliza_prenda', $st->fetchAll());

function enlace_admin(array $cambios): string
{
    global $estado, $talla, $q;
    $p = array_merge(['estado' => $estado, 'talla' => $talla, 'q' => $q], $cambios);
    $p = array_filter($p, fn ($v) => $v !== '' && $v !== null && $v !== 1);
    return '/admin/' . ($p ? '?' . http_build_query($p) : '');
}
$aqui = $_SERVER['REQUEST_URI'] ?? '/admin/';

$titulo = 'Mis prendas';
require APP_DIR . '/vistas/admin-encabezado.php';
?>
<section class="stats" aria-label="Resumen">
  <a class="stat" href="<?= e(enlace_admin(['estado' => 'disponible', 'pagina' => 1])) ?>"><b><?= $resumen['disponibles'] ?? 0 ?></b><span>Disponibles</span></a>
  <a class="stat amber" href="<?= e(enlace_admin(['estado' => 'apartado', 'pagina' => 1])) ?>"><b><?= $resumen['apartados'] ?? 0 ?></b><span>Apartadas<?php if (!empty($resumen['vencidos'])): ?> · <em><?= $resumen['vencidos'] ?> vencidas</em><?php endif; ?></span></a>
  <a class="stat dark" href="<?= e(enlace_admin(['estado' => 'vendido', 'pagina' => 1])) ?>"><b><?= $resumen['vendidas_semana'] ?? 0 ?></b><span>Vendidas (7 días)</span></a>
</section>

<form class="filters" method="get" action="/admin/">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar código, nombre o marca" aria-label="Buscar">
  <select name="talla" aria-label="Talla" data-autosubmit>
    <option value="">Todas las tallas</option>
    <?php foreach (TALLAS as $clave => $t): ?>
      <option value="<?= e($clave) ?>"<?= $talla === $clave ? ' selected' : '' ?>><?= e($clave . ' · ' . $t['nombre']) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if ($estado !== ''): ?><input type="hidden" name="estado" value="<?= e($estado) ?>"><?php endif; ?>
  <button class="btn" type="submit">Buscar</button>
</form>

<nav class="tabs" aria-label="Estado">
  <a href="<?= e(enlace_admin(['estado' => '', 'pagina' => 1])) ?>"<?= $estado === '' ? ' aria-current="page"' : '' ?>>Todas</a>
  <?php foreach (ESTADOS as $clave => $nombre): ?>
    <a href="<?= e(enlace_admin(['estado' => $clave, 'pagina' => 1])) ?>"<?= $estado === $clave ? ' aria-current="page"' : '' ?>><?= e($nombre) ?>s</a>
  <?php endforeach; ?>
</nav>

<p class="count"><?= $total === 1 ? '1 prenda' : "$total prendas" ?><?php if ($q !== '' || $talla !== '' || $estado !== ''): ?> · <a href="/admin/">Quitar filtros</a><?php endif; ?></p>

<?php if (!$prendas): ?>
  <div class="empty">
    <?php if ($total === 0 && $q === '' && $talla === '' && $estado === ''): ?>
      <strong>Aún no hay prendas</strong>
      <p>Toca el botón <b>+</b> para subir la primera.</p>
    <?php else: ?>
      <strong>Sin resultados</strong>
      <p>Prueba con otra búsqueda.</p>
    <?php endif; ?>
  </div>
<?php endif; ?>

<ul class="items">
  <?php foreach ($prendas as $p): ?>
    <?php
      $vencido = $p['estado'] === 'apartado' && $p['apartado_en'] && $p['apartado_en'] < $limiteApartado;
      $oculta = $p['estado'] === 'vendido' && !prenda_visible($p);
    ?>
    <li class="item estado-<?= e($p['estado']) ?><?= $vencido ? ' vencido' : '' ?>">
      <a class="item-main" href="/admin/editar.php?id=<?= $p['id'] ?>&amp;volver=<?= e(rawurlencode($aqui)) ?>">
        <img src="<?= e(foto_url($p, true)) ?>" width="72" height="72" alt="" loading="lazy">
        <span class="item-text">
          <span class="item-code"><?= e($p['codigo']) ?> · <?= e($p['talla']) ?></span>
          <strong><?= e($p['nombre']) ?></strong>
          <span class="item-meta">
            <?= $p['precio'] !== null ? e(precio($p['precio'])) : 'Sin precio' ?><?= $p['marca'] !== '' ? ' · ' . e($p['marca']) : '' ?>
          </span>
          <?php if ($p['estado'] === 'apartado' && $p['apartado_en']): ?>
            <span class="item-note<?= $vencido ? ' bad' : '' ?>">Apartada <?= e(hace($p['apartado_en'])) ?><?= $vencido ? ' · ¡venció el plazo!' : '' ?></span>
          <?php elseif ($p['estado'] === 'vendido' && $p['vendido_en']): ?>
            <span class="item-note">Vendida <?= e(hace($p['vendido_en'])) ?> · <?= $oculta ? 'ya no se muestra' : 'visible como “Vendida”' ?></span>
          <?php endif; ?>
        </span>
      </a>
      <form class="states" method="post" action="/admin/accion.php">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <input type="hidden" name="volver" value="<?= e($aqui) ?>">
        <button name="accion" value="disponible" class="st st-disp"<?= $p['estado'] === 'disponible' ? ' aria-pressed="true"' : '' ?>>Disponible</button>
        <button name="accion" value="apartado" class="st st-apar"<?= $p['estado'] === 'apartado' ? ' aria-pressed="true"' : '' ?>>Apartada</button>
        <button name="accion" value="vendido" class="st st-vend"<?= $p['estado'] === 'vendido' ? ' aria-pressed="true"' : '' ?>>Vendida</button>
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
  <button class="link" type="submit">Cerrar sesión</button>
</form>

<a class="fab" href="/admin/editar.php" aria-label="Subir prenda nueva">＋</a>
<?php require APP_DIR . '/vistas/admin-pie.php';
