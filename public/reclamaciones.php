<?php
$pageTitle = 'Libro de reclamaciones - FDU';
$activePage = '';
$pageStyles = array();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Models/Reclamaciones.php';

$secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params(array(
  'httponly' => true,
  'samesite' => 'Lax',
  'secure' => $secureCookie,
));
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$errors = array();
$receipt = null;
$old = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : array();
$escape = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $submittedToken = $_POST['csrf_token'] ?? '';
  if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
    http_response_code(403);
    $errors[] = 'La sesión del formulario expiró. Recarga la página e inténtalo nuevamente.';
  }

  $data = array(
    'nombre' => trim((string) ($_POST['nombre'] ?? '')),
    'tipo_documento' => trim((string) ($_POST['tipo_documento'] ?? 'dni')),
    'numero_documento' => trim((string) ($_POST['numero_documento'] ?? '')),
    'domicilio' => trim((string) ($_POST['domicilio'] ?? '')),
    'telefono' => trim((string) ($_POST['telefono'] ?? '')),
    'correo' => trim((string) ($_POST['correo'] ?? '')),
    'tipo_bien' => trim((string) ($_POST['tipo_bien'] ?? '')),
    'fecha_compra' => trim((string) ($_POST['fecha_compra'] ?? '')),
    'bien_contratado' => trim((string) ($_POST['bien_contratado'] ?? '')),
    'monto_reclamado' => trim((string) ($_POST['monto_reclamado'] ?? '')),
    'tipo' => trim((string) ($_POST['tipo'] ?? '')),
    'detalle' => trim((string) ($_POST['detalle'] ?? '')),
    'pedido' => trim((string) ($_POST['pedido'] ?? '')),
    'acepta_datos' => isset($_POST['acepta_datos']) ? 1 : 0,
  );

  foreach (array('nombre', 'correo', 'bien_contratado', 'tipo', 'detalle', 'pedido') as $requiredField) {
    if ($data[$requiredField] === '') {
      $errors[] = 'Completa todos los campos obligatorios.';
      break;
    }
  }
  $maxLengths = array(
    'nombre' => 180,
    'tipo_documento' => 20,
    'numero_documento' => 20,
    'domicilio' => 250,
    'telefono' => 30,
    'correo' => 180,
    'bien_contratado' => 250,
    'detalle' => 3000,
    'pedido' => 1500,
  );
  foreach ($maxLengths as $field => $maxLength) {
    if (mb_strlen($data[$field], 'UTF-8') > $maxLength) {
      $errors[] = 'Uno o más campos superan la longitud permitida.';
      break;
    }
  }
  if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Ingresa un correo electrónico válido.';
  }
  if (!in_array($data['tipo_documento'], array('dni', 'ce', 'pasaporte', 'otro'), true)) {
    $errors[] = 'Selecciona un tipo de documento válido.';
  }
  if (!in_array($data['tipo_bien'], array('producto', 'servicio'), true)) {
    $errors[] = 'Selecciona si se trata de un producto o un servicio.';
  }
  if (!in_array($data['tipo'], array('reclamo', 'queja'), true)) {
    $errors[] = 'Selecciona si presentarás un reclamo o una queja.';
  }
  if ($data['fecha_compra'] !== '') {
    $date = DateTime::createFromFormat('Y-m-d', $data['fecha_compra']);
    if (!$date || $date->format('Y-m-d') !== $data['fecha_compra']) {
      $errors[] = 'La fecha de compra o contratación no es válida.';
    }
  }
  if ($data['monto_reclamado'] !== '' && (!is_numeric($data['monto_reclamado']) || (float) $data['monto_reclamado'] < 0)) {
    $errors[] = 'El monto reclamado debe ser un número igual o mayor a cero.';
  }
  if ($data['monto_reclamado'] !== '' && is_numeric($data['monto_reclamado']) && (float) $data['monto_reclamado'] > 9999999999.99) {
    $errors[] = 'El monto reclamado supera el máximo permitido.';
  }
  if (!$data['acepta_datos']) {
    $errors[] = 'Confirma el aviso de tratamiento de datos para continuar.';
  }

  if (!$errors) {
    try {
      $database = new Database();
      $model = new Reclamaciones($database->getConnection());
      $folio = $model->create($data);
      $_SESSION['reclamaciones_receipt'] = array(
        'folio' => $folio,
        'created_at' => date('d/m/Y H:i'),
        'data' => $data,
      );
      header('Location: reclamaciones?registrada=1', true, 303);
      exit;
    } catch (Throwable $exception) {
      error_log('No se pudo registrar el reclamo: ' . $exception->getMessage());
      $errors[] = 'No se pudo registrar la solicitud. Inténtalo nuevamente o comunícate con la institución.';
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['registrada'])) {
  $receipt = $_SESSION['reclamaciones_receipt'] ?? null;
  unset($_SESSION['reclamaciones_receipt']);
}

