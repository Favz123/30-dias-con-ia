<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../../app/bootstrap.php') ? __DIR__ . '/../../app/bootstrap.php' : __DIR__ . '/../app/bootstrap.php';
iniciar_sesion();

if (usuario_actual()) {
    redirigir('/admin/');
}
if ((int) db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() === 0) {
    redirigir('/admin/instalar.php');
}

$error = '';
$usuario = '';
$espera = max(0, (int) ($_SESSION['bloqueo_hasta'] ?? 0) - time());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');

    if ($espera > 0) {
        $error = "Demasiados intentos. Espera $espera segundos.";
    } else {
        $st = db()->prepare('SELECT id, usuario, clave_hash FROM usuarios WHERE usuario = :u');
        $st->execute([':u' => $usuario]);
        $fila = $st->fetch();
        if ($fila && password_verify($clave, $fila['clave_hash'])) {
            if (password_needs_rehash($fila['clave_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE usuarios SET clave_hash = :h WHERE id = :id')
                    ->execute([':h' => password_hash($clave, PASSWORD_DEFAULT), ':id' => $fila['id']]);
            }
            session_regenerate_id(true);
            unset($_SESSION['intentos'], $_SESSION['bloqueo_hasta']);
            $_SESSION['usuario'] = ['id' => (int) $fila['id'], 'usuario' => $fila['usuario']];
            redirigir('/admin/');
        }
        sleep(1); // frena a quien intente adivinar contraseñas
        $_SESSION['intentos'] = (int) ($_SESSION['intentos'] ?? 0) + 1;
        if ($_SESSION['intentos'] >= 5) {
            $_SESSION['bloqueo_hasta'] = time() + 60;
            $_SESSION['intentos'] = 0;
        }
        $error = 'Usuario o contraseña incorrectos.';
    }
}

$titulo = 'Entrar';
require APP_DIR . '/vistas/admin-encabezado.php';
?>
<form class="panel narrow" method="post">
  <?= csrf_campo() ?>
  <div class="login-logo"><img src="/assets/img/logo.webp" width="120" height="120" alt="En Busca de Closet"></div>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <label>Usuario
    <input name="usuario" value="<?= e($usuario) ?>" required autocapitalize="none" autocomplete="username">
  </label>
  <label>Contraseña
    <input type="password" name="clave" required autocomplete="current-password">
  </label>
  <button class="btn primary block" type="submit">Entrar</button>
</form>
<?php require APP_DIR . '/vistas/admin-pie.php';
