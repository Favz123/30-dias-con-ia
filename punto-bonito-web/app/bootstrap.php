<?php
/**
 * Punto Bonito · catálogo en línea
 * Diseño y desarrollo: Favzstudio.com (https://favzstudio.com)
 */
declare(strict_types=1);

const APP_DIR = __DIR__;

/* ---------- Configuración ---------- */
$rutaConfig = __DIR__ . '/config.php';
if (!is_file($rutaConfig)) {
    http_response_code(500);
    exit('Falta app/config.php. Copia app/config.example.php como app/config.php y llena tus datos.');
}
$GLOBALS['CFG'] = require $rutaConfig;

function cfg(string $clave, $defecto = null)
{
    return $GLOBALS['CFG'][$clave] ?? $defecto;
}

date_default_timezone_set(cfg('zona_horaria', 'America/Merida'));

// Las páginas cambian cuando un producto se aparta o se vende: el navegador
// debe volver a preguntar siempre (evita mostrar "vendida" cuando ya no lo está).
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Cache-Control: no-cache, must-revalidate');
}
mb_internal_encoding('UTF-8');

/* ---------- Categorías del catálogo ----------
 * Para agregar una categoría: copia una línea y cambia la clave (3 letras, en mayúsculas),
 * el slug (lo que aparece en la dirección: /categoria/bolsos), el nombre, el ícono y el color.
 * No cambies la clave de una categoría que ya tiene productos: es parte de su código (BOL-0001).
 * Colores disponibles: magenta, azul, naranja, amarillo, verde, morado, vino.
 * La descripción aparece arriba de cada categoría y en Google (unos 120–155 caracteres).
 */
const CATEGORIAS = [
    'BOL' => ['slug' => 'bolsos',        'nombre' => 'Bolsos',        'singular' => 'Bolso',        'icono' => '👜', 'color' => 'magenta',
              'descripcion' => 'Bolsos de mano, tote, cruzados y de playa en colores alegres. Elige el tuyo y apártalo o cómpralo por WhatsApp.'],
    'MON' => ['slug' => 'monederos',     'nombre' => 'Monederos',     'singular' => 'Monedero',     'icono' => '👛', 'color' => 'azul',
              'descripcion' => 'Monederos y carteras pequeñas con cierre, prácticos y bonitos para llevar tus monedas, tarjetas y llaves.'],
    'COS' => ['slug' => 'cosmetiqueras', 'nombre' => 'Cosmetiqueras', 'singular' => 'Cosmetiquera', 'icono' => '💄', 'color' => 'naranja',
              'descripcion' => 'Cosmetiqueras y neceseres para tu maquillaje y artículos de viaje, en diseños coloridos y fáciles de limpiar.'],
    'LAP' => ['slug' => 'lapiceras',     'nombre' => 'Lapiceras',     'singular' => 'Lapicera',     'icono' => '✏️', 'color' => 'verde',
              'descripcion' => 'Lapiceras y estuches escolares para guardar lápices, plumas y colores. Ideales para la escuela o la oficina.'],
    'TAZ' => ['slug' => 'tazas',         'nombre' => 'Tazas',         'singular' => 'Taza',         'icono' => '☕', 'color' => 'morado',
              'descripcion' => 'Tazas decorativas y personalizables para tu café o té, y para regalar en cumpleaños y fechas especiales.'],
    'TER' => ['slug' => 'termos',        'nombre' => 'Termos',        'singular' => 'Termo',        'icono' => '🧴', 'color' => 'amarillo',
              'descripcion' => 'Termos para mantener tus bebidas frías o calientes, en colores y diseños bonitos para llevar a todas partes.'],
    'VAS' => ['slug' => 'vasos',         'nombre' => 'Vasos',         'singular' => 'Vaso',         'icono' => '🥤', 'color' => 'vino',
              'descripcion' => 'Vasos con tapa y popote, reutilizables y llenos de color, para tus bebidas de todos los días.'],
    'LLA' => ['slug' => 'llaveros',      'nombre' => 'Llaveros',      'singular' => 'Llavero',      'icono' => '🔑', 'color' => 'magenta',
              'descripcion' => 'Llaveros bonitos y originales para tus llaves, tu bolso o tu mochila. Un detalle perfecto para regalar.'],
    'PLU' => ['slug' => 'plumas',        'nombre' => 'Plumas',        'singular' => 'Pluma',        'icono' => '🖊️', 'color' => 'azul',
              'descripcion' => 'Plumas y bolígrafos decorativos para escribir con estilo, tomar apuntes o regalar.'],
    'ARE' => ['slug' => 'aretes',        'nombre' => 'Aretes',        'singular' => 'Aretes',       'icono' => '💎', 'color' => 'naranja',
              'descripcion' => 'Aretes bonitos y ligeros para todos los días y para ocasiones especiales, en muchos estilos y colores.'],
    'ESP' => ['slug' => 'espejos',       'nombre' => 'Espejos',       'singular' => 'Espejo',       'icono' => '🪞', 'color' => 'verde',
              'descripcion' => 'Espejos de bolsillo y de mano, decorados y prácticos para retocarte donde estés.'],
    'MAN' => ['slug' => 'mandiles',      'nombre' => 'Mandiles',      'singular' => 'Mandil',       'icono' => '🧑‍🍳', 'color' => 'morado',
              'descripcion' => 'Mandiles de cocina bonitos y resistentes, para cocinar, hornear o atender tu negocio con estilo.'],
];
const ESTADOS = ['disponible' => 'Disponible', 'apartado' => 'Apartado', 'vendido' => 'Vendido'];

