<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../../app/bootstrap.php') ? __DIR__ . '/../../app/bootstrap.php' : __DIR__ . '/../app/bootstrap.php';
iniciar_sesion();

// Sólo funciona mientras no exista ninguna cuenta.
if ((int) db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0) {
    redirigir('/admin/entrar.php');
}

$error = '';
$usuario = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');
    $claveInstalacion = (string) ($_POST['clave_instalacion'] ?? '');

    if (cfg('clave_instalacion') === 'cambia-esta-clave' || !hash_equals((string) cfg('clave_instalacion'), $claveInstalacion)) {
        sleep(1);
        $error = 'La clave de instalación no es correcta (revisa app/config.php).';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $usuario)) {
        $error = 'El usuario debe tener de 3 a 40 letras o números, sin espacios.';
    } elseif (mb_strlen($clave) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        $id = crear_usuario($usuario, $clave);
        $st = db()->prepare('SELECT clave_hash FROM usuarios WHERE id = :id');
        $st->execute([':id' => $id]);
        abrir_sesion($id, $usuario, (string) $st->fetchColumn());
        aviso('¡Cuenta creada! Ya puedes subir prendas.');
        redirigir('/admin/');
    }
}

$titulo = 'Crear cuenta';
require APP_DIR . '/vistas/admin-encabezado.php';
?>
<form class="panel narrow" method="post" autocomplete="off">
  <?= csrf_campo() ?>
  <p class="hint">Este paso se hace una sola vez. Crea el usuario y la contraseña con los que se entrará al panel.</p>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <label>Clave de instalación
    <input type="password" name="clave_instalacion" required>
  </label>
  <label>Usuario
    <input name="usuario" value="<?= e($usuario) ?>" required autocapitalize="none" autocomplete="username">
  </label>
  <label>Contraseña (mínimo 8 caracteres)
    <input type="password" name="clave" minlength="8" required autocomplete="new-password">
  </label>
  <button class="btn primary block" type="submit">Crear cuenta</button>
</form>
<?php require APP_DIR . '/vistas/admin-pie.php';
