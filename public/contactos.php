<?php
$pageTitle = 'Contactos - FDU';
$activePage = 'contactos';
$pageStyles = array('assets/css/contactos-page.css');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/mail.php';
require_once dirname(__DIR__) . '/config/recaptcha.php';
require_once dirname(__DIR__) . '/app/Models/Mensajes.php';

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
$sent = false;
$mailWarning = false;
$old = array('nombre' => '', 'correo' => '', 'telefono' => '', 'asunto' => '', 'mensaje' => '');
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
        'correo' => trim((string) ($_POST['correo'] ?? '')),
        'telefono' => trim((string) ($_POST['telefono'] ?? '')),
        'asunto' => trim((string) ($_POST['asunto'] ?? '')),
        'mensaje' => trim((string) ($_POST['mensaje'] ?? '')),
    );
    $old = $data;

    // Campo trampa: los robots llenan todo, las personas lo dejan vacio.
    if (trim((string) ($_POST['sitio_web'] ?? '')) !== '') {
        $errors[] = 'No se pudo enviar el mensaje.';
    }

    foreach (array('nombre', 'correo', 'mensaje') as $requiredField) {
        if ($data[$requiredField] === '') {
            $errors[] = 'Completa todos los campos obligatorios.';
            break;
        }
    }

    $maxLengths = array(
        'nombre' => 180,
        'correo' => 180,
        'telefono' => 30,
        'asunto' => 120,
        'mensaje' => 2000,
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
    if ($data['telefono'] !== '' && !preg_match('/^[0-9 +()-]{6,30}$/', $data['telefono'])) {
        $errors[] = 'Ingresa un teléfono válido.';
    }

    if (!$errors) {
      $captchaResponse = $_POST['g-recaptcha-response'] ?? '';
      $remoteIp = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : null;
      if (!is_string($captchaResponse) || !verifyRecaptcha($captchaResponse, $remoteIp)) {
        $errors[] = 'Confirma que no eres un robot para enviar el mensaje.';
      }
    }

    if (!$errors) {
        try {
            $database = new Database();
            $model = new Mensajes($database->getConnection());
            $model->create($data);
            $emailSent = sendContactNotification(
              $data['nombre'],
              $data['correo'],
              $data['telefono'],
              $data['asunto'],
              $data['mensaje']
            );
            $sent = true;
            $mailWarning = !$emailSent;
            $old = array('nombre' => '', 'correo' => '', 'telefono' => '', 'asunto' => '', 'mensaje' => '');
        } catch (Throwable $throwable) {
            $errors[] = 'Ocurrió un problema al guardar el mensaje. Inténtalo nuevamente.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<main class="contact-page-shell">
  <div class="container contact-page-inner">
    <section class="contact-form-panel" aria-labelledby="contact-form-heading">
      <h1 id="contact-form-heading" class="contact-form-title">Formulario de Consultas</h1>
      <p class="contact-form-subtitle">Déjanos tus datos y un asesor se comunicará contigo hoy mismo.</p>

      <?php if ($sent): ?>
        <div class="contact-alert <?= $mailWarning ? 'contact-alert-error' : 'contact-alert-success' ?>" role="status">
          <strong><?= $mailWarning ? 'Consulta guardada' : '¡Consulta enviada!' ?></strong>
          <p><?= $mailWarning ? 'Guardamos tu consulta, pero el servidor no aceptó la notificación por correo a ' . $escape(CONTACT_DESTINATION_EMAIL) . '. Si necesitas una respuesta urgente, escríbenos directamente.' : 'Gracias por escribirnos. Recibimos tu consulta y nuestro equipo responderá al correo que indicaste.' ?></p>
        </div>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="contact-alert contact-alert-error" role="alert">
          <strong>Revisa el formulario:</strong>
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= $escape($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form class="contact-form" action="contactos" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
        <div class="contact-hp" aria-hidden="true">
          <label for="sitio_web">No completar este campo</label>
          <input id="sitio_web" name="sitio_web" type="text" tabindex="-1" autocomplete="off">
        </div>

        <div class="contact-form-row">
          <div class="contact-form-field">
            <label for="nombre">Nombre Completo <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="nombre" name="nombre" type="text" maxlength="180" required autocomplete="name"
                   placeholder="Ej. Juan Pérez" value="<?= $escape($old['nombre']) ?>"
                   <?= $errors ? 'aria-invalid="true"' : '' ?>>
          </div>

          <div class="contact-form-field">
            <label for="correo">Correo Electrónico <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="correo" name="correo" type="email" maxlength="180" required autocomplete="email"
                   placeholder="Ej. juan.perez@email.com" value="<?= $escape($old['correo']) ?>"
                   <?= $errors ? 'aria-invalid="true"' : '' ?>>
          </div>
        </div>

        <div class="contact-form-row">
          <div class="contact-form-field">
            <label for="telefono">Teléfono / Celular</label>
            <input id="telefono" name="telefono" type="tel" maxlength="30" autocomplete="tel"
                   placeholder="Ej. 987 654 321" value="<?= $escape($old['telefono']) ?>">
          </div>

          <div class="contact-form-field">
            <label for="asunto">Asunto</label>
            <select id="asunto" name="asunto">
              <?php
              $asuntos = array(
                  '' => 'Selecciona un tema',
                  'admision' => 'Admisión y matrícula',
                  'programas' => 'Programas y especializaciones',
                  'costos' => 'Costos y pagos',
                  'certificados' => 'Certificados',
                  'otro' => 'Otro tema',
              );
              foreach ($asuntos as $value => $label): ?>
                <option value="<?= $escape($value) ?>" <?= ($old['asunto'] === $value) ? 'selected' : '' ?>><?= $escape($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="contact-form-field">
          <label for="mensaje">Mensaje <span class="required-mark" aria-hidden="true">*</span></label>
          <textarea id="mensaje" name="mensaje" rows="6" maxlength="2000" required
                    placeholder="Escribe tus preguntas sobre ciclos, costos o sedes..."
                    <?= $errors ? 'aria-invalid="true"' : '' ?>><?= $escape($old['mensaje']) ?></textarea>
        </div>

        <div class="contact-captcha">
          <div class="g-recaptcha" data-sitekey="<?= $escape(getRecaptchaSiteKey()) ?>"></div>
        </div>

        <button type="submit" class="contact-submit">Enviar Mensaje</button>
      </form>
    </section>

    <aside class="contact-info-panel" aria-label="Información de contacto">
      <div class="contact-info-card">
        <h2 class="info-section-title">Información de contacto</h2>

        <div class="info-item">
          <div class="info-icon"><img src="assets/icons/place.svg" alt="" aria-hidden="true"></div>
          <div class="info-content">
            <h3>Dirección</h3>
            <p>Campus - Sector 3 Grupo 1A 03, Cercado (Av. Central y Av. Bolívar) Villa El Salvador.</p>
          </div>
        </div>

        <div class="info-item">
          <div class="info-icon"><img src="assets/icons/phone.svg" alt="" aria-hidden="true"></div>
          <div class="info-content">
            <h3>Teléfono</h3>
            <p>916 330 009</p>
          </div>
        </div>

        <div class="info-item">
          <div class="info-icon"><img src="assets/icons/email.svg" alt="" aria-hidden="true"></div>
          <div class="info-content">
            <h3>Correo electrónico</h3>
            <p>informes@fundaciondu.org</p>
          </div>
        </div>
      </div>

      <div class="map-card">
        <div class="map-card-header">
          <h2 class="map-card-title">Localización de la sede</h2>
        </div>

        <iframe class="map-frame" 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3899.50626669385!2d-76.93259619999999!3d-12.213961900000001!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9105b9588abb2853%3A0x4bae8a2bca307eb6!2sUniversidad%20Nacional%20Tecnol%C3%B3gica%20de%20Lima%20Sur!5e0!3m2!1ses!2spe!4v1790815613262!5m2!1ses!2spe"        
                loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Mapa de ubicación de la sede"></iframe>

        <a class="map-link" href="https://maps.google.com/?q=Villa%20El%20Salvador%20Lima%20Peru" target="_blank" rel="noopener noreferrer">Abrir en Maps</a>
      </div>
    </aside>
  </div>
</main>
<script src="https://www.google.com/recaptcha/api.js?hl=es" async defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