function categoria_por_slug(string $slug): ?string
{
    foreach (CATEGORIAS as $clave => $t) {
        if ($t['slug'] === $slug) {
            return $clave;
        }
    }
    return null;
}

/* ---------- Base de datos ---------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $c = cfg('db');
    $opciones = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if ($c['driver'] === 'sqlite') {
        $dir = dirname($c['sqlite_ruta']);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $pdo = new PDO('sqlite:' . $c['sqlite_ruta'], null, null, $opciones);
        $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
    } else {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['nombre']);
        $pdo = new PDO($dsn, $c['usuario'], $c['clave'], $opciones);
    }
    crear_tablas($pdo, $c['driver']);
    return $pdo;
}

function crear_tablas(PDO $pdo, string $driver): void
{
    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS productos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo TEXT UNIQUE,
            nombre TEXT NOT NULL,
            color TEXT NOT NULL DEFAULT '',
            categoria TEXT NOT NULL,
            precio INTEGER NULL,
            detalle TEXT NOT NULL DEFAULT '',
            descripcion TEXT NOT NULL DEFAULT '',
            foto TEXT NOT NULL DEFAULT '',
            estado TEXT NOT NULL DEFAULT 'disponible',
            apartado_en TEXT NULL,
            vendido_en TEXT NULL,
            creado_en TEXT NOT NULL,
            actualizado_en TEXT NOT NULL
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_productos_categoria ON productos (categoria)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_productos_estado ON productos (estado)');
        $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario TEXT UNIQUE NOT NULL,
            clave_hash TEXT NOT NULL,
            creado_en TEXT NOT NULL
        )");
        return;
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS productos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(12) NULL UNIQUE,
        nombre VARCHAR(120) NOT NULL,
        color VARCHAR(60) NOT NULL DEFAULT '',
        categoria VARCHAR(3) NOT NULL,
        precio INT UNSIGNED NULL,
        detalle VARCHAR(60) NOT NULL DEFAULT '',
        descripcion TEXT NOT NULL,
        foto VARCHAR(60) NOT NULL DEFAULT '',
        estado VARCHAR(12) NOT NULL DEFAULT 'disponible',
        apartado_en DATETIME NULL,
        vendido_en DATETIME NULL,
        creado_en DATETIME NOT NULL,
        actualizado_en DATETIME NOT NULL,
        INDEX idx_productos_categoria (categoria),
        INDEX idx_productos_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        usuario VARCHAR(60) NOT NULL UNIQUE,
        clave_hash VARCHAR(255) NOT NULL,
        creado_en DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ahora(): string
{
    return date('Y-m-d H:i:s');
}

/** Fecha límite: las vendidas antes de esto ya no se muestran en el catálogo. */
function limite_vendidas(): string
{
    return date('Y-m-d H:i:s', time() - (int) cfg('dias_vendido', 3) * 86400);
}

/** Condición SQL para productos visibles en el catálogo público. */
const SQL_VISIBLE = "(estado <> 'vendido' OR vendido_en >= :limite)";

/* ---------- Utilidades de salida ---------- */
function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $ruta = ''): string
{
    return rtrim((string) cfg('sitio_url'), '/') . '/' . ltrim($ruta, '/');
}

