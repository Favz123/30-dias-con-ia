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

$st = db()->prepare('SELECT * FROM productos WHERE id = :id');
$st->execute([':id' => $id]);
$p = $st->fetch();
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
