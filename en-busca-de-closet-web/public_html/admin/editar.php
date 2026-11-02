<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../../app/bootstrap.php') ? __DIR__ . '/../../app/bootstrap.php' : __DIR__ . '/../app/bootstrap.php';
requiere_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$volver = (string) ($_GET['volver'] ?? $_POST['volver'] ?? '/admin/');
if (!preg_match('#^/admin/[^\s]*$#', $volver) || str_starts_with($volver, '//')) {
    $volver = '/admin/';
}

$p = null;
if ($id) {
    $st = db()->prepare('SELECT * FROM prendas WHERE id = :id');
    $st->execute([':id' => $id]);
    $p = $st->fetch() ?: null;
    if (!$p) {
        aviso('No se encontró la prenda.', 'error');
        redirigir('/admin/');
    }
    $p = normaliza_prenda($p);
}

// Valores del formulario: los de la prenda, o los recordados de la última que se subió.
$f = [
    'nombre' => $p['nombre'] ?? '',
    'marca' => $p['marca'] ?? ($_SESSION['ultima_marca'] ?? ''),
    'talla' => $p['talla'] ?? ($_SESSION['ultima_talla'] ?? ''),
    'precio' => isset($p['precio']) ? (string) $p['precio'] : '',
    'detalle' => $p['detalle'] ?? '',
    'descripcion' => $p['descripcion'] ?? '',
];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    foreach ($f as $campo => $_) {
        $f[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }
    $f['precio'] = preg_replace('/[^\d]/', '', $f['precio']);

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

    $archivo = $_FILES['foto'] ?? null;
    $hayFoto = $archivo && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($hayFoto && $archivo['error'] !== UPLOAD_ERR_OK) {
        $errores[] = in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'La foto pesa demasiado. Intenta con otra.'
            : 'No se pudo subir la foto. Intenta de nuevo.';
    } elseif ($hayFoto && !is_uploaded_file($archivo['tmp_name'])) {
        $errores[] = 'No se pudo subir la foto. Intenta de nuevo.';
    }
    if (!$p && !$hayFoto) {
        $errores[] = 'Agrega la foto de la prenda.';
    }

    if (!$errores) {
        $ahora = ahora();
        $datos = [
            ':nombre' => $f['nombre'],
            ':marca' => $f['marca'],
            ':talla' => $f['talla'],
            ':precio' => $f['precio'] === '' ? null : (int) $f['precio'],
            ':detalle' => $f['detalle'],
            ':descripcion' => $f['descripcion'],
            ':act' => $ahora,
        ];
        $pdo = db();
        try {
            if ($p) {
                $nuevaFoto = $hayFoto ? guardar_foto($archivo['tmp_name'], $p['codigo']) : null;
                $sql = 'UPDATE prendas SET nombre = :nombre, marca = :marca, talla = :talla, precio = :precio,
                        detalle = :detalle, descripcion = :descripcion, actualizado_en = :act'
                    . ($nuevaFoto ? ', foto = :foto' : '') . ' WHERE id = :id';
                $datos[':id'] = $p['id'];
                if ($nuevaFoto) {
                    $datos[':foto'] = $nuevaFoto;
                }
                $pdo->prepare($sql)->execute($datos);
                if ($nuevaFoto) {
                    borrar_foto($p['foto']);
                }
                aviso("{$p['codigo']} actualizada.");
                redirigir($volver);
            }

            // Prenda nueva: se guarda, se le asigna código (CH-0001) y se procesa la foto.
            $pdo->beginTransaction();
            $datos[':creado'] = $ahora;
            $pdo->prepare("INSERT INTO prendas (nombre, marca, talla, precio, detalle, descripcion, foto, estado, creado_en, actualizado_en)
                VALUES (:nombre, :marca, :talla, :precio, :detalle, :descripcion, '', 'disponible', :creado, :act)")->execute($datos);
            $nuevoId = (int) $pdo->lastInsertId();
            $codigo = sprintf('%s-%04d', $f['talla'], $nuevoId);
            $foto = guardar_foto($archivo['tmp_name'], $codigo);
            $pdo->prepare('UPDATE prendas SET codigo = :c, foto = :f WHERE id = :id')
                ->execute([':c' => $codigo, ':f' => $foto, ':id' => $nuevoId]);
            $pdo->commit();

            $_SESSION['ultima_talla'] = $f['talla'];
            $_SESSION['ultima_marca'] = $f['marca'];
            aviso("¡Listo! $codigo ya está en el catálogo.");
            redirigir(isset($_POST['otra']) ? '/admin/editar.php' : '/admin/');
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Closet: ' . $ex->getMessage());
            $errores[] = 'No se pudo guardar. Intenta de nuevo en un momento.';
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = $ex->getMessage();
        }
    }
}

$marcasSugeridas = array_values(array_unique(array_merge(
    db()->query("SELECT DISTINCT marca FROM prendas WHERE marca <> '' ORDER BY marca")->fetchAll(PDO::FETCH_COLUMN),
    ['Nike', 'Adidas', 'Puma', 'Under Armour', 'Reebok', 'Lululemon', 'Fila', 'New Balance', 'Zara', 'H&M', 'Shein', 'Forever 21', 'Old Navy', 'Gap', "Levi's", 'Hollister', 'American Eagle', 'Bershka', 'Pull&Bear']
)));

$titulo = $p ? 'Editar ' . $p['codigo'] : 'Nueva prenda';
require APP_DIR . '/vistas/admin-encabezado.php';
?>
<form class="panel" method="post" enctype="multipart/form-data" data-foto-form>
  <?= csrf_campo() ?>
  <input type="hidden" name="volver" value="<?= e($volver) ?>">
  <?php if ($p): ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><?php endif; ?>

  <?php if ($errores): ?>
    <div class="error"><?php foreach ($errores as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
  <?php endif; ?>

  <label class="photo-pick">
    <img data-preview src="<?= $p ? e(foto_url($p)) : '' ?>" alt=""<?= $p ? '' : ' hidden' ?>>
    <span class="photo-cta" data-photo-cta><?= $p ? '📷 Cambiar foto' : '📷 Tomar o elegir foto' ?></span>
    <input type="file" name="foto" accept="image/*" data-foto<?= $p ? '' : ' required' ?>>
  </label>
  <p class="hint" data-foto-estado></p>

  <fieldset class="sizes">
    <legend>Talla</legend>
    <?php foreach (TALLAS as $clave => $t): ?>
      <label><input type="radio" name="talla" value="<?= e($clave) ?>" required<?= $f['talla'] === $clave ? ' checked' : '' ?>><span><b><?= e($clave) ?></b><?= e($t['nombre']) ?></span></label>
    <?php endforeach; ?>
  </fieldset>

  <label>Nombre de la prenda
    <input name="nombre" value="<?= e($f['nombre']) ?>" maxlength="120" required placeholder="Ej. Short Nike negro con rosa">
  </label>

  <div class="two">
    <label>Marca
      <input name="marca" value="<?= e($f['marca']) ?>" maxlength="60" list="marcas" placeholder="Nike">
      <datalist id="marcas"><?php foreach ($marcasSugeridas as $m): ?><option value="<?= e($m) ?>"><?php endforeach; ?></datalist>
    </label>
    <label>Precio ($)
      <input name="precio" value="<?= e($f['precio']) ?>" inputmode="numeric" pattern="[0-9,.$ ]*" placeholder="350">
    </label>
  </div>

  <label>Detalle destacado <small>(opcional)</small>
    <input name="detalle" value="<?= e($f['detalle']) ?>" maxlength="60" list="detalles" placeholder="Ej. Copas removibles">
    <datalist id="detalles"><option value="Copas removibles"><option value="Con licra interior"><option value="Nueva con etiqueta"><option value="Como nueva"><option value="Con bolsillos"></datalist>
  </label>

  <label>Descripción <small>(opcional)</small>
    <textarea name="descripcion" rows="3" maxlength="1000" placeholder="Color, tela, medidas, detalles…"><?= e($f['descripcion']) ?></textarea>
  </label>

  <div class="actions">
    <button class="btn primary block" type="submit" data-enviar>Guardar</button>
    <?php if (!$p): ?>
      <button class="btn block" type="submit" name="otra" value="1" data-enviar>Guardar y subir otra</button>
    <?php endif; ?>
  </div>
</form>

<?php if ($p): ?>
  <section class="panel danger-zone">
    <h2>Estado actual: <?= e(ESTADOS[$p['estado']]) ?></h2>
    <form method="post" action="/admin/accion.php" class="states wide">
      <?= csrf_campo() ?>
      <input type="hidden" name="id" value="<?= $p['id'] ?>">
      <input type="hidden" name="volver" value="<?= e($volver) ?>">
      <button name="accion" value="disponible" class="st st-disp"<?= $p['estado'] === 'disponible' ? ' aria-pressed="true"' : '' ?>>Disponible</button>
      <button name="accion" value="apartado" class="st st-apar"<?= $p['estado'] === 'apartado' ? ' aria-pressed="true"' : '' ?>>Apartada</button>
      <button name="accion" value="vendido" class="st st-vend"<?= $p['estado'] === 'vendido' ? ' aria-pressed="true"' : '' ?>>Vendida</button>
    </form>
    <?php if ($p['estado'] === 'vendido' && prenda_visible($p)): ?>
      <form method="post" action="/admin/accion.php">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <input type="hidden" name="volver" value="<?= e($volver) ?>">
        <button class="btn block" name="accion" value="ocultar">Quitar del catálogo ya (sin esperar <?= (int) cfg('dias_vendido', 3) ?> días)</button>
      </form>
    <?php endif; ?>
    <p class="hint"><a href="<?= e(prenda_url($p)) ?>" target="_blank" rel="noopener">Ver cómo se ve en la tienda ↗</a></p>
    <form method="post" action="/admin/accion.php" data-confirm="¿Borrar <?= e($p['codigo']) ?> para siempre? Esto no se puede deshacer.">
      <?= csrf_campo() ?>
      <input type="hidden" name="id" value="<?= $p['id'] ?>">
      <input type="hidden" name="volver" value="/admin/">
      <button class="btn danger block" name="accion" value="borrar">Borrar prenda</button>
    </form>
  </section>
<?php endif; ?>
<?php require APP_DIR . '/vistas/admin-pie.php';