function precio(?int $p): string
{
    return $p === null ? '' : '$' . number_format($p, 0, '.', ',');
}

function foto_url(array $p, bool $miniatura = false): string
{
    if ($p['foto'] === '') {
        return '/assets/img/logo.webp';
    }
    return '/uploads/' . ($miniatura ? str_replace('.webp', '-t.webp', $p['foto']) : $p['foto']);
}

function producto_url(array $p, bool $absoluta = false): string
{
    $ruta = '/producto/' . $p['codigo'];
    return $absoluta ? url($ruta) : $ruta;
}

function whatsapp_link(string $mensaje): string
{
    return 'https://wa.me/' . preg_replace('/\D/', '', (string) cfg('whatsapp')) . '?text=' . rawurlencode($mensaje);
}

/** " en Campeche" si se configuró la ciudad; vacío si no. Se usa en títulos y descripciones para Google. */
function en_ciudad(): string
{
    return cfg('ciudad') ? ' en ' . cfg('ciudad') : '';
}

/** ¿Ya se configuró el número de WhatsApp de la tienda? (52 + 10 dígitos) */
function whatsapp_configurado(): bool
{
    $tel = preg_replace('/\D/', '', (string) cfg('whatsapp'));
    return strlen($tel) === 12 && $tel !== '520000000000';
}

/** El número de WhatsApp para mostrarlo: "981 105 0953". */
function telefono_legible(): string
{
    $tel = substr(preg_replace('/\D/', '', (string) cfg('whatsapp')), -10);
    return substr($tel, 0, 3) . ' ' . substr($tel, 3, 3) . ' ' . substr($tel, 6);
}

/**
 * Mensaje de WhatsApp ya escrito para un producto.
 * $tipo: 'apartar', 'comprar' o 'info'. Los textos se pueden cambiar aquí.
 */
function mensaje_producto(array $p, string $tipo = 'info'): string
{
    $inicio = [
        'apartar' => '¡Hola, Punto Bonito! 💖 Me gustaría *apartar* este producto:',
        'comprar' => '¡Hola, Punto Bonito! 🛍️ Quiero *comprar* este producto:',
        'info'    => '¡Hola, Punto Bonito! 👋 Quisiera *más información* de este producto:',
    ][$tipo] ?? '¡Hola, Punto Bonito! Me interesa este producto:';
    $linea = sprintf('%s · %s (%s)', $p['nombre'], $p['codigo'], CATEGORIAS[$p['categoria']]['singular']);
    if ($p['precio'] !== null) {
        $linea .= ' · ' . precio((int) $p['precio']);
    }
    $final = [
        'apartar' => '¿Cómo lo aparto?',
        'comprar' => '¿Cuáles son las formas de pago y entrega?',
        'info'    => '¿Me pueden dar más detalles?',
    ][$tipo] ?? '';
    return $inicio . "\n" . $linea . "\n" . producto_url($p, true) . ($final !== '' ? "\n" . $final : '');
}

/** Texto alternativo (alt) de la foto: qué es, color y categoría, para Google Imágenes y lectores de pantalla. */
function texto_alt(array $p): string
{
    $texto = $p['nombre'];
    if ($p['color'] !== '' && mb_stripos($texto, $p['color']) === false) {
        $texto .= ' ' . mb_strtolower($p['color']);
    }
    return $texto . ' · ' . CATEGORIAS[$p['categoria']]['nombre'] . ' en Punto Bonito';
}

/** Convierte un texto en una parte de URL o nombre de archivo: "Short Niké negro" → "short-nike-negro". */
function slug(string $texto, int $max = 40): string
{
    $t = mb_strtolower(trim($texto));
    $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    $t = trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-');
    if (strlen($t) > $max) {
        // Corta en la última palabra completa que cabe.
        $t = substr($t, 0, $max + 1);
        $t = strpos($t, '-') !== false ? substr($t, 0, strrpos($t, '-')) : substr($t, 0, $max);
    }
    return $t;
}

function hace(string $fecha): string
{
    $dias = (int) floor((time() - strtotime($fecha)) / 86400);
    if ($dias <= 0) {
        return 'hoy';
    }
    return $dias === 1 ? 'hace 1 día' : "hace $dias días";
}

