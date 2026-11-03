<?php
// Carga las 7 prendas del catálogo original como ejemplo (útil para probar).
//   php tools/cargar-ejemplos.php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

$fotos = __DIR__ . '/../../en-busca-de-closet/img';
$prendas = [
    ['Biker short Nike negro', 'Nike', 'CH', 280, '', 'Corte largo a medio muslo, tela suave y elástica con logo bordado.', 'biker-nike-negro-ch.webp'],
    ['Top deportivo Nike Pro rosa con negro', 'Nike', 'CH', 250, 'Copas removibles', 'Franjas diagonales y espalda deportiva para entrenar cómoda.', 'top-nike-pro-rosa-ch.webp'],
    ['Short Adidas negro para correr', 'Adidas', 'CH', 220, '', 'Tela ligera con cintura elástica y paneles laterales transpirables.', 'short-adidas-negro-ch.webp'],
    ['Top deportivo Nike lila con morado', 'Nike', 'M', 250, 'Copas removibles', 'Tirantes delgados cruzados, perforaciones al centro y banda elástica.', 'top-nike-lila-m.webp'],
    ['Short Nike negro con rosa', 'Nike', 'G', 230, 'Con licra interior', 'Short doble capa para correr, con detalles en rosa fucsia.', 'short-nike-negro-rosa-g.webp'],
    ['Short Nike negro con coral', 'Nike', 'G', 210, '', 'Short para correr con paneles laterales en coral y rosa claro.', 'short-nike-negro-coral-g.webp'],
    ['Playera polo Nike negra', 'Nike', 'XG', 260, '', 'Polo deportiva de manga corta con cuello abierto en V y logo en la manga.', 'polo-nike-negra-xg.webp'],
];
$pdo = db();
foreach ($prendas as $i => [$nombre, $marca, $talla, $precio, $detalle, $desc, $foto]) {
    $fecha = date('Y-m-d H:i:s', time() - (count($prendas) - $i) * 60);
    $pdo->prepare("INSERT INTO prendas (nombre, marca, talla, precio, detalle, descripcion, foto, estado, creado_en, actualizado_en)
        VALUES (:n, :m, :t, :p, :d, :desc, '', 'disponible', :c, :a)")
        ->execute([':n' => $nombre, ':m' => $marca, ':t' => $talla, ':p' => $precio, ':d' => $detalle, ':desc' => $desc, ':c' => $fecha, ':a' => $fecha]);
    $id = (int) $pdo->lastInsertId();
    $codigo = sprintf('%s-%04d', $talla, $id);
    $archivo = guardar_foto("$fotos/$foto", $codigo, $nombre);
    $pdo->prepare('UPDATE prendas SET codigo = :c, foto = :f WHERE id = :id')->execute([':c' => $codigo, ':f' => $archivo, ':id' => $id]);
    echo "$codigo  $nombre\n";
}
echo "Listo. Los precios son de ejemplo.\n";
