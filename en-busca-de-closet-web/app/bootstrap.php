<?php
/**
 * En Busca de Closet · enbuscadecloset.com
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

// Las páginas cambian cuando una prenda se aparta o se vende: el navegador
// debe volver a preguntar siempre (evita mostrar "vendida" cuando ya no lo está).
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Cache-Control: no-cache, must-revalidate');
}
mb_internal_encoding('UTF-8');

/* ---------- Catálogo de tallas ---------- */
const TALLAS = [
    'XCH' => ['slug' => 'extra-chica', 'nombre' => 'Extra chica'],
    'CH' => ['slug' => 'chica',       'nombre' => 'Chica'],
    'M'  => ['slug' => 'mediana',     'nombre' => 'Mediana'],
    'G'  => ['slug' => 'grande',      'nombre' => 'Grande'],
    'XG' => ['slug' => 'extragrande', 'nombre' => 'Extragrande'],
];
const ESTADOS = ['disponible' => 'Disponible', 'apartado' => 'Apartada', 'vendido' => 'Vendida'];

function talla_por_slug(string $slug): ?string
{
    foreach (TALLAS as $clave => $t) {
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
        $pdo->exec("CREATE TABLE IF NOT EXISTS prendas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo TEXT UNIQUE,
            nombre TEXT NOT NULL,
            marca TEXT NOT NULL DEFAULT '',
            talla TEXT NOT NULL,
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
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_prendas_talla ON prendas (talla)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_prendas_estado ON prendas (estado)');
        $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario TEXT UNIQUE NOT NULL,
            clave_hash TEXT NOT NULL,
            creado_en TEXT NOT NULL
        )");
        return;
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS prendas (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(12) NULL UNIQUE,
        nombre VARCHAR(120) NOT NULL,
        marca VARCHAR(60) NOT NULL DEFAULT '',
        talla VARCHAR(3) NOT NULL,
        precio INT UNSIGNED NULL,
        detalle VARCHAR(60) NOT NULL DEFAULT '',
        descripcion TEXT NOT NULL,
        foto VARCHAR(60) NOT NULL DEFAULT '',
        estado VARCHAR(12) NOT NULL DEFAULT 'disponible',
        apartado_en DATETIME NULL,
        vendido_en DATETIME NULL,
        creado_en DATETIME NOT NULL,
        actualizado_en DATETIME NOT NULL,
        INDEX idx_prendas_talla (talla),
        INDEX idx_prendas_estado (estado)
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

/** Condición SQL para prendas visibles en el catálogo público. */
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

function prenda_url(array $p, bool $absoluta = false): string
{
    $ruta = '/prenda/' . $p['codigo'];
    return $absoluta ? url($ruta) : $ruta;
}

function whatsapp_link(string $mensaje): string
{
    return 'https://wa.me/' . preg_replace('/\D/', '', (string) cfg('whatsapp')) . '?text=' . rawurlencode($mensaje);
}

function mensaje_prenda(array $p): string
{
    $msg = sprintf('Me interesa este artículo de talla %s: %s · %s', TALLAS[$p['talla']]['nombre'], $p['codigo'], $p['nombre']);
    if ($p['precio'] !== null) {
        $msg .= ' (' . precio((int) $p['precio']) . ')';
    }
    return $msg . '. ' . prenda_url($p, true);
}

function hace(string $fecha): string
{
    $dias = (int) floor((time() - strtotime($fecha)) / 86400);
    if ($dias <= 0) {
        return 'hoy';
    }
    return $dias === 1 ? 'hace 1 día' : "hace $dias días";
}

function normaliza_prenda(array $p): array
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
    session_name('closet_admin');
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
function guardar_foto(string $tmp, string $codigo): string
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
    $nombre = strtolower($codigo) . '-' . bin2hex(random_bytes(3)) . '.webp';
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
/** Preguntas frecuentes: [pregunta, respuesta en HTML]. Se usan en la página y en los datos para Google. */
function preguntas_frecuentes(): array
{
    return [
        ['¿Qué métodos de pago aceptan?',
            '<p><strong>Transferencia, depósito, tarjeta o efectivo.</strong></p><p>En caso de ser envío, el pago sólo puede ser por transferencia o depósito.</p><p>Si eres de Campeche, el pago puede ser en efectivo, transferencia o con nuestra terminal para tarjeta de débito y crédito.</p>'],
        ['¿Se puede acumular?',
            '<p><strong>Sí</strong>, siempre y cuando las prendas queden completamente saldadas.</p><p>Dispones de <strong>3 meses</strong> para acumular prendas y aprovechar al máximo el costo del envío.</p>'],
        ['¿Cómo funcionan los apartados?',
            '<p>Para realizar un apartado se debe pagar el <strong>50% del valor de la prenda</strong> y dispones de <strong>una semana</strong> para saldar el restante.</p><p>En caso de que pase la semana y no liquides el restante o mandes mensaje, la prenda vuelve a pasar a disponible y no se devuelve el importe previamente pagado.</p>'],
        ['¿Cuánto tiempo tengo para confirmar y pagar?',
            '<p>En las actualizaciones la prenda se otorga al primer comentario o DM y tienes <strong>30 minutos</strong> para confirmar. Ya confirmada, cuentas con <strong>24 horas</strong> para pagar.</p>'],
        ['¿Cuánto cuesta el envío?',
            '<p>Correos de México: <strong>$80</strong> con guía de rastreo, sin importar cuántas prendas incluyas. Estafeta: <strong>desde $160</strong> según zona y peso. <a href="/#envios">Ver detalles de envíos</a>.</p>'],
    ];
}

function jsonld_tienda(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'ClothingStore',
        'name' => 'En Busca de Closet',
        'url' => url('/'),
        'logo' => url('/assets/img/logo-512.png'),
        'image' => url('/assets/img/tienda-fisica-campeche.webp'),
        'description' => 'Tienda de ropa de segunda mano y ropa deportiva de marca en Campeche, con envíos a todo México.',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Av. Colosio #54, entre Calle 20 y Calle Victoria, Col. San José',
            'addressLocality' => 'San Francisco de Campeche',
            'addressRegion' => 'Campeche',
            'addressCountry' => 'MX',
        ],
        'paymentAccepted' => 'Efectivo, transferencia, depósito, tarjeta de débito y crédito',
        'currenciesAccepted' => 'MXN',
        'areaServed' => 'MX',
        'sameAs' => ['https://www.instagram.com/enbuscadecloset/'],
    ];
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

/** Cantidad de prendas visibles por talla, p. ej. ['CH' => 12, 'M' => 4, ...]. */
function contar_por_talla(): array
{
    $st = db()->prepare('SELECT talla, COUNT(*) AS n FROM prendas WHERE ' . SQL_VISIBLE . ' GROUP BY talla');
    $st->execute([':limite' => limite_vendidas()]);
    $conteo = array_fill_keys(array_keys(TALLAS), 0);
    foreach ($st as $fila) {
        if (isset($conteo[$fila['talla']])) {
            $conteo[$fila['talla']] = (int) $fila['n'];
        }
    }
    return $conteo;
}

function marcas_visibles(?string $talla = null): array
{
    $sql = "SELECT DISTINCT marca FROM prendas WHERE marca <> '' AND " . SQL_VISIBLE;
    $params = [':limite' => limite_vendidas()];
    if ($talla) {
        $sql .= ' AND talla = :talla';
        $params[':talla'] = $talla;
    }
    $st = db()->prepare($sql . ' ORDER BY marca');
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

/** Búsqueda paginada. Devuelve [lista de prendas, total]. */
function buscar_prendas(?string $talla, string $q, string $marca, int $pagina, int $porPagina): array
{
    $where = [SQL_VISIBLE];
    $params = [':limite' => limite_vendidas()];
    if ($talla) {
        $where[] = 'talla = :talla';
        $params[':talla'] = $talla;
    }
    if ($marca !== '') {
        $where[] = 'marca = :marca';
        $params[':marca'] = $marca;
    }
    if ($q !== '') {
        $where[] = "(nombre LIKE :q1 ESCAPE '!' OR marca LIKE :q2 ESCAPE '!' OR codigo LIKE :q3 ESCAPE '!' OR descripcion LIKE :q4 ESCAPE '!')";
        $params[':q1'] = $params[':q2'] = $params[':q3'] = $params[':q4'] = like($q);
    }
    $condicion = implode(' AND ', $where);

    $st = db()->prepare("SELECT COUNT(*) FROM prendas WHERE $condicion");
    $st->execute($params);
    $total = (int) $st->fetchColumn();

    $offset = max(0, ($pagina - 1) * $porPagina);
    $st = db()->prepare("SELECT * FROM prendas WHERE $condicion ORDER BY " . SQL_ORDEN_PUBLICO . " LIMIT $porPagina OFFSET $offset");
    $st->execute($params);
    return [array_map('normaliza_prenda', $st->fetchAll()), $total];
}

function prenda_por_codigo(string $codigo): ?array
{
    $st = db()->prepare('SELECT * FROM prendas WHERE codigo = :c');
    $st->execute([':c' => strtoupper($codigo)]);
    $p = $st->fetch();
    return $p ? normaliza_prenda($p) : null;
}

function prenda_visible(array $p): bool
{
    return $p['estado'] !== 'vendido' || ($p['vendido_en'] !== null && $p['vendido_en'] >= limite_vendidas());
}

/* ---------- Alta y validación de prendas (formulario y carga masiva) ---------- */
const CAMPOS_PRENDA = ['nombre', 'marca', 'talla', 'precio', 'detalle', 'descripcion'];

function leer_campos_prenda(array $src): array
{
    $f = [];
    foreach (CAMPOS_PRENDA as $campo) {
        $f[$campo] = trim((string) ($src[$campo] ?? ''));
    }
    $f['talla'] = strtoupper($f['talla']);
    $f['precio'] = preg_replace('/[^\d]/', '', $f['precio']);
    return $f;
}

function validar_prenda(array $f): array
{
    $errores = [];
    if ($f['nombre'] === '' || mb_strlen($f['nombre']) > 120) {
        $errores[] = 'Escribe el nombre de la prenda (máximo 120 letras).';
    }
    if (!isset(TALLAS[$f['talla']])) {
        $errores[] = 'Elige la talla.';
    }
    if (mb_strlen($f['marca']) > 60 || mb_strlen($f['detalle']) > 60) {
        $errores[] = 'La marca y el detalle deben ser cortos (máximo 60 letras).';
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
        $errores[] = 'Agrega la foto de la prenda.';
    }
    return [$hayFoto, $errores];
}

/**
 * Da de alta una prenda nueva: la guarda, le asigna código (CH-0001) y procesa la foto.
 * Devuelve el código. Lanza RuntimeException (mensaje para la usuaria) o PDOException.
 */
function crear_prenda(array $f, string $rutaFoto): string
{
    $pdo = db();
    $ahora = ahora();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO prendas (nombre, marca, talla, precio, detalle, descripcion, foto, estado, creado_en, actualizado_en)
            VALUES (:nombre, :marca, :talla, :precio, :detalle, :descripcion, '', 'disponible', :creado, :act)")->execute([
            ':nombre' => $f['nombre'],
            ':marca' => $f['marca'],
            ':talla' => $f['talla'],
            ':precio' => $f['precio'] === '' ? null : (int) $f['precio'],
            ':detalle' => $f['detalle'],
            ':descripcion' => $f['descripcion'],
            ':creado' => $ahora,
            ':act' => $ahora,
        ]);
        $id = (int) $pdo->lastInsertId();
        $codigo = sprintf('%s-%04d', $f['talla'], $id);
        $foto = guardar_foto($rutaFoto, $codigo);
        $pdo->prepare('UPDATE prendas SET codigo = :c, foto = :f WHERE id = :id')
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

function marcas_sugeridas(): array
{
    return array_values(array_unique(array_merge(
        db()->query("SELECT DISTINCT marca FROM prendas WHERE marca <> '' ORDER BY marca")->fetchAll(PDO::FETCH_COLUMN),
        ['Nike', 'Adidas', 'Puma', 'Under Armour', 'Reebok', 'Lululemon', 'Fila', 'New Balance', 'Zara', 'H&M', 'Shein', 'Forever 21', 'Old Navy', 'Gap', "Levi's", 'Hollister', 'American Eagle', 'Bershka', 'Pull&Bear']
    )));
}
