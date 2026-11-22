<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../../app/bootstrap.php') ? __DIR__ . '/../../app/bootstrap.php' : __DIR__ . '/../app/bootstrap.php';
requiere_login();

$yo = usuario_actual();
$errores = [];   // errores por formulario: ['mia' => [...], 'otro-3' => [...], 'agregar' => [...]]
$nuevoUsuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = (string) ($_POST['accion'] ?? '');
    $clave = (string) ($_POST['clave'] ?? '');
    $confirmacion = (string) ($_POST['confirmacion'] ?? '');

    if ($accion === 'mia') {
        // Cambiar mi propia contraseña: pide la actual.
        $st = db()->prepare('SELECT clave_hash FROM usuarios WHERE id = :id');
        $st->execute([':id' => $yo['id']]);
        $hashActual = (string) $st->fetchColumn();
        if (espera_intentos('cuenta') > 0) {
            $errores['mia'] = ['Demasiados intentos. Espera ' . espera_intentos('cuenta') . ' segundos.'];
        } elseif (!password_verify((string) ($_POST['actual'] ?? ''), $hashActual)) {
            registrar_fallo('cuenta');
            $errores['mia'] = ['La contraseña actual no es correcta.'];
        } else {
            $errores['mia'] = validar_clave_nueva($clave, $confirmacion);
            if (!$errores['mia']) {
                $hash = cambiar_clave($yo['id'], $clave);
                abrir_sesion($yo['id'], $yo['usuario'], $hash); // esta sesión sigue; las demás se cierran
                aviso('Tu contraseña cambió. En otros dispositivos tendrás que volver a entrar.');
                redirigir('/admin/cuenta.php');
            }
        }
    } elseif ($accion === 'otro') {
        // Poner una contraseña nueva a otra persona (por ejemplo, si la olvidó).
        $id = (int) ($_POST['id'] ?? 0);
        $st = db()->prepare('SELECT usuario FROM usuarios WHERE id = :id');
        $st->execute([':id' => $id]);
        $nombre = $st->fetchColumn();
        if ($nombre === false || $id === $yo['id']) {
            aviso('No se encontró el usuario.', 'error');
            redirigir('/admin/cuenta.php');
        }
        $errores["otro-$id"] = validar_clave_nueva($clave, $confirmacion);
        if (!$errores["otro-$id"]) {
            cambiar_clave($id, $clave);
            aviso("Listo: $nombre ya puede entrar con su contraseña nueva. Su sesión anterior se cerró.");
            redirigir('/admin/cuenta.php');
        }
    } elseif ($accion === 'agregar') {
        $nuevoUsuario = trim((string) ($_POST['usuario'] ?? ''));
        $errores['agregar'] = array_merge(validar_usuario_nuevo($nuevoUsuario), validar_clave_nueva($clave, $confirmacion));
        if (!$errores['agregar']) {
            crear_usuario($nuevoUsuario, $clave);
            aviso("Usuario $nuevoUsuario creado.");
            redirigir('/admin/cuenta.php');
        }
    } elseif ($accion === 'borrar') {
        $id = (int) ($_POST['id'] ?? 0);
        $total = (int) db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        if ($id === $yo['id'] || $total <= 1) {
            aviso('No puedes borrar tu propia cuenta ni la última que queda.', 'error');
        } else {
            db()->prepare('DELETE FROM usuarios WHERE id = :id')->execute([':id' => $id]);
            aviso('Usuario borrado. Ya no puede entrar al panel.');
        }
        redirigir('/admin/cuenta.php');
    }
}

$usuarios = db()->query('SELECT id, usuario, creado_en FROM usuarios ORDER BY id')->fetchAll();

function lista_errores(array $errores, string $clave): string
{
    if (empty($errores[$clave])) {
        return '';
    }
    $html = '<div class="error">';
    foreach ($errores[$clave] as $err) {
        $html .= '<p>' . e($err) . '</p>';
    }
    return $html . '</div>';
}