function normaliza_producto(array $p): array
{
    $p['precio'] = $p['precio'] === null ? null : (int) $p['precio'];
    $p['id'] = (int) $p['id'];
    return $p;
}

/* ---------- Sesión, CSRF y avisos (panel) ---------- */
function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $duracion = 60 * 60 * 24 * 30; // 30 días: la dueña no tiene que iniciar sesión a cada rato
    ini_set('session.gc_maxlifetime', (string) $duracion);
    session_name('puntobonito_admin');
    session_set_cookie_params([
        'lifetime' => $duracion,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string
{
    iniciar_sesion();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verificar_csrf(): void
{
    iniciar_sesion();
    $enviado = (string) ($_POST['csrf'] ?? '');
    if ($enviado === '' || !hash_equals($_SESSION['csrf'] ?? '', $enviado)) {
        http_response_code(400);
        exit('La sesión expiró. Regresa y vuelve a intentarlo.');
    }
}

function aviso(string $texto, string $tipo = 'ok'): void
{
    iniciar_sesion();
    $_SESSION['aviso'] = ['texto' => $texto, 'tipo' => $tipo];
}

function tomar_aviso(): ?array
{
    iniciar_sesion();
    $a = $_SESSION['aviso'] ?? null;
    unset($_SESSION['aviso']);
    return $a;
}

function redirigir(string $ruta): void
{
    header('Location: ' . $ruta, true, 303);
    exit;
}

function usuario_actual(): ?array
{
    iniciar_sesion();
    return $_SESSION['usuario'] ?? null;
}

/** Huella corta de la contraseña: si la contraseña cambia, las sesiones viejas dejan de valer. */
function huella_clave(string $hash): string
{
    return substr(hash('sha256', $hash), 0, 16);
}

/** Guarda en la sesión al usuario que acaba de entrar. */
function abrir_sesion(int $id, string $usuario, string $hash): void
{
    iniciar_sesion();
    session_regenerate_id(true);
    $_SESSION['usuario'] = ['id' => $id, 'usuario' => $usuario, 'huella' => huella_clave($hash)];
}

function requiere_login(): void
{
    $u = usuario_actual();
    if ($u) {
        // La cuenta debe seguir existiendo y con la misma contraseña con la que se entró.
        $st = db()->prepare('SELECT usuario, clave_hash FROM usuarios WHERE id = :id');
        $st->execute([':id' => $u['id']]);
        $fila = $st->fetch();
        if ($fila && !isset($u['huella'])) {
            // Sesiones abiertas antes de esta función: se adoptan una sola vez.
            $_SESSION['usuario']['huella'] = huella_clave($fila['clave_hash']);
            return;
        }
        if ($fila && hash_equals($u['huella'], huella_clave($fila['clave_hash']))) {
            return;
        }
        unset($_SESSION['usuario']);
        aviso('Tu contraseña cambió o tu cuenta ya no existe. Vuelve a entrar.', 'error');
        redirigir('/admin/entrar.php');
    }
    $total = (int) db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    redirigir($total === 0 ? '/admin/instalar.php' : '/admin/entrar.php');
}

/* ---------- Contraseñas y usuarios ---------- */
function validar_usuario_nuevo(string $usuario): array
{
    if (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $usuario)) {
        return ['El usuario debe tener de 3 a 40 letras o números, sin espacios.'];
    }
    $st = db()->prepare('SELECT COUNT(*) FROM usuarios WHERE usuario = :u');
    $st->execute([':u' => $usuario]);
    return (int) $st->fetchColumn() > 0 ? ['Ese usuario ya existe.'] : [];
}

function validar_clave_nueva(string $clave, string $confirmacion): array
{
    if (mb_strlen($clave) < 8) {
        return ['La contraseña nueva debe tener al menos 8 caracteres.'];
    }
    if (mb_strlen($clave) > 200) {
        return ['La contraseña nueva es demasiado larga.'];
    }
    if ($clave !== $confirmacion) {
        return ['Las dos contraseñas nuevas no coinciden.'];
    }
    return [];
}

/** Cambia la contraseña y devuelve el hash nuevo. */
function cambiar_clave(int $id, string $clave): string
{
    $hash = password_hash($clave, PASSWORD_DEFAULT);
    db()->prepare('UPDATE usuarios SET clave_hash = :h WHERE id = :id')->execute([':h' => $hash, ':id' => $id]);
    return $hash;
}

function crear_usuario(string $usuario, string $clave): int
{
    $hash = password_hash($clave, PASSWORD_DEFAULT);
    db()->prepare('INSERT INTO usuarios (usuario, clave_hash, creado_en) VALUES (:u, :h, :f)')
        ->execute([':u' => $usuario, ':h' => $hash, ':f' => ahora()]);
    return (int) db()->lastInsertId();
}

/** Freno contra quien intente adivinar: 5 fallos seguidos = 60 s de espera. Devuelve segundos por esperar. */
function espera_intentos(string $clave): int
{
    iniciar_sesion();
    return max(0, (int) ($_SESSION['bloqueo'][$clave] ?? 0) - time());
}

function registrar_fallo(string $clave): void
{
    sleep(1);
    $_SESSION['fallos'][$clave] = (int) ($_SESSION['fallos'][$clave] ?? 0) + 1;
    if ($_SESSION['fallos'][$clave] >= 5) {
        $_SESSION['bloqueo'][$clave] = time() + 60;
        $_SESSION['fallos'][$clave] = 0;
    }
}

/* ---------- Fotos ---------- */
/**
 * Convierte la foto subida a WebP (grande y miniatura), corrige la rotación
 * y elimina los metadatos. Devuelve el nombre del archivo grande.
 */
function guardar_foto(string $tmp, string $codigo, string $descripcion = ''): string
{
    @ini_set('memory_limit', '384M');
    $info = @getimagesize($tmp);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
        throw new RuntimeException('La foto debe ser JPG, PNG o WebP. Si usas iPhone, envíala como JPG.');
    }
    $img = @imagecreatefromstring((string) file_get_contents($tmp));
    if (!$img) {
        throw new RuntimeException('No se pudo leer la foto. Intenta con otra.');
    }
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmp);
        $giro = ['3' => 180, '6' => -90, '8' => 90][(string) ($exif['Orientation'] ?? '')] ?? 0;
        if ($giro) {
            $img = imagerotate($img, $giro, 0);
        }
    }

    $carpeta = rtrim((string) cfg('carpeta_fotos'), '/');
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0775, true);
    }
    // Nombre descriptivo para Google Imágenes: "short-nike-negro-ch-0012-a1b2c3.webp" (cabe en 60 caracteres).
    $prefijo = slug($descripcion, 30);
    $nombre = ($prefijo !== '' ? $prefijo . '-' : '') . strtolower($codigo) . '-' . bin2hex(random_bytes(3)) . '.webp';
    guardar_redimensionada($img, 1200, $carpeta . '/' . $nombre, 80);
    guardar_redimensionada($img, 600, $carpeta . '/' . str_replace('.webp', '-t.webp', $nombre), 76);
    imagedestroy($img);
    return $nombre;
}

