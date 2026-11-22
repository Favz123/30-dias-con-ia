<?php
/**
 * Configuración de Punto Bonito.
 * Copia este archivo como "config.php" (en esta misma carpeta) y llena tus datos.
 * config.php NO se sube a GitHub porque contiene contraseñas.
 */
return [
    // Dirección pública del sitio, sin diagonal al final.
    'sitio_url' => 'https://puntobonito.store',

    // WhatsApp de la tienda: 52 + 10 dígitos, sin espacios ni signos.
    'whatsapp' => '529811050953',

    // Redes sociales (opcional; déjalas vacías si no hay).
    'instagram' => '',   // sólo el usuario, sin @. Ej: 'puntobonito'
    'facebook' => '',    // dirección completa de la página. Ej: 'https://www.facebook.com/puntobonito'

    // Ubicación (opcional). Ayuda a salir en Google cuando buscan "bolsos en <ciudad>".
    // La dirección, si se llena, aparece en el pie de página.
    'ciudad' => '',      // Ej: 'San Francisco de Campeche'
    'estado' => '',      // Ej: 'Campeche'
    'direccion' => '',   // Ej: 'Calle 10 #25, Col. Centro'

    // Código de verificación de Google Search Console (opcional): sólo el valor de content="…"
    // de la etiqueta <meta name="google-site-verification">. Si verificas por DNS no hace falta.
    'google_verificacion' => '',

    // Días que un producto vendido se sigue mostrando con la etiqueta "Vendido".
    'dias_vendido' => 3,

    // Días de plazo de un apartado (se usa para avisar en el panel).
    'dias_apartado' => 7,

    // Días que un producto recién subido lleva la etiqueta "Nuevo".
    'dias_nuevo' => 7,

    // Productos por página en el catálogo.
    'por_pagina' => 24,

    'zona_horaria' => 'America/Merida',

    // Base de datos. En Hostinger: driver 'mysql' con los datos que te da hPanel.
    // Para pruebas en tu computadora: driver 'sqlite' (no necesita servidor).
    'db' => [
        'driver'  => 'mysql',
        'host'    => 'localhost',
        'nombre'  => 'u000000000_puntobonito',
        'usuario' => 'u000000000_puntobonito',
        'clave'   => '',
        'sqlite_ruta' => __DIR__ . '/datos/puntobonito.sqlite',
    ],

    // Clave que se pide UNA sola vez para crear la cuenta de la dueña en /admin.
    // Cámbiala por algo difícil antes de subir el sitio.
    'clave_instalacion' => 'cambia-esta-clave',

    // Clave de emergencia para /admin/recuperar.php, por si NADIE puede entrar al panel.
    // Déjala vacía para desactivarla. Si la usas: 12 caracteres o más, y sólo la conoce quien administra el hosting.
    'clave_recuperacion' => '',

    // Carpeta pública donde se guardan las fotos de los productos.
    // Si subiste app/ DENTRO de public_html, cámbialo por: __DIR__ . '/../uploads'
    'carpeta_fotos' => __DIR__ . '/../public_html/uploads',
];