$titulo = 'Mi cuenta y usuarios';
$volver = '/admin/';
require APP_DIR . '/vistas/admin-encabezado.php';
?>
<form class="panel" method="post" autocomplete="off">
  <?= csrf_campo() ?>
  <input type="hidden" name="accion" value="mia">
  <h2>Cambiar mi contraseña</h2>
  <p class="hint">Estás usando la cuenta <b><?= e($yo['usuario']) ?></b>.</p>
  <?= lista_errores($errores, 'mia') ?>
  <label>Contraseña actual
    <input type="password" name="actual" required autocomplete="current-password">
  </label>
  <label>Contraseña nueva (mínimo 8 caracteres)
    <input type="password" name="clave" minlength="8" required autocomplete="new-password">
  </label>
  <label>Repite la contraseña nueva
    <input type="password" name="confirmacion" minlength="8" required autocomplete="new-password">
  </label>
  <button class="btn primary block" type="submit">Guardar contraseña nueva</button>
</form>

<section class="panel">
  <h2>Personas con acceso al panel</h2>
  <p class="hint">Si alguien olvidó su contraseña, ponle una nueva aquí y díselo en persona o por WhatsApp.</p>
  <ul class="users">
    <?php foreach ($usuarios as $u): $uid = (int) $u['id']; ?>
      <li>
        <div class="user-row">
          <span><b><?= e($u['usuario']) ?></b><?= $uid === $yo['id'] ? ' <small>(tú)</small>' : '' ?><br><small>Desde <?= e(date('d/m/Y', strtotime($u['creado_en']))) ?></small></span>
        </div>
        <?php if ($uid !== $yo['id']): ?>
          <details<?= !empty($errores["otro-$uid"]) ? ' open' : '' ?>>
            <summary>Poner contraseña nueva</summary>
            <form method="post" autocomplete="off" class="mini-form">
              <?= csrf_campo() ?>
              <input type="hidden" name="accion" value="otro">
              <input type="hidden" name="id" value="<?= $uid ?>">
              <?= lista_errores($errores, "otro-$uid") ?>
              <input type="password" name="clave" minlength="8" required placeholder="Contraseña nueva" autocomplete="new-password" aria-label="Contraseña nueva para <?= e($u['usuario']) ?>">
              <input type="password" name="confirmacion" minlength="8" required placeholder="Repítela" autocomplete="new-password" aria-label="Repite la contraseña">
              <button class="btn primary block" type="submit">Guardar para <?= e($u['usuario']) ?></button>
            </form>
            <form method="post" data-confirm="¿Quitar el acceso de <?= e($u['usuario']) ?> al panel?">
              <?= csrf_campo() ?>
              <input type="hidden" name="accion" value="borrar">
              <input type="hidden" name="id" value="<?= $uid ?>">
              <button class="btn danger block" type="submit">Quitar acceso</button>
            </form>
          </details>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<form class="panel" method="post" autocomplete="off">
  <?= csrf_campo() ?>
  <input type="hidden" name="accion" value="agregar">
  <h2>Agregar una persona</h2>
  <p class="hint">Por ejemplo, la cuenta de quien administra el sitio, para que pueda ayudar si se olvida una contraseña.</p>
  <?= lista_errores($errores, 'agregar') ?>
  <label>Usuario
    <input name="usuario" value="<?= e($nuevoUsuario) ?>" required autocapitalize="none" autocomplete="off">
  </label>
  <label>Contraseña (mínimo 8 caracteres)
    <input type="password" name="clave" minlength="8" required autocomplete="new-password">
  </label>
  <label>Repite la contraseña
    <input type="password" name="confirmacion" minlength="8" required autocomplete="new-password">
  </label>
  <button class="btn block" type="submit">Agregar</button>
</form>
<?php require APP_DIR . '/vistas/admin-pie.php';