function guardar_redimensionada($img, int $max, string $destino, int $calidad): void
{
    $w = imagesx($img);
    $h = imagesy($img);
    $escala = min(1, $max / max($w, $h));
    $nw = (int) round($w * $escala);
    $nh = (int) round($h * $escala);
    $lienzo = imagecreatetruecolor($nw, $nh);
    imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
    imagecopyresampled($lienzo, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagewebp($lienzo, $destino, $calidad);
    imagedestroy($lienzo);
}

function borrar_foto(string $nombre): void
{
    if ($nombre === '' || !preg_match('/^[a-z0-9-]+\.webp$/', $nombre)) {
        return;
    }
    $carpeta = rtrim((string) cfg('carpeta_fotos'), '/');
    @unlink($carpeta . '/' . $nombre);
    @unlink($carpeta . '/' . str_replace('.webp', '-t.webp', $nombre));
}

/* ---------- Contenido fijo ---------- */
/**
 * Preguntas frecuentes: [pregunta, respuesta en HTML]. Se usan en la página y en los datos para Google.
 * Son respuestas generales: ajústalas a las reglas reales de la tienda (montos, plazos, envíos).
 */
function preguntas_frecuentes(): array
{
    return [
        ['¿Cómo compro un producto?',
            '<p>Toca <strong>“Comprar”</strong> en el producto que te guste. Se abre WhatsApp con un mensaje listo que incluye el nombre y el código del producto; sólo envíalo y te respondemos para acordar el pago y la entrega.</p>'],
        ['¿Puedo apartar un producto?',
            '<p><strong>Sí.</strong> Toca <strong>“Apartar”</strong> y escríbenos por WhatsApp. Te confirmamos el anticipo y el plazo para liquidar. Mientras esté apartado, el producto aparece con la etiqueta <em>Apartado</em>.</p>'],
        ['¿Qué métodos de pago aceptan?',
            '<p>Pregúntanos por WhatsApp las formas de pago disponibles (efectivo, transferencia o depósito) y te damos los datos.</p>'],
        ['¿Hacen envíos?',
            '<p>Escríbenos con tu código postal y te decimos las opciones de entrega y su costo.</p>'],
        ['¿Los productos de las fotos son los que recibo?',
            '<p>Sí, las fotos son de nuestros productos. Si quieres ver otro ángulo, otro color o más detalles, toca <strong>“Más información”</strong> y te mandamos más fotos.</p>'],
    ];
}

function jsonld_tienda(): array
{
    $tienda = [
        '@context' => 'https://schema.org',
        '@type' => 'Store',
        '@id' => url('/#tienda'),
        'name' => 'Punto Bonito',
        'url' => url('/'),
        'logo' => [
            '@type' => 'ImageObject',
            'url' => url('/assets/img/logo-512.png'),
            'caption' => 'Logo de Punto Bonito',
        ],
        'image' => [url('/assets/img/logo-512.png')],
        'description' => 'Bolsos, monederos, cosmetiqueras, lapiceras, tazas, termos, vasos, llaveros, plumas, aretes, espejos y mandiles. Aparta o compra por WhatsApp.',
        'currenciesAccepted' => 'MXN',
        'areaServed' => 'MX',
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => 'Catálogo de Punto Bonito',
            'itemListElement' => array_values(array_map(fn ($c) => [
                '@type' => 'OfferCatalog',
                'name' => $c['nombre'],
                'url' => url('/categoria/' . $c['slug']),
            ], CATEGORIAS)),
        ],
    ];
    $redes = array_values(array_filter([cfg('instagram') ? 'https://www.instagram.com/' . cfg('instagram') . '/' : '', (string) cfg('facebook', '')]));
    if ($redes) {
        $tienda['sameAs'] = $redes;
    }
    if (cfg('direccion') || cfg('ciudad')) {
        $tienda['address'] = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => (string) cfg('direccion'),
            'addressLocality' => (string) cfg('ciudad'),
            'addressRegion' => (string) cfg('estado'),
            'addressCountry' => 'MX',
        ]);
    }
    $tel = preg_replace('/\D/', '', (string) cfg('whatsapp'));
    if (whatsapp_configurado()) {
        $tienda['telephone'] = '+' . $tel;
    }
    return $tienda;
}

