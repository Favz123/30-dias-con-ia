<?php
/**
 * Configuración de En Busca de Closet.
 * Copia este archivo como "config.php" (en esta misma carpeta) y llena tus datos.
 * config.php NO se sube a GitHub porque contiene contraseñas.
 */
return [
    // Dirección pública del sitio, sin diagonal al final.
    'sitio_url' => 'https://enbuscadecloset.com',

    // WhatsApp de la tienda: 52 + 10 dígitos, sin espacios ni signos. Ej: '529811234567'
    'whatsapp' => '',

    // Días que una prenda vendida se sigue mostrando con la etiqueta "Vendida".
    'dias_vendido' => 3,

    // Días de plazo de un apartado (se usa para avisar en el panel).
    'dias_apartado' => 7,

    // Prendas por página en el catálogo.
    'por_pagina' => 24,

    'zona_horaria' => 'America/Merida', // Campeche

    // Base de datos. En Hostinger: driver 'mysql' con los datos que te da hPanel.
    // Para pruebas en tu computadora: driver 'sqlite' (no necesita servidor).
    'db' => [
        'driver'  => 'mysql',
        'host'    => 'localhost',
        'nombre'  => 'u000000000_closet',
        'usuario' => 'u000000000_closet',
        'clave'   => '',
        'sqlite_ruta' => __DIR__ . '/datos/closet.sqlite',
    ],

    // Clave que se pide UNA sola vez para crear la cuenta de la dueña en /admin.
    // Cámbiala por algo difícil antes de subir el sitio.
    'clave_instalacion' => 'cambia-esta-clave',

    // Carpeta pública donde se guardan las fotos de las prendas.
    // Si subiste app/ DENTRO de public_html, cámbialo por: __DIR__ . '/../uploads'
    'carpeta_fotos' => __DIR__ . '/../public_html/uploads',
];
