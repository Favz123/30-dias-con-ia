<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../../app/bootstrap.php') ? __DIR__ . '/../../app/bootstrap.php' : __DIR__ . '/../app/bootstrap.php';
requiere_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('/admin/');
}
verificar_csrf();

$id = (int) ($_POST['id'] ?? 0);
$accion = (string) ($_POST['accion'] ?? '');
$volver = (string) ($_POST['volver'] ?? '/admin/');
if (!preg_match('#^/admin/[^\s]*$#', $volver) || str_starts_with($volver, '//')) {
    $volver = '/admin/';
}

/* ---------- Unir productos repetidos en uno solo ---------- */
if ($accion === 'unir') {
    $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) ($_POST['ids'] ?? ''))))));
    $pdo = db();
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $filas = $ids ? $pdo->prepare("SELECT * FROM productos WHERE id IN ($marcas) ORDER BY id") : null;
    $filas?->execute($ids);
    $filas = $filas ? array_map('normaliza_producto', $filas->fetchAll()) : [];
    $nombres = array_unique(array_map(fn ($f) => mb_strtolower(trim(preg_replace('/\s+/', ' ', $f['nombre']))), $filas));
    $categorias = array_unique(array_column($filas, 'categoria'));
    $disponibles = array_filter($filas, fn ($f) => $f['estado'] === 'disponible');
    if (count($filas) < 2 || count($filas) !== count($ids) || count($nombres) !== 1 || count($categorias) !== 1 || count($disponibles) !== count($filas)) {
        aviso('No se pudieron unir: revisa que sean el mismo producto y que estén disponibles.', 'error');
        redirigir($volver);
    }
    // Se queda el más antiguo (conserva su código, su foto y su lugar); los demás se borran.
    $queda = $filas[0];
    $total = min(CANTIDAD_MAXIMA, array_sum(array_map(fn ($f) => max(1, $f['cantidad']), $filas)));
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE productos SET cantidad = :c, actualizado_en = :a WHERE id = :id')
        ->execute([':c' => $total, ':a' => ahora(), ':id' => $queda['id']]);
    $borrar = $pdo->prepare('DELETE FROM productos WHERE id = :id');
    foreach (array_slice($filas, 1) as $f) {
        $borrar->execute([':id' => $f['id']]);
    }
    $pdo->commit();
    foreach (array_slice($filas, 1) as $f) {
        if ($f['foto'] !== $queda['foto']) {
            borrar_foto($f['foto']);
        }
    }
    $codigos = implode(', ', array_column(array_slice($filas, 1), 'codigo'));
    aviso("Listo: {$queda['codigo']} ahora tiene $total piezas. Se quitaron $codigos.");
    redirigir($volver);
}

$st = db()->prepare('SELECT * FROM productos WHERE id = :id');
$st->execute([':id' => $id]);
$p = $st->fetch();
$p = $p ? normaliza_producto($p) : null;
if (!$p) {
    aviso('No se encontró el producto.', 'error');
    redirigir($volver);
}

$ahora = ahora();
switch ($accion) {
    case 'disponible':
        $sql = "UPDATE productos SET estado = 'disponible', apartado_en = NULL, vendido_en = NULL, actualizado_en = :a WHERE id = :id";
        $texto = "{$p['codigo']} está disponible.";
        break;
    case 'apartado':
        // Si ya estaba apartado, se conserva la fecha original del apartado.
        $sql = $p['estado'] === 'apartado'
            ? 'UPDATE productos SET actualizado_en = :a WHERE id = :id'
            : "UPDATE productos SET estado = 'apartado', apartado_en = :a2, vendido_en = NULL, actualizado_en = :a WHERE id = :id";
        $texto = "{$p['codigo']} quedó apartado. Los botones de Comprar y Apartar se desactivaron.";
        break;
    case 'vendido':
        $sql = $p['estado'] === 'vendido'
            ? 'UPDATE productos SET actualizado_en = :a WHERE id = :id'
            : "UPDATE productos SET estado = 'vendido', vendido_en = :a2, actualizado_en = :a WHERE id = :id";
        $texto = "{$p['codigo']} vendido 🎉 Se verá como “Vendido” " . (int) cfg('dias_vendido', 3) . ' días.';
        break;
    case 'vender_uno':
        // Se vendió una pieza: si quedan más, sólo baja la cantidad; si era la última, queda agotado.
        if ($p['cantidad'] > 1 && $p['estado'] !== 'vendido') {
            $sql = 'UPDATE productos SET cantidad = cantidad - 1, actualizado_en = :a WHERE id = :id AND cantidad > 1';
            $texto = "{$p['codigo']}: se vendió 1. Quedan " . ($p['cantidad'] - 1) . '.';
        } else {
            $sql = $p['estado'] === 'vendido'
                ? 'UPDATE productos SET actualizado_en = :a WHERE id = :id'
                : "UPDATE productos SET estado = 'vendido', vendido_en = :a2, actualizado_en = :a WHERE id = :id";
            $texto = "{$p['codigo']}: se vendió la última pieza 🎉 Se verá como “Agotado” " . (int) cfg('dias_vendido', 3) . ' días.';
        }
        break;
    case 'sumar_uno':
        // Llegó una pieza más. Si estaba agotado, vuelve a estar disponible con 1.
        if ($p['estado'] === 'vendido') {
            $sql = "UPDATE productos SET estado = 'disponible', cantidad = 1, vendido_en = NULL, actualizado_en = :a WHERE id = :id";
            $texto = "{$p['codigo']} está disponible otra vez (1 pieza).";
        } else {
            $sql = 'UPDATE productos SET cantidad = cantidad + 1, actualizado_en = :a WHERE id = :id AND cantidad < ' . CANTIDAD_MAXIMA;
            $texto = "{$p['codigo']}: ahora hay " . min(CANTIDAD_MAXIMA, $p['cantidad'] + 1) . ' piezas.';
        }
        break;
    case 'ocultar':
        // Vendido, pero se quita del catálogo de inmediato.
        $sql = "UPDATE productos SET estado = 'vendido', vendido_en = :pasado, actualizado_en = :a WHERE id = :id";
        $texto = "{$p['codigo']} se quitó del catálogo.";
        break;
    case 'borrar':
        db()->prepare('DELETE FROM productos WHERE id = :id')->execute([':id' => $id]);
        borrar_foto($p['foto']);
        aviso("{$p['codigo']} se borró.");
        redirigir($volver);
        // no break: redirigir() termina el script
    default:
        aviso('Acción no válida.', 'error');
        redirigir($volver);
}

// Cada parámetro se usa una sola vez por consulta (requisito de MySQL).
$params = [':a' => $ahora, ':id' => $id];
if (str_contains($sql, ':a2')) {
    $params[':a2'] = $ahora;
}
if (str_contains($sql, ':pasado')) {
    $params[':pasado'] = '2000-01-01 00:00:00';
}
db()->prepare($sql)->execute($params);
aviso($texto);
redirigir($volver);