require __DIR__ . '/includes/header.php';
?>
<main class="section">
  <div class="container complaints-page">
    <h1 class="page-title">Libro de reclamaciones</h1>
    <p class="section-subtitle">Registra una disconformidad relacionada con un producto, servicio o atención recibida.</p>
    <section class="complaints-provider" aria-labelledby="complaintsProviderTitle">
      <h2 id="complaintsProviderTitle">Identificación del proveedor</h2>
      <p><strong>Razón social:</strong> Fundación para el Desarrollo Universitario de Lima Sur</p>
      <p><strong>RUC:</strong> 20616186001</p>
      <p><strong>Domicilio:</strong> Av. Bolivar S/N, sector 3 grupo 1, mz. A, sublote 3, Villa El Salvador</p>
      <p><strong>Correo de contacto:</strong> informes@fundaciondu.org</p>
    </section>
    <div class="complaints-legal-note" role="note">
      <p>El proveedor debe responder el reclamo o la queja en un plazo máximo de 15 días hábiles.</p>
      <p>Presentar un reclamo o una queja no impide acudir a otras vías de solución de controversias.</p>
    </div>
    <?php if ($errors): ?>
      <div class="complaints-errors" role="alert">
        <?php foreach (array_unique($errors) as $error): ?><p><?= $escape($error) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($receipt): ?>
      <section class="complaints-receipt" aria-labelledby="receiptTitle">
        <h2 id="receiptTitle">Constancia de registro</h2>
        <p>Tu solicitud fue registrada el <?= $escape($receipt['created_at']) ?>.</p>
        <p>Folio: <strong><?= $escape($receipt['folio']) ?></strong></p>
        <p>Conserva este folio para consultar tu solicitud. El plazo de respuesta es de hasta 15 días hábiles.</p>
        <dl>
          <dt>Proveedor</dt><dd>Fundación para el Desarrollo Universitario de Lima Sur</dd>
          <dt>RUC del proveedor</dt><dd>20616186001</dd>
          <dt>Persona reclamante</dt><dd><?= $escape($receipt['data']['nombre']) ?></dd>
          <dt>Documento</dt><dd><?= $escape(strtoupper($receipt['data']['tipo_documento'])) ?> <?= $escape($receipt['data']['numero_documento'] ?: 'No consignado') ?></dd>
          <dt>Domicilio</dt><dd><?= $escape($receipt['data']['domicilio'] ?: 'No consignado') ?></dd>
          <dt>Teléfono</dt><dd><?= $escape($receipt['data']['telefono'] ?: 'No consignado') ?></dd>
          <dt>Correo</dt><dd><?= $escape($receipt['data']['correo']) ?></dd>
          <dt>Tipo</dt><dd><?= $receipt['data']['tipo'] === 'reclamo' ? 'Reclamo' : 'Queja' ?></dd>
          <dt>Tipo de bien</dt><dd><?= $receipt['data']['tipo_bien'] === 'producto' ? 'Producto' : 'Servicio' ?></dd>
          <dt>Bien o servicio</dt><dd><?= $escape($receipt['data']['bien_contratado']) ?></dd>
          <dt>Fecha de compra/contratación</dt><dd><?= $escape($receipt['data']['fecha_compra'] ?: 'No consignada') ?></dd>
          <dt>Monto reclamado</dt><dd><?= $receipt['data']['monto_reclamado'] !== '' ? 'S/ ' . $escape($receipt['data']['monto_reclamado']) : 'No consignado' ?></dd>
          <dt>Detalle</dt><dd><?= nl2br($escape($receipt['data']['detalle'])) ?></dd>
          <dt>Pedido</dt><dd><?= nl2br($escape($receipt['data']['pedido'])) ?></dd>
        </dl>
        <button class="btn btn-secondary" type="button" data-print-button>Imprimir constancia</button>
      </section>
    <?php else: ?>
    <form class="complaints-form" method="post" action="reclamaciones">
      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
      <fieldset>
        <legend>Datos de la persona reclamante</legend>
        <label>Nombre completo<input type="text" name="nombre" autocomplete="name" maxlength="180" value="<?= $escape($old['nombre'] ?? '') ?>" required></label>
        <label>Tipo de documento
          <select name="tipo_documento">
            <option value="dni" <?= ($old['tipo_documento'] ?? 'dni') === 'dni' ? 'selected' : '' ?>>DNI</option>
            <option value="ce" <?= ($old['tipo_documento'] ?? '') === 'ce' ? 'selected' : '' ?>>Carné de extranjería</option>
            <option value="pasaporte" <?= ($old['tipo_documento'] ?? '') === 'pasaporte' ? 'selected' : '' ?>>Pasaporte</option>
            <option value="otro" <?= ($old['tipo_documento'] ?? '') === 'otro' ? 'selected' : '' ?>>Otro</option>
          </select>
        </label>
        <label>Número de documento<input type="text" name="numero_documento" autocomplete="off" maxlength="20" value="<?= $escape($old['numero_documento'] ?? '') ?>"></label>
        <label>Teléfono de contacto<input type="tel" name="telefono" autocomplete="tel" maxlength="30" value="<?= $escape($old['telefono'] ?? '') ?>"></label>
        <label class="complaints-field-full">Domicilio<input type="text" name="domicilio" autocomplete="street-address" maxlength="250" value="<?= $escape($old['domicilio'] ?? '') ?>"></label>
        <label class="complaints-field-full">Correo electrónico<input type="email" name="correo" autocomplete="email" maxlength="180" value="<?= $escape($old['correo'] ?? '') ?>" required></label>
      </fieldset>

      <fieldset>
        <legend>Identificación del bien contratado</legend>
        <label>Tipo
          <select name="tipo_bien" required>
            <option value="producto" <?= ($old['tipo_bien'] ?? 'producto') === 'producto' ? 'selected' : '' ?>>Producto</option>
            <option value="servicio" <?= ($old['tipo_bien'] ?? '') === 'servicio' ? 'selected' : '' ?>>Servicio</option>
          </select>
        </label>
        <label>Fecha de compra o contratación<input type="date" name="fecha_compra" value="<?= $escape($old['fecha_compra'] ?? '') ?>"></label>
        <label class="complaints-field-full">Producto o servicio<input type="text" name="bien_contratado" maxlength="250" value="<?= $escape($old['bien_contratado'] ?? '') ?>" required></label>
        <label>Monto reclamado (S/)<input type="number" name="monto_reclamado" min="0" step="0.01" inputmode="decimal" value="<?= $escape($old['monto_reclamado'] ?? '') ?>"></label>
      </fieldset>

      <fieldset>
        <legend>Detalle de la reclamación</legend>
        <label class="complaints-field-full">Tipo de disconformidad
          <select name="tipo" required>
            <option value="reclamo" <?= ($old['tipo'] ?? 'reclamo') === 'reclamo' ? 'selected' : '' ?>>Reclamo: disconformidad relacionada con un producto o servicio</option>
            <option value="queja" <?= ($old['tipo'] ?? '') === 'queja' ? 'selected' : '' ?>>Queja: disconformidad relacionada con la atención al público</option>
          </select>
        </label>
        <label class="complaints-field-full">Detalle de los hechos<textarea name="detalle" rows="6" maxlength="3000" required><?= $escape($old['detalle'] ?? '') ?></textarea></label>
        <label class="complaints-field-full">Pedido de la persona consumidora<textarea name="pedido" rows="4" maxlength="1500" required><?= $escape($old['pedido'] ?? '') ?></textarea></label>
      </fieldset>

      <label class="complaints-data-notice"><input type="checkbox" name="acepta_datos" value="1" <?= isset($old['acepta_datos']) ? 'checked' : '' ?> required> Declaro que los datos proporcionados son correctos y autorizo su uso para gestionar y responder esta reclamación o queja.</label>
      <button class="btn btn-primary" type="submit">Registrar reclamación</button>
    </form>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>