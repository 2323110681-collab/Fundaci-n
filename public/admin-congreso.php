<?php
$secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params(array('httponly' => true, 'samesite' => 'Lax', 'secure' => $secureCookie));
session_start();
$adminUser = $_SESSION['user'] ?? null;
session_write_close();
header('Cache-Control: no-store');
if (!$adminUser || !in_array($adminUser['role'] ?? '', array('admin', 'editor'), true)) {
  header('Location: login');
  exit;
}
$isNewCongress = ($_GET['new'] ?? '') === '1';
$congressId = filter_var($_GET['id'] ?? '1', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
if (!$isNewCongress && $congressId === false) {
  http_response_code(404);
  exit('No se encontró el congreso solicitado.');
}
$pageHeading = $isNewCongress ? 'Presentación del Congreso' : ((int) $congressId === 1 ? 'Administrar Congreso CDIA' : 'Presentación del Congreso');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8') ?> - Panel de administración</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/admin.css?v=<?= filemtime(__DIR__ . '/assets/css/admin.css') ?>">
  <link rel="stylesheet" href="assets/css/quill.snow.css?v=<?= filemtime(__DIR__ . '/assets/css/quill.snow.css') ?>">
  <link rel="stylesheet" href="assets/css/admin-congreso.css?v=<?= filemtime(__DIR__ . '/assets/css/admin-congreso.css') ?>">
  <link rel="icon" type="image/png" href="assets/images/favicon.png">
</head>
<body class="admin-page p-6 md:p-12">
  <div class="max-w-6xl mx-auto space-y-10 congress-admin-layout">
    <header class="page-header">
      <button id="adminSidebarToggle" class="admin-sidebar-toggle" type="button" aria-label="Abrir menú de secciones" aria-expanded="false" aria-controls="adminSidebar">
        <span class="admin-sidebar-toggle-bars" aria-hidden="true"></span>
        <span class="admin-sidebar-toggle-label">Menú</span>
      </button>
      <div class="page-header-brand">
        <div>
          <p class="page-header-eyebrow">Gestión del sitio · Fundación DU</p>
          <h1><?= htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8') ?></h1>
          <p class="sub">Gestiona la información pública, tarifas, pagos, auspiciadores y ponentes.</p>
        </div>
      </div>
      <a class="page-header-link" href="admin">
        <span>Volver al panel</span>
      </a>
    </header>

    <section class="admin-shell bg-white rounded-3xl border border-gray-200 shadow-sm p-6">
      <div class="admin-workspace congress-admin-workspace">
        <aside id="adminSidebar" class="admin-sidebar" aria-label="Secciones del panel">
          <div class="admin-sidebar-title">
            <img class="admin-sidebar-logo" src="assets/images/logo-fdu.png" alt="Fundación DU" width="40" height="40">
            <span>Fundación DU</span>
          </div>
          <nav id="adminSectionNav" class="admin-sidebar-nav" aria-label="Secciones del panel">
            <a id="tab-programas" class="tab-button" data-resource="programas" href="admin?section=programas"><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h11a3 3 0 0 1 3 3v11H7a3 3 0 0 1-3-3V5z"/><path d="M4 16a3 3 0 0 1 3-3h11"/></svg><span>Programas</span></a>
            <a id="tab-noticias" class="tab-button" data-resource="noticias" href="admin?section=noticias"><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h12a2 2 0 0 1 2 2v13H7a2 2 0 0 1-2-2V4z"/><path d="M9 8h6M9 12h6M9 16h3"/></svg><span>Noticias</span></a>
            <a id="tab-eventos" class="tab-button" data-resource="agenda" href="admin?section=agenda"><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg><span>Eventos</span></a>
            <a id="tab-miembros" class="tab-button" data-resource="miembros" href="admin?section=miembros"><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><circle cx="17" cy="9" r="2.5"/><path d="M17 14a5 5 0 0 1 4 5"/></svg><span>Personal</span></a>
            <a id="tab-convenios" class="tab-button" data-resource="convenios" href="admin?section=convenios"><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V8l8-4 8 4v12"/><path d="M9 20v-6h6v6"/></svg><span>Convenios</span></a>
            <a id="tab-congreso" class="tab-button active" data-resource="congreso" href="admin?section=congreso" aria-current="page"><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5M8 16h8"/></svg><span>Congreso</span></a>
            <a id="tab-usuarios" class="tab-button hidden" data-resource="usuarios" href="admin?section=usuarios"><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg><span>Usuarios</span></a>
          </nav>
          <div id="sidebarUserCard" class="admin-sidebar-user hidden">
            <span id="sidebarUserAvatar" class="admin-sidebar-avatar" aria-hidden="true"></span>
            <span class="admin-sidebar-user-copy">
              <strong id="sidebarUsername"></strong>
              <small id="sidebarUserRole"></small>
            </span>
            <button id="btnLogout" type="button" class="admin-sidebar-logout">Salir</button>
          </div>
        </aside>
        <div class="admin-sidebar-backdrop" data-sidebar-close hidden></div>

        <main class="congress-admin-content">
          <div id="adminMessage" class="admin-message" role="status" hidden></div>
          <nav class="congress-jump" id="congressJump" aria-label="Ir a una sección del formulario">
            <a href="#sec-presentacion">Presentación</a>
            <a href="#sec-tarifas">Tarifas</a>
            <a href="#sec-certificaciones">Certificaciones</a>
            <a href="#sec-pago">Pago</a>
            <a href="#sec-auspiciadores">Auspiciadores</a>
            <a href="#sec-paises">Países</a>
            <a href="#sec-ponentes">Ponentes</a>
          </nav>
          <form id="congressForm" novalidate>
            <section class="admin-form-panel congress-admin-section" id="sec-presentacion">
              <h2>Presentación del Congreso</h2>
              <p class="congress-admin-intro">Completa la presentación y los datos del congreso. Luego añade tarifas, pagos, auspiciadores y ponentes en sus secciones.</p>
              <div class="admin-edit-grid">
                <label class="edit-field"><span class="edit-label">Título*</span><input name="title" maxlength="180" required class="input-field"></label>
                <label class="edit-field"><span class="edit-label">Subtítulo</span><input name="subtitle" maxlength="255" class="input-field"></label>
                <label class="edit-field edit-field-wide"><span class="edit-label">Resumen breve</span><textarea name="summary" rows="3" maxlength="1000" class="input-field"></textarea></label>
                <label class="edit-field edit-field-wide"><span class="edit-label">Presentación</span><textarea name="intro" rows="3" maxlength="1000" class="input-field"></textarea></label>
                <label class="edit-field"><span class="edit-label">Fecha</span><input name="date" maxlength="120" placeholder="Por confirmar o fecha" class="input-field"></label>
                <label class="edit-field"><span class="edit-label">Hora</span><input name="time" type="time" class="input-field"></label>
                <label class="edit-field"><span class="edit-label">Modalidad</span><input name="modality" maxlength="120" placeholder="Virtual, presencial, híbrida" class="input-field"></label>
                <label class="edit-field"><span class="edit-label">Sede</span><input name="venue" maxlength="255" class="input-field"></label>
                <label class="edit-field"><span class="edit-label">Enlace de inscripción</span><input name="registration_url" type="url" maxlength="1000" placeholder="https://..." class="input-field"></label>
                <div class="edit-field edit-field-wide congress-logo-field">
                  <label class="edit-label" for="registrationQrFile">Código QR de inscripción</label>
                  <div class="congress-qr-preview-wrap">
                    <img id="registrationQrPreview" class="congress-logo-preview" alt="Vista previa del QR de inscripción" hidden>
                    <button id="removeRegistrationQr" class="congress-qr-remove" type="button" aria-label="Quitar código QR" title="Quitar código QR" hidden>&times;</button>
                  </div>
                  <div class="congress-logo-controls">
                    <label class="btn-secondary congress-logo-picker">
                      <span>Elegir imagen del QR</span>
                      <input id="registrationQrFile" type="file" accept="image/jpeg,image/png,image/gif,image/webp" aria-label="Elegir código QR desde el equipo">
                    </label>
                    <span id="registrationQrFileName" class="speaker-photo-hint">JPG, PNG, GIF o WEBP · máximo 5 MB.</span>
                  </div>
                  <input id="registrationQrPath" name="registration_qr" type="hidden">
                </div>
                <label class="edit-field"><span class="edit-label">Correo de informes*</span><input name="contact_email" type="email" maxlength="254" required class="input-field"></label>
                <div class="edit-field edit-field-wide congress-logo-field">
                  <label class="edit-label" for="congressLogoFile">Logo del Congreso</label>
                  <img id="congressLogoPreview" class="congress-logo-preview" alt="Vista previa del logo" hidden>
                  <div class="congress-logo-controls">
                    <label class="btn-secondary congress-logo-picker">
                      <span>Elegir imagen del equipo</span>
                      <input id="congressLogoFile" type="file" accept="image/jpeg,image/png,image/gif,image/webp" aria-label="Elegir logo desde el equipo">
                    </label>
                    <span id="congressLogoFileName" class="speaker-photo-hint">JPG, PNG, GIF o WEBP · máximo 5 MB.</span>
                  </div>
                  <input id="congressLogoPath" name="logo" type="hidden">
                </div>
              </div>
            </section>

            <section class="admin-form-panel congress-admin-section" id="sec-tarifas">
              <div class="congress-admin-section-heading">
                <div><h2>Tarifas</h2><p>Agrega, edita o quita las categorías y sus precios.</p></div>
                <button class="btn-secondary" type="button" id="addFee">Agregar tarifa</button>
              </div>
              <div id="feesList" class="repeat-list"></div>
            </section>

            <section class="admin-form-panel congress-admin-section" id="sec-certificaciones">
              <h2>Certificaciones</h2>
              <div id="certificationsEditor" class="congress-rich-editor"></div>
            </section>

            <section class="admin-form-panel congress-admin-section" id="sec-pago">
              <h2>Datos de pago</h2>
              <div class="admin-edit-grid">
                <label class="edit-field"><span class="edit-label">Banco</span><input name="payment.bank" maxlength="180" class="input-field"></label>
                <label class="edit-field"><span class="edit-label">Titular</span><input name="payment.holder" maxlength="180" class="input-field"></label>
                <label class="edit-field"><span class="edit-label">Cuenta</span><input name="payment.account" maxlength="80" class="input-field"></label>
                <label class="edit-field"><span class="edit-label">CCI</span><input name="payment.cci" maxlength="80" class="input-field"></label>
                <label class="edit-field"><span class="edit-label">Otro medio de pago (nombre)</span><input name="payment.wallet_name" maxlength="60" placeholder="Yape, Plin, transferencia, etc." class="input-field"></label>
                <label class="edit-field"><span class="edit-label">Número o dato de ese medio</span><input name="payment.yape" maxlength="80" placeholder="Número de celular o dato de pago" class="input-field"></label>
                <label class="edit-field edit-field-wide"><span class="edit-label">Titular de ese medio</span><input name="payment.yape_holder" maxlength="180" class="input-field"></label>
                <label class="edit-field edit-field-wide congress-yape-option">
                  <input id="paymentYapeEnabled" type="checkbox">
                  <span>Mostrar este medio de pago en la página</span>
                </label>
                <label class="edit-field edit-field-wide"><span class="edit-label">Aviso de pago</span><textarea name="payment.note" rows="3" maxlength="1000" class="input-field"></textarea></label>
              </div>
            </section>

            <section class="admin-form-panel congress-admin-section" id="sec-auspiciadores">
              <h2>Auspiciadores</h2>
              <div class="congress-admin-section-heading">
                <p>Agrega el nombre, una descripción con formato y el logo de cada auspiciador.</p>
                <button class="btn-secondary" type="button" id="addSponsor">Agregar auspiciador</button>
              </div>
              <div id="sponsorsList" class="repeat-list"></div>
            </section>

            <section class="admin-form-panel congress-admin-section" id="sec-paises">
              <div class="congress-admin-section-heading">
                <div><h2>Países participantes</h2><p>Agrega países que quieras mostrar aunque todavía no tengan ponentes registrados.</p></div>
              </div>
              <div class="congress-country-admin-controls">
                <label class="edit-field">
                  <span class="edit-label">Agregar país</span>
                  <input id="congressCountryInput" class="input-field" maxlength="120" placeholder="Escribe el nombre del país">
                </label>
                <button class="btn-secondary" type="button" id="addCongressCountry">Agregar país</button>
              </div>
              <ul id="congressCountriesList" class="congress-countries-list" aria-label="Países participantes"></ul>
            </section>

            <section class="admin-form-panel congress-admin-section" id="sec-ponentes">
              <div class="congress-admin-section-heading">
                <div><h2>Ponentes</h2><p>Sube una fotografía y usa el editor visual para dar formato a cada biografía. Los enlaces van uno por línea.</p></div>
                <button class="btn-secondary" type="button" id="addSpeaker">Agregar ponente</button>
              </div>
              <div id="speakersList" class="repeat-list"></div>
            </section>

            <div class="admin-edit-footer congress-admin-actions">
              <span id="congressDirtyState" class="congress-dirty-state" role="status" aria-live="polite">Sin cambios pendientes</span>
              <a class="btn-secondary" href="admin?section=congreso">Volver a congresos</a>
              <button class="btn-primary" id="saveCongress" type="submit">Guardar cambios</button>
            </div>
          </form>
        </main>
      </div>
    </section>
  </div>
  <script src="assets/js/quill.js?v=<?= filemtime(__DIR__ . '/assets/js/quill.js') ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script defer src="assets/js/admin-congreso.js?v=<?= filemtime(__DIR__ . '/assets/js/admin-congreso.js') ?>"></script>
  <script defer src="assets/js/admin-congreso-ux.js?v=<?= filemtime(__DIR__ . '/assets/js/admin-congreso-ux.js') ?>"></script>
</body>
</html>