/** Nombre del sitio para Google (el que aparece arriba de cada resultado). */
function jsonld_sitio(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Punto Bonito',
        'url' => url('/'),
        'inLanguage' => 'es-MX',
        'publisher' => ['@id' => url('/#tienda')],
        'creator' => ['@type' => 'Organization', 'name' => 'Favzstudio', 'url' => 'https://favzstudio.com'],
    ];
}

/** Lista de los productos que muestra una página (categorías y búsqueda). */
function jsonld_lista(array $productos, string $nombre): array
{
    $items = [];
    foreach (array_values($productos) as $i => $p) {
        $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => producto_url($p, true), 'name' => $p['nombre']];
    }
    return ['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => $nombre, 'itemListElement' => $items];
}

function jsonld_preguntas(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($f) => [
            '@type' => 'Question',
            'name' => $f[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags(str_replace('</p>', ' ', $f[1])))],
        ], preguntas_frecuentes()),
    ];
}

/* ---------- Consultas del catálogo público ---------- */
const SQL_ORDEN_PUBLICO = "CASE estado WHEN 'disponible' THEN 0 WHEN 'apartado' THEN 1 ELSE 2 END, creado_en DESC, id DESC";

function like(string $texto): string
{
    // "!" es el carácter de escape (ESCAPE '!'): funciona igual en MySQL y SQLite.
    // Una diagonal invertida no sirve: MySQL la toma como escape dentro de las comillas.
    return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $texto) . '%';
}

