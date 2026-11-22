<?php
declare(strict_types=1);
// Recuperación de emergencia: poner una contraseña nueva cuando nadie puede entrar.
// Requiere la "clave_recuperacion" de app/config.php, que sólo ve quien administra el hosting.
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../../app/bootstrap.php') ? __DIR__ . '/../../app/bootstrap.php' : __DIR__ . '/../app/bootstrap.php';
iniciar_sesion();

$claveConfig = (string) cfg('clave_recuperacion', '');
$activa = mb_strlen($claveConfig) >= 12;
$error = '';
$usuario = '';

if ($activa && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');
    if (espera_intentos('recuperar') > 0) {
        $error = 'Demasiados intentos. Espera ' . espera_intentos('recuperar') . ' segundos.';
    } elseif (!hash_equals($claveConfig, (string) ($_POST['clave_recuperacion'] ?? ''))) {
        registrar_fallo('recuperar');
        $error = 'La clave de recuperación no es correcta.';
    } else {
        $st = db()->prepare('SELECT id, usuario FROM usuarios WHERE usuario = :u');
        $st->execute([':u' => $usuario]);
        $fila = $st->fetch();
        $errores = $fila ? validar_clave_nueva($clave, (string) ($_POST['confirmacion'] ?? '')) : ['Ese usuario no existe.'];
        if ($errores) {
            $error = implode(' ', $errores);
        } else {
            $hash = cambiar_clave((int) $fila['id'], $clave);
            abrir_sesion((int) $fila['id'], $fila['usuario'], $hash);
            aviso('Contraseña restablecida. Ya estás dentro del panel.');
            redirigir('/admin/');
        }
    }
}

$titulo = 'Recuperar acceso';
$volver = '/admin/entrar.php';
require APP_DIR . '/vistas/admin-encabezado.php';
?>
<?php if (!$activa): ?>
  <section class="panel narrow">
    <h2>Recuperación no activada</h2>
    <p class="hint">Si olvidaste tu contraseña, pide a otra persona con acceso al panel que te ponga una nueva desde <b>Mi cuenta y usuarios</b>.</p>
    <p class="hint">Si nadie puede entrar, quien administra el hosting debe agregar una <b>clave_recuperacion</b> (12 caracteres o más) en <code>app/config.php</code> y volver a esta página.</p>
  </section>
<?php else: ?>
  <form class="panel narrow" method="post" autocomplete="off">
    <?= csrf_campo() ?>
    <p class="hint">Escribe la clave de recuperación de <code>app/config.php</code>, el usuario y su contraseña nueva.</p>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <label>Clave de recuperación
      <input type="password" name="clave_recuperacion" required autocomplete="off">
    </label>
    <label>Usuario
      <input name="usuario" value="<?= e($usuario) ?>" required autocapitalize="none" autocomplete="username">
    </label>
    <label>Contraseña nueva (mínimo 8 caracteres)
      <input type="password" name="clave" minlength="8" required autocomplete="new-password">
    </label>
    <label>Repite la contraseña nueva
      <input type="password" name="confirmacion" minlength="8" required autocomplete="new-password">
    </label>
    <button class="btn primary block" type="submit">Restablecer y entrar</button>
  </form>
<?php endif; ?>
<?php require APP_DIR . '/vistas/admin-pie.php';
