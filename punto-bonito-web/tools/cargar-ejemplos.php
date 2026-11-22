<?php
// Carga productos de PRUEBA para ver cómo luce el catálogo mientras llegan las fotos reales.
// Usa las fotos de ropa del catálogo de En Busca de Closet sólo como relleno.
//   php tools/cargar-ejemplos.php
// Para quitarlos después: borra app/datos/ (en local) o bórralos desde el panel.
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

$fotos = __DIR__ . '/../../en-busca-de-closet/img';
// [nombre, color, categoría, precio, detalle, descripción, foto de prueba]
$productos = [
    ['Bolso de mano rosa con negro', 'Rosa', 'BOL', 350, 'Con cierre', 'Foto de prueba. Aquí va la descripción del bolso: material, medidas y compartimentos.', 'top-nike-pro-rosa-ch.webp'],
    ['Bolso tote negro', 'Negro', 'BOL', 420, '', 'Foto de prueba. Bolso amplio para el día a día.', 'biker-nike-negro-ch.webp'],
    ['Bolso cruzado lila', 'Lila', 'BOL', 380, 'Pieza única', 'Foto de prueba.', 'top-nike-lila-m.webp'],
    ['Bolso de playa coral', 'Naranja', 'BOL', 290, '', 'Foto de prueba.', 'short-nike-negro-coral-g.webp'],
    ['Bolso tejido multicolor', 'Multicolor', 'BOL', null, 'Hecho a mano', 'Foto de prueba. Sin precio: el botón pide el precio por WhatsApp.', 'short-nike-negro-rosa-g.webp'],
    ['Monedero rosa con cierre', 'Rosa', 'MON', 120, 'Con cierre', 'Foto de prueba.', 'short-nike-negro-rosa-g.webp'],
    ['Monedero negro clásico', 'Negro', 'MON', 110, '', 'Foto de prueba.', 'short-adidas-negro-ch.webp'],
    ['Cosmetiquera lila', 'Lila', 'COS', 160, '', 'Foto de prueba.', 'top-nike-lila-m.webp'],
    ['Taza floral', 'Floral', 'TAZ', 140, 'Personalizable', 'Foto de prueba.', 'top-nike-pro-rosa-ch.webp'],
    ['Termo negro 500 ml', 'Negro', 'TER', 260, 'Térmico', 'Foto de prueba.', 'polo-nike-negra-xg.webp'],
    ['Aretes coral', 'Naranja', 'ARE', 90, 'Edición limitada', 'Foto de prueba.', 'short-nike-negro-coral-g.webp'],
    ['Mandil negro de cocina', 'Negro', 'MAN', 180, '', 'Foto de prueba.', 'biker-nike-negro-ch.webp'],
];
$pdo = db();
foreach ($productos as $i => [$nombre, $color, $categoria, $precio, $detalle, $desc, $foto]) {
    $fecha = date('Y-m-d H:i:s', time() - (count($productos) - $i) * 60);
    $pdo->prepare("INSERT INTO productos (nombre, color, categoria, precio, detalle, descripcion, foto, estado, creado_en, actualizado_en)
        VALUES (:n, :m, :t, :p, :d, :desc, '', 'disponible', :c, :a)")
        ->execute([':n' => $nombre, ':m' => $color, ':t' => $categoria, ':p' => $precio, ':d' => $detalle, ':desc' => $desc, ':c' => $fecha, ':a' => $fecha]);
    $id = (int) $pdo->lastInsertId();
    $codigo = sprintf('%s-%04d', $categoria, $id);
    $archivo = guardar_foto("$fotos/$foto", $codigo, $nombre);
    $pdo->prepare('UPDATE productos SET codigo = :c, foto = :f WHERE id = :id')->execute([':c' => $codigo, ':f' => $archivo, ':id' => $id]);
    echo "$codigo  $nombre\n";
}
// Un apartado y un vendido, para ver cómo se ven.
$pdo->exec("UPDATE productos SET estado = 'apartado', apartado_en = '" . ahora() . "' WHERE codigo = 'BOL-0002'");
$pdo->exec("UPDATE productos SET estado = 'vendido', vendido_en = '" . ahora() . "' WHERE codigo = 'MON-0007'");
echo "Listo. Son productos de PRUEBA con fotos de ropa; los precios son de ejemplo.\n";