/** Cantidad de productos visibles por categoría, p. ej. ['BOL' => 12, 'TAZ' => 4, ...]. */
function contar_por_categoria(): array
{
    $st = db()->prepare('SELECT categoria, COUNT(*) AS n FROM productos WHERE ' . SQL_VISIBLE . ' GROUP BY categoria');
    $st->execute([':limite' => limite_vendidas()]);
    $conteo = array_fill_keys(array_keys(CATEGORIAS), 0);
    foreach ($st as $fila) {
        if (isset($conteo[$fila['categoria']])) {
            $conteo[$fila['categoria']] = (int) $fila['n'];
        }
    }
    return $conteo;
}

function colores_visibles(?string $categoria = null): array
{
    $sql = "SELECT DISTINCT color FROM productos WHERE color <> '' AND " . SQL_VISIBLE;
    $params = [':limite' => limite_vendidas()];
    if ($categoria) {
        $sql .= ' AND categoria = :categoria';
        $params[':categoria'] = $categoria;
    }
    $st = db()->prepare($sql . ' ORDER BY color');
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

const ORDENES = [
    'nuevos' => 'Más nuevos',
    'menor' => 'Precio: menor a mayor',
    'mayor' => 'Precio: mayor a menor',
];

/** Búsqueda paginada. Devuelve [lista de productos, total]. */
function buscar_productos(?string $categoria, string $q, string $color, int $pagina, int $porPagina, string $orden = 'nuevos'): array
{
    $where = [SQL_VISIBLE];
    $params = [':limite' => limite_vendidas()];
    if ($categoria) {
        $where[] = 'categoria = :categoria';
        $params[':categoria'] = $categoria;
    }
    if ($color !== '') {
        $where[] = 'color = :color';
        $params[':color'] = $color;
    }
    if ($q !== '') {
        // "bolso", "Bolsos" o "taza" también encuentran todo lo de esa categoría.
        $enCategorias = [];
        $qSimple = slug($q);
        foreach (CATEGORIAS as $clave => $c) {
            if (strlen($qSimple) >= 3 && (str_starts_with($c['slug'], $qSimple) || str_starts_with(slug($c['singular']), $qSimple))) {
                $enCategorias[] = $clave;
            }
        }
        $porCategoria = $enCategorias ? " OR categoria IN ('" . implode("','", $enCategorias) . "')" : '';
        $where[] = "(nombre LIKE :q1 ESCAPE '!' OR color LIKE :q2 ESCAPE '!' OR codigo LIKE :q3 ESCAPE '!' OR descripcion LIKE :q4 ESCAPE '!'$porCategoria)";
        $params[':q1'] = $params[':q2'] = $params[':q3'] = $params[':q4'] = like($q);
    }
    $condicion = implode(' AND ', $where);

    $st = db()->prepare("SELECT COUNT(*) FROM productos WHERE $condicion");
    $st->execute($params);
    $total = (int) $st->fetchColumn();

    $offset = max(0, ($pagina - 1) * $porPagina);
    $st = db()->prepare("SELECT * FROM productos WHERE $condicion ORDER BY " . orden_sql($orden) . " LIMIT $porPagina OFFSET $offset");
    $st->execute($params);
    return [array_map('normaliza_producto', $st->fetchAll()), $total];
}

/** Orden del catálogo: primero lo disponible; después, lo que eligió la clienta. Los productos sin precio van al final. */
function orden_sql(string $orden): string
{
    $estado = "CASE estado WHEN 'disponible' THEN 0 WHEN 'apartado' THEN 1 ELSE 2 END";
    if ($orden === 'menor') {
        return "$estado, CASE WHEN precio IS NULL THEN 1 ELSE 0 END, precio ASC, id DESC";
    }
    if ($orden === 'mayor') {
        return "$estado, CASE WHEN precio IS NULL THEN 1 ELSE 0 END, precio DESC, id DESC";
    }
    return SQL_ORDEN_PUBLICO;
}

/** ¿Es nuevo? (subido en los últimos días de 'dias_nuevo'). */
function es_nuevo(array $p): bool
{
    return $p['estado'] === 'disponible' && strtotime($p['creado_en']) >= time() - (int) cfg('dias_nuevo', 7) * 86400;
}

function producto_por_codigo(string $codigo): ?array
{
    $st = db()->prepare('SELECT * FROM productos WHERE codigo = :c');
    $st->execute([':c' => strtoupper($codigo)]);
    $p = $st->fetch();
    return $p ? normaliza_producto($p) : null;
}

function producto_visible(array $p): bool
{
    return $p['estado'] !== 'vendido' || ($p['vendido_en'] !== null && $p['vendido_en'] >= limite_vendidas());
}

/* ---------- Alta y validación de productos (formulario y carga masiva) ---------- */
const CAMPOS_PRODUCTO = ['nombre', 'color', 'categoria', 'precio', 'detalle', 'descripcion'];

function leer_campos_producto(array $src): array
{
    $f = [];
    foreach (CAMPOS_PRODUCTO as $campo) {
        $f[$campo] = trim((string) ($src[$campo] ?? ''));
    }
    $f['categoria'] = strtoupper($f['categoria']);
    $f['precio'] = preg_replace('/[^\d]/', '', $f['precio']);
    return $f;
}

function validar_producto(array $f): array
{
    $errores = [];
    if ($f['nombre'] === '' || mb_strlen($f['nombre']) > 120) {
        $errores[] = 'Escribe el nombre del producto (máximo 120 letras).';
    }
    if (!isset(CATEGORIAS[$f['categoria']])) {
        $errores[] = 'Elige la categoría.';
    }
    if (mb_strlen($f['color']) > 60 || mb_strlen($f['detalle']) > 60) {
        $errores[] = 'El color y el detalle deben ser cortos (máximo 60 letras).';
    }
    if (mb_strlen($f['descripcion']) > 1000) {
        $errores[] = 'La descripción es muy larga (máximo 1000 letras).';
    }
    if ($f['precio'] !== '' && (int) $f['precio'] > 1000000) {
        $errores[] = 'Revisa el precio.';
    }
    return $errores;
}

/** Revisa la foto subida. Devuelve [hay foto (bool), lista de errores]. */
function validar_archivo_foto(?array $archivo, bool $obligatoria): array
{
    $hayFoto = $archivo && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $errores = [];
    if ($hayFoto && $archivo['error'] !== UPLOAD_ERR_OK) {
        $errores[] = in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'La foto pesa demasiado. Intenta con otra.'
            : 'No se pudo subir la foto. Intenta de nuevo.';
    } elseif ($hayFoto && !is_uploaded_file($archivo['tmp_name'])) {
        $errores[] = 'No se pudo subir la foto. Intenta de nuevo.';
    }
    if ($obligatoria && !$hayFoto) {
        $errores[] = 'Agrega la foto del producto.';
    }
    return [$hayFoto, $errores];
}

/**
 * Da de alta un producto nuevo: lo guarda, le asigna código (BOL-0001) y procesa la foto.
 * Devuelve el código. Lanza RuntimeException (mensaje para la usuaria) o PDOException.
 */
function crear_producto(array $f, string $rutaFoto): string
{
    $pdo = db();
    $ahora = ahora();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO productos (nombre, color, categoria, precio, detalle, descripcion, foto, estado, creado_en, actualizado_en)
            VALUES (:nombre, :color, :categoria, :precio, :detalle, :descripcion, '', 'disponible', :creado, :act)")->execute([
            ':nombre' => $f['nombre'],
            ':color' => $f['color'],
            ':categoria' => $f['categoria'],
            ':precio' => $f['precio'] === '' ? null : (int) $f['precio'],
            ':detalle' => $f['detalle'],
            ':descripcion' => $f['descripcion'],
            ':creado' => $ahora,
            ':act' => $ahora,
        ]);
        $id = (int) $pdo->lastInsertId();
        $codigo = sprintf('%s-%04d', $f['categoria'], $id);
        $foto = guardar_foto($rutaFoto, $codigo, $f['nombre']);
        $pdo->prepare('UPDATE productos SET codigo = :c, foto = :f WHERE id = :id')
            ->execute([':c' => $codigo, ':f' => $foto, ':id' => $id]);
        $pdo->commit();
        return $codigo;
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (isset($foto)) {
            borrar_foto($foto);
        }
        throw $ex;
    }
}

function colores_sugeridos(): array
{
    return array_values(array_unique(array_merge(
        db()->query("SELECT DISTINCT color FROM productos WHERE color <> '' ORDER BY color")->fetchAll(PDO::FETCH_COLUMN),
        ['Rosa', 'Fucsia', 'Rojo', 'Vino', 'Naranja', 'Amarillo', 'Verde', 'Menta', 'Azul', 'Azul marino', 'Turquesa', 'Morado', 'Lila', 'Negro', 'Blanco', 'Beige', 'Café', 'Dorado', 'Plateado', 'Multicolor', 'Floral', 'Estampado']
    )));
}
