<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../app/bootstrap.php') ? __DIR__ . '/../app/bootstrap.php' : __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');

$st = db()->prepare("SELECT codigo, actualizado_en FROM prendas WHERE estado <> 'vendido' ORDER BY id DESC");
$st->execute();

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
echo '<url><loc>', e(url('/')), '</loc><changefreq>daily</changefreq><priority>1.0</priority></url>', "\n";
foreach (TALLAS as $t) {
    echo '<url><loc>', e(url('/talla/' . $t['slug'])), '</loc><changefreq>daily</changefreq><priority>0.9</priority></url>', "\n";
}
foreach ($st as $fila) {
    echo '<url><loc>', e(url('/prenda/' . $fila['codigo'])), '</loc><lastmod>', e(date('Y-m-d', strtotime($fila['actualizado_en']))), '</lastmod></url>', "\n";
}
echo '</urlset>', "\n";
