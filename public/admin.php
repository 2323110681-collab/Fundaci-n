<?php
require_once dirname(__DIR__) . '/config/recaptcha.php';
require_once __DIR__ . '/../app/Core/Autoloader.php';
Autoloader::register();
// Sin sesión iniciada no se entrega el panel: se redirige al login desde el servidor.
$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
  'httponly' => true,
  'samesite' => 'Lax',
  'secure' => $secureCookie,
]);
session_start();
$adminSessionUser = $_SESSION['user'] ?? null;
header('Cache-Control: no-store');
if (!$adminSessionUser) {
  session_write_close();
  header('Location: login');
  exit;
}

$requestedAdminSection = $_GET['section'] ?? 'programas';
$adminSection = is_string($requestedAdminSection) ? $requestedAdminSection : 'programas';
if ($adminSection === 'popup' && $_SERVER['REQUEST_METHOD'] === 'GET') {
  $popupNotice = $_SESSION['popup_notice'] ?? null;
  unset($_SESSION['popup_notice']);
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  $popupCsrfToken = $_SESSION['csrf_token'];
  try {
    $popupController = new PopupController();
    $popupConfiguration = $popupController->readAdmin();
  } catch (Throwable $exception) {
    error_log('No se pudo cargar la configuración del aviso emergente: ' . $exception->getMessage());
    $popupNotice = array('type' => 'error', 'message' => 'No se pudo cargar la configuración. Revisa la conexión con la base de datos.');
    $popupConfiguration = array();
  }
}
session_write_close();
if ($adminSection === 'congreso' && ($_GET['editar'] ?? '') === '1') {
  header('Location: admin-congreso?editar=1', true, 303);
  exit;
}
$adminSections = array('programas', 'noticias', 'agenda', 'miembros', 'convenios', 'congreso', 'usuarios', 'popup');
$adminCreateLabels = array(
  'programas' => array('title' => 'Crear Programa', 'description' => 'Registra nuevos programas.'),
  'noticias'  => array('title' => 'Crear Noticia', 'description' => 'Registra nuevas noticias.'),
  'agenda'    => array('title' => 'Crear Evento', 'description' => 'Registra nuevos eventos.'),
  'miembros'  => array('title' => 'Agregar Personal', 'description' => 'Registra a las personas que trabajan en la fundación: nombre, cargo, correo y foto. El orden de la lista se asigna solo, en el orden en que se registren. Se muestran en "Nosotros" y en el mensaje del presidente.'),
  'convenios' => array('title' => 'Crear Convenio', 'description' => 'Registra la institución, ubicación y coordenadas del convenio.'),
  'congreso'  => array('title' => 'Presentación del Congreso', 'description' => 'Completa en blanco la presentación de un nuevo congreso. Luego agrega tarifas, pagos, auspiciadores y ponentes.'),
  'usuarios'  => array('title' => 'Crear Usuario', 'description' => 'Crea usuarios con rol de editor o administrador.'),
);
if (!in_array($adminSection, $adminSections, true)) {
  $adminSection = 'programas';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Admin - Fundación DU</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/admin.css?v=<?= filemtime(__DIR__ . '/assets/css/admin.css') ?>">
  <?php if ($adminSection === 'popup'): ?>
    <link rel="stylesheet" href="assets/css/admin-popup.css?v=<?= filemtime(__DIR__ . '/assets/css/admin-popup.css') ?>">
  <?php endif; ?>
  <link rel="stylesheet" href="assets/css/quill.snow.css?v=<?= filemtime(__DIR__ . '/assets/css/quill.snow.css') ?>">
  <link rel="icon" type="image/png" href="assets/images/favicon.png">
  <link rel="apple-touch-icon" href="assets/images/favicon.png">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/images/logo-fdu.png">
</head>
<body class="bg-slate-50 font-sans p-6 md:p-12 text-slate-900 admin-page">
  <div class="max-w-6xl mx-auto space-y-10">
    <header class="page-header">
      <button id="adminSidebarToggle" class="admin-sidebar-toggle" type="button" aria-label="Abrir el menú de secciones" aria-expanded="false" aria-controls="adminSidebar">
        <span class="admin-sidebar-toggle-bars" aria-hidden="true"></span>
        <span class="admin-sidebar-toggle-label">Menú</span>
      </button>
      <div class="page-header-brand">
        <div>
          <p class="page-header-eyebrow">Gestión del sitio</p>
          <h1>Panel de administración</h1>
          <p class="sub">Gestiona los programas, noticias, eventos, personal, convenios, el Congreso CDIA y los usuarios del sitio.</p>
        </div>
      </div>
      <a class="page-header-link" href="home" target="_blank" rel="noopener noreferrer">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9"/><path d="M18 13v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5"/></svg>
        <span>Ver sitio</span>
      </a>
    </header>

    <section class="admin-shell bg-white rounded-3xl border border-gray-200 shadow-sm p-6">
      <div class="admin-workspace">
        <aside id="adminSidebar" class="admin-sidebar" aria-label="Secciones del panel">
          <div class="admin-sidebar-title">
            <img class="admin-sidebar-logo" src="assets/images/logo-fdu.png" alt="Fundación DU" width="36" height="36">
            <span>Fundación DU</span>
          </div>

          <nav id="adminSectionNav" class="admin-sidebar-nav" aria-label="Secciones del panel">
            <a class="tab-button <?= $adminSection === 'popup' ? 'active' : '' ?>" href="admin?section=popup" <?= $adminSection === 'popup' ? 'aria-current="page"' : '' ?>><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg><span>Aviso emergente</span></a>
            <a id="tab-programas" href="admin?section=programas" class="tab-button <?= $adminSection === 'programas' ? 'active' : '' ?>" data-resource="programas" <?= $adminSection === 'programas' ? 'aria-current="page"' : '' ?>><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h11a3 3 0 0 1 3 3v11H7a3 3 0 0 1-3-3V5z"/><path d="M4 16a3 3 0 0 1 3-3h11"/></svg><span>Programas</span></a>
            <a id="tab-noticias" href="admin?section=noticias" class="tab-button <?= $adminSection === 'noticias' ? 'active' : '' ?>" data-resource="noticias" <?= $adminSection === 'noticias' ? 'aria-current="page"' : '' ?>><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h12a2 2 0 0 1 2 2v13H7a2 2 0 0 1-2-2V4z"/><path d="M9 8h6M9 12h6M9 16h3"/></svg><span>Noticias</span></a>
            <a id="tab-eventos" href="admin?section=agenda" class="tab-button <?= $adminSection === 'agenda' ? 'active' : '' ?>" data-resource="agenda" <?= $adminSection === 'agenda' ? 'aria-current="page"' : '' ?>><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg><span>Eventos</span></a>
            <a id="tab-miembros" href="admin?section=miembros" class="tab-button <?= $adminSection === 'miembros' ? 'active' : '' ?>" data-resource="miembros" <?= $adminSection === 'miembros' ? 'aria-current="page"' : '' ?>><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><circle cx="17" cy="9" r="2.5"/><path d="M17 14a5 5 0 0 1 4 5"/></svg><span>Personal</span></a>
            <a id="tab-convenios" href="admin?section=convenios" class="tab-button <?= $adminSection === 'convenios' ? 'active' : '' ?>" data-resource="convenios" <?= $adminSection === 'convenios' ? 'aria-current="page"' : '' ?>><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V8l8-4 8 4v12"/><path d="M9 20v-6h6v6"/></svg><span>Convenios</span></a>
            <a id="tab-congreso" href="admin?section=congreso" class="tab-button <?= $adminSection === 'congreso' ? 'active' : '' ?>" data-resource="congreso" <?= $adminSection === 'congreso' ? 'aria-current="page"' : '' ?>><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5M8 16h8"/></svg><span>Congreso</span></a>
            <a id="tab-usuarios" href="admin?section=usuarios" class="tab-button hidden <?= $adminSection === 'usuarios' ? 'active' : '' ?>" data-resource="usuarios" <?= $adminSection === 'usuarios' ? 'aria-current="page"' : '' ?>><svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg><span>Usuarios</span></a>
          </nav>

          <div id="sidebarUserCard" class="admin-sidebar-user <?= $adminSection === 'popup' ? '' : 'hidden' ?>">
            <span id="sidebarUserAvatar" class="admin-sidebar-avatar" aria-hidden="true"><?= $adminSection === 'popup' ? htmlspecialchars(strtoupper(substr((string) ($adminSessionUser['username'] ?? 'A'), 0, 1)), ENT_QUOTES, 'UTF-8') : '' ?></span>
            <span class="admin-sidebar-user-copy">
              <strong id="sidebarUsername"><?= $adminSection === 'popup' ? htmlspecialchars((string) ($adminSessionUser['username'] ?? ''), ENT_QUOTES, 'UTF-8') : '' ?></strong>
              <small id="sidebarUserRole"><?= $adminSection === 'popup' ? (($adminSessionUser['role'] ?? '') === 'admin' ? 'Administrador' : 'Editor') : '' ?></small>
            </span>
            <button id="btnLogout" type="button" class="admin-sidebar-logout" <?= $adminSection === 'popup' ? 'data-admin-logout' : '' ?>>Salir</button>
          </div>
        </aside>

        <div class="admin-sidebar-backdrop" data-sidebar-close hidden></div>

        <?php if ($adminSection === 'popup'): ?>
        <?php $escapePopup = static function ($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }; ?>
        <main class="admin-content admin-popup-content">
          <section class="admin-form-panel admin-popup-card bg-white rounded-3xl border border-gray-200 shadow-sm p-6" aria-labelledby="popupSettingsTitle">
            <h2 id="popupSettingsTitle">Aviso emergente de inicio</h2>
            <p class="admin-popup-intro">Elige el afiche, el período de publicación y cuánto tiempo permanece abierto.</p>
            <?php if (!empty($popupNotice['message'])): ?>
              <div id="popupNotice" hidden data-type="<?= $escapePopup($popupNotice['type'] ?? 'error') ?>" data-message="<?= $escapePopup($popupNotice['message']) ?>"></div>
            <?php endif; ?>
            <form method="post" action="admin-popup" enctype="multipart/form-data" class="admin-popup-form">
              <input type="hidden" name="csrf_token" value="<?= $escapePopup($popupCsrfToken ?? '') ?>">
              <div class="admin-popup-field admin-popup-image-field">
                <label for="popupImage">Imagen del aviso</label>
                <input id="popupImage" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                <small>JPG, PNG, GIF o WEBP; máximo 5 MB. Si no seleccionas otra imagen, se conserva la actual.</small>
                <?php if (!empty($popupConfiguration['image_path'])): ?>
                  <img class="admin-popup-preview" src="<?= $escapePopup(UploadPath::normalize($popupConfiguration['image_path'])) ?>" alt="Vista previa del afiche actual">
                <?php endif; ?>
              </div>
              <div class="admin-popup-field">
                <label for="linkUrl">Enlace al hacer clic (opcional)</label>
                <input class="input-field" id="linkUrl" name="link_url" type="url" maxlength="1000" placeholder="https://fundaciondu.org/..." value="<?= $escapePopup($popupConfiguration['link_url'] ?? '') ?>">
              </div>
              <div class="admin-popup-dates">
                <div class="admin-popup-field">
                  <label for="startsOn">Mostrar desde</label>
                  <input class="input-field" id="startsOn" name="starts_on" type="date" required value="<?= $escapePopup($popupConfiguration['starts_on'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="admin-popup-field">
                  <label for="endsOn">Mostrar hasta (inclusive)</label>
                  <input class="input-field" id="endsOn" name="ends_on" type="date" required value="<?= $escapePopup($popupConfiguration['ends_on'] ?? date('Y-m-d', strtotime('+7 days'))) ?>">
                </div>
              </div>
              <div class="admin-popup-field">
                <label for="displaySeconds">Tiempo en pantalla</label>
                <select class="input-field" id="displaySeconds" name="display_seconds">
                  <?php foreach (array(0, 5, 10, 15, 20, 30, 45, 60, 90, 120) as $seconds): ?>
                    <option value="<?= $seconds ?>" <?= (int) ($popupConfiguration['display_seconds'] ?? 0) === $seconds ? 'selected' : '' ?>>
                      <?= $seconds === 0 ? 'Hasta que el visitante lo cierre' : $seconds . ' segundos' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small>El aviso se muestra una vez por sesión del navegador.</small>
              </div>
              <label class="admin-popup-active">
                <input type="checkbox" name="active" value="1" <?= !empty($popupConfiguration['active']) ? 'checked' : '' ?>>
                <span>Activar aviso emergente</span>
              </label>
              <div class="admin-popup-actions">
                <button class="btn-primary" type="submit">Guardar configuración</button>
              </div>
            </form>
          </section>
        </main>
        <?php else: ?>
        <div class="admin-content grid grid-cols-1 lg:grid-cols-2 gap-6 items-start <?= $adminSection === 'congreso' ? 'admin-content-congreso' : '' ?>">

        <div class="admin-form-panel bg-slate-50 border border-slate-200 rounded-3xl p-6">
          <h2 id="resourceTitle" class="text-xl font-semibold text-slate-900 mb-3"><?= htmlspecialchars($adminCreateLabels[$adminSection]["title"], ENT_QUOTES, "UTF-8") ?></h2>
          <p id="resourceDescription" class="text-sm text-slate-600"><?= htmlspecialchars($adminCreateLabels[$adminSection]["description"], ENT_QUOTES, "UTF-8") ?></p>
          <form id="formCreate" class="space-y-4 mt-6" enctype="multipart/form-data">
            <input type="hidden" id="createResourceInput" name="resource" value="programas">
            <div class="field-programas">
              <label for="titulo-programas" class="text-sm text-slate-700">Título*</label>
              <input id="titulo-programas" name="titulo" required class="input-field">
            </div>
            <div class="field-noticias hidden">
              <label for="titulo-noticias" class="text-sm text-slate-700">Título*</label>
              <input id="titulo-noticias" name="titulo" required class="input-field">
            </div>
            <div class="field-noticias hidden">
              <label for="autor-noticias" class="text-sm text-slate-700">Autor</label>
              <input id="autor-noticias" name="autor" class="input-field">
            </div>
            <div class="field-noticias hidden">
              <label for="categoria-noticias" class="text-sm text-slate-700">Categoría</label>
              <input id="categoria-noticias" name="categoria" list="categorias-noticias-list" maxlength="100" placeholder="Ej. Institucional (si la dejas vacía será «General»)" class="input-field">
              <datalist id="categorias-noticias-list"></datalist>
            </div>
            <div class="field-noticias hidden">
              <label class="admin-check"><input type="checkbox" id="destacada-noticias" name="es_destacada" value="1"><span>Noticia destacada (se muestra arriba en la página de noticias)</span></label>
            </div>
            <div class="field-agenda hidden">
              <label for="titulo-agenda" class="text-sm text-slate-700">Título*</label>
              <input id="titulo-agenda" name="titulo" required class="input-field">
            </div>

            <div class="field-programas">
              <label for="categoria-programas" class="text-sm text-slate-700">Categoría*</label>
              <input id="categoria-programas" name="categoria" required class="input-field">
            </div>
            <div class="field-miembros hidden">
              <label for="nombre-miembros" class="text-sm text-slate-700">Nombre completo*</label>
              <input id="nombre-miembros" name="nombre" required class="input-field">
            </div>
            <div class="field-miembros hidden">
              <label for="cargo-miembros" class="text-sm text-slate-700">Cargo*</label>
              <input id="cargo-miembros" name="cargo" required class="input-field">
            </div>
<div class="field-miembros hidden">
  <label for="correo-miembros" class="text-sm text-slate-700">Correo electrónico</label>
  <input id="correo-miembros" name="correo" type="email" maxlength="180" placeholder="Ej. j.perez@fundaciondu.org" class="input-field">
</div>

            <div class="field-convenios hidden"><label for="institucion-convenios" class="text-sm text-slate-700">Institución*</label><input id="institucion-convenios" name="institucion" required class="input-field"></div>
            <div class="field-convenios hidden"><label for="pais-convenios" class="text-sm text-slate-700">País*</label><input id="pais-convenios" name="pais" required class="input-field"></div>
            <div class="field-convenios hidden"><label for="ciudad-convenios" class="text-sm text-slate-700">Ciudad</label><input id="ciudad-convenios" name="ciudad" class="input-field"></div>
            <div class="field-convenios hidden"><label for="ubicacion-google-convenios" class="text-sm text-slate-700">Ubicación de Google Maps (opcional)</label><input id="ubicacion-google-convenios" name="ubicacion_google" type="url" placeholder="https://maps.google.com/..." class="input-field"><p class="edit-hint">Acepta enlaces largos y cortos (maps.app.goo.gl). Si no se pueden obtener las coordenadas, el convenio se guarda igual y se te avisará.</p></div>
            <div class="field-convenios hidden"><label for="tipo-convenios" class="text-sm text-slate-700">Tipo de convenio</label><input id="tipo-convenios" name="tipo" class="input-field"></div>
            <div class="field-convenios hidden"><label for="descripcion-convenios" class="text-sm text-slate-700">Descripción</label><textarea id="descripcion-convenios" name="descripcion" class="input-field"></textarea></div>

            <div class="field-congreso hidden">
              <p>Completa toda la información del congreso en el editor. Si sales antes de guardar, no se creará ningún registro.</p>
              <a href="admin-congreso?new=1" class="btn-primary">Crear congreso</a>
            </div>

<div class="field-programas">
  <label for="autor-programas" class="text-sm text-slate-700">Autor</label>
  <input id="autor-programas" name="autor" class="input-field">
            </div>
            <div class="field-programas">
              <label for="descripcion-programas" class="text-sm text-slate-700">Descripción</label>
              <div id="editor-descripcion-programas" class="quill-editor"></div>
              <input type="hidden" name="descripcion" id="input-descripcion-programas">
            </div>
            <div class="field-programas"><label for="duracion-programas" class="text-sm text-slate-700">Duración</label><input id="duracion-programas" name="duracion" placeholder="Ej. 120 horas" class="input-field"></div>
            <div class="field-programas"><label for="modalidad-programas" class="text-sm text-slate-700">Modalidad</label><select id="modalidad-programas" name="modalidad" class="input-field"><option value="">Seleccionar</option><option value="presencial">Presencial</option><option value="virtual">Virtual</option><option value="hibrida">Híbrida</option></select></div>
            <div class="field-programas"><label for="horario-programas" class="text-sm text-slate-700">Horario</label><input id="horario-programas" name="horario" class="input-field"></div>
            <div class="field-programas"><label for="frecuencia-programas" class="text-sm text-slate-700">Frecuencia</label><input id="frecuencia-programas" name="frecuencia" placeholder="Ej. Lunes y miércoles" class="input-field"></div>
            <div class="field-programas"><label for="dirigido-a-programas" class="text-sm text-slate-700">Dirigido a</label><textarea id="dirigido-a-programas" name="dirigido_a" class="input-field"></textarea></div>
            <div class="field-programas"><label for="objetivos-programas" class="text-sm text-slate-700">Objetivos</label><textarea id="objetivos-programas" name="objetivos" class="input-field"></textarea></div>
            <div class="field-programas"><label class="text-sm text-slate-700">Temario por módulos</label><div id="temario-programas" class="program-json-editor" data-program-json-list="temario"></div></div>
            <div class="field-programas"><label for="requisitos-programas" class="text-sm text-slate-700">Requisitos</label><textarea id="requisitos-programas" name="requisitos" class="input-field"></textarea></div>
            <div class="field-programas"><label for="certificacion-programas" class="text-sm text-slate-700">Certificación</label><textarea id="certificacion-programas" name="certificacion" class="input-field"></textarea></div>
            <div class="field-programas"><label class="text-sm text-slate-700">Docentes y expositores</label><div id="docentes-programas" class="program-json-editor" data-program-json-list="docentes"></div></div>
            <div class="field-programas"><label for="inversion-programas" class="text-sm text-slate-700">Inversión</label><textarea id="inversion-programas" name="inversion" class="input-field"></textarea></div>
            <div class="field-programas"><label for="descuentos-programas" class="text-sm text-slate-700">Descuentos</label><textarea id="descuentos-programas" name="descuentos" class="input-field"></textarea></div>
            <div class="field-programas"><label for="vacantes-programas" class="text-sm text-slate-700">Vacantes disponibles</label><input id="vacantes-programas" type="number" min="0" name="vacantes" class="input-field"></div>
            <div class="field-programas"><label for="contacto-telefono-programas" class="text-sm text-slate-700">Teléfono de inscripción</label><input id="contacto-telefono-programas" name="contacto_telefono" class="input-field"></div>
            <div class="field-programas"><label for="contacto-whatsapp-programas" class="text-sm text-slate-700">WhatsApp de inscripción (con código de país)</label><input id="contacto-whatsapp-programas" name="contacto_whatsapp" class="input-field"></div>
            <div class="field-programas"><label for="contacto-correo-programas" class="text-sm text-slate-700">Correo de inscripción</label><input id="contacto-correo-programas" type="email" name="contacto_correo" class="input-field"></div>
            <div class="field-noticias hidden">
              <label for="descripcion-noticias" class="text-sm text-slate-700">Descripción corta</label>
              <textarea id="descripcion-noticias" name="descripcion_corta" maxlength="255" class="input-field"></textarea>
            </div>
            <div class="field-noticias hidden">
              <label for="contenido-noticias" class="text-sm text-slate-700">Contenido</label>
              <div id="editor-contenido-noticias" class="quill-editor"></div>
              <input type="hidden" name="contenido" id="input-contenido-noticias">
            </div>
            <div class="field-agenda hidden">
              <label for="hora-evento-agenda" class="text-sm text-slate-700">Hora del evento</label>
              <input id="hora-evento-agenda" type="time" name="hora_evento" class="input-field">
            </div>
            <div class="field-agenda hidden">
              <label for="lugar-agenda" class="text-sm text-slate-700">Lugar</label>
              <input id="lugar-agenda" name="lugar" class="input-field">
            </div>
            <div class="field-agenda hidden">
              <label for="categoria-agenda" class="text-sm text-slate-700">Categoría</label>
              <input id="categoria-agenda" name="categoria" list="categorias-agenda-list" maxlength="100" placeholder="Ej. Taller, Conferencia" class="input-field">
              <datalist id="categorias-agenda-list"></datalist>
            </div>
            <div class="field-agenda hidden">
              <label for="descripcion-agenda" class="text-sm text-slate-700">Descripción del evento</label>
              <div id="editor-descripcion-crear" class="quill-editor"></div>
              <input type="hidden" name="descripcion" id="input-descripcion-crear">
            </div>
            <div class="field-programas">
              <label for="link-programas" class="text-sm text-slate-700">Link</label>
              <input id="link-programas" name="link" class="input-field">
            </div>
            <div class="field-noticias hidden">
              <label for="link-noticias" class="text-sm text-slate-700">Link</label>
              <input id="link-noticias" name="link" class="input-field">
            </div>
            <div class="field-agenda hidden">
              <label for="link-inscripcion-agenda" class="text-sm text-slate-700">Link de inscripción</label>
              <input id="link-inscripcion-agenda" name="link_inscripcion" class="input-field">
            </div>
            <div class="field-agenda hidden">
              <label for="imagen-agenda" class="text-sm text-slate-700">Imagen del evento (JPG, PNG, GIF o WEBP; máximo 2 MB)</label>
              <input id="imagen-agenda" type="file" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp" class="input-field">
            </div>
            <div class="field-programas hidden">
              <label for="imagen-programas" class="text-sm text-slate-700">Imagen (JPG, PNG, GIF o WEBP; máximo 2 MB)</label>
              <input id="imagen-programas" type="file" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp" class="input-field">
            </div>
            <div class="field-noticias hidden">
              <label for="imagen-noticias" class="text-sm text-slate-700">Imagen (JPG, PNG, GIF o WEBP; máximo 2 MB)</label>
              <input id="imagen-noticias" type="file" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp" class="input-field">
            </div>
            <div class="field-miembros hidden">
              <label for="imagen-miembros" class="text-sm text-slate-700">Foto del miembro (opcional; JPG, PNG o WEBP; máximo 2 MB)</label>
              <input id="imagen-miembros" type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="input-field">
            </div>
            <div class="field-convenios hidden"><label for="imagen-convenios" class="text-sm text-slate-700">Imagen del convenio (opcional; JPG, PNG o WEBP; máximo 2 MB)</label><input id="imagen-convenios" type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="input-field"></div>
            <div class="field-agenda hidden">
              <label for="fecha-evento-agenda" class="text-sm text-slate-700">Fecha del evento</label>
              <input id="fecha-evento-agenda" type="date" name="fecha_evento" class="input-field">
            </div>
            <div class="field-agenda hidden">
              <label for="fecha-fin-agenda" class="text-sm text-slate-700">Fecha de fin (opcional)</label>
              <input id="fecha-fin-agenda" type="date" name="fecha_fin" class="input-field">
            </div>
            <div class="field-noticias hidden">
              <label for="fecha-evento-noticias" class="text-sm text-slate-700">Fecha del evento</label>
              <input id="fecha-evento-noticias" type="date" name="fecha_evento" class="input-field">
            </div>
            <div class="field-programas hidden">
              <label for="fecha-inicio-programas" class="text-sm text-slate-700">Fecha Inicio</label>
              <input id="fecha-inicio-programas" type="date" name="fecha_inicio" class="input-field">
            </div>
            <div class="field-programas hidden">
              <label for="fecha-fin-programas" class="text-sm text-slate-700">Fecha Fin</label>
              <input id="fecha-fin-programas" type="date" name="fecha_fin" class="input-field">
            </div>
            <div class="field-programas">
              <label for="lugar-programas" class="text-sm text-slate-700">Lugar</label>
              <input id="lugar-programas" name="lugar" maxlength="200" placeholder="Ej. Auditorio principal, Villa El Salvador" class="input-field">
            </div>
            <div class="field-usuarios hidden">
              <label for="usuario-usuarios" class="text-sm text-slate-700">Usuario*</label>
              <input id="usuario-usuarios" name="username" required class="input-field">
            </div>
            <div class="field-usuarios hidden">
              <label for="password-usuarios" class="text-sm text-slate-700">Contraseña*</label>
              <input id="password-usuarios" type="password" name="password" required class="input-field">
            </div>
            <div class="field-usuarios hidden">
              <label for="role-usuarios" class="text-sm text-slate-700">Rol</label>
              <select id="role-usuarios" name="role" class="input-field">
                <option value="editor">Editor</option>
                <option value="admin">Administrador</option>
              </select>
            </div>
            <button type="button" id="createSubmitBtn" class="btn-primary <?= $adminSection === 'congreso' ? 'hidden' : '' ?>">Guardar</button>
          </form>
        </div>

        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6 results-panel">
          <h2 class="text-xl font-semibold text-slate-900 mb-4"><?= $adminSection === 'congreso' ? 'Congresos registrados' : 'Resultados' ?></h2>
          <div id="listadoResultados" class="space-y-4">
            <div class="admin-toolbar mb-4 flex items-center gap-3">
              <label class="text-sm">Sección:</label>
              <select id="topResourceSelect" class="input-field">
                <option value="programas">Programas</option>
                <option value="noticias">Noticias</option>
                <option value="agenda">Eventos</option>
                <option value="congreso">Congreso</option>
                <option value="usuarios" class="hidden">Usuarios</option>
              </select>
              <label class="text-sm">Buscar:</label>
              <input id="searchInput" placeholder="Buscar..." class="input-field">
              <button id="refreshBtn" class="btn-primary">Refrescar</button>
            </div>

            <div class="table-wrap">
              <table id="dataTable" class="w-full bg-white border">
                <thead id="tableHead"></thead>
                <tbody id="tableBody"></tbody>
              </table>
            </div>
            <div id="tablePager" class="table-pager" aria-label="Paginación de la tabla"></div>
          </div>
          <div id="loginModal" class="modal" aria-hidden="true">
              <div class="modal-content small-modal">
                  <button class="close" id="closeLogin">×</button>
                  <h2>Iniciar sesión</h2>
                  <form id="loginForm" class="space-y-4">
                    <label class="text-sm">Usuario</label>
                    <input id="loginUser" name="username" class="input-field">
                    <label class="text-sm">Contraseña</label>
                    <input id="loginPass" name="password" type="password" class="input-field">
                    <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars(getRecaptchaSiteKey(), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="actions">
                      <button type="button" id="loginBtn" class="btn-primary">Entrar</button>
                      <button type="button" id="logoutBtn" class="btn-secondary">Salir</button>
                    </div>
                  </form>
              </div>
          </div>
        </div>
        </div>
        <section id="editScreen" class="admin-edit-screen" aria-labelledby="modalTitle" hidden>
          <div class="admin-edit-header">
            <div>
              <button type="button" id="editBackBtn" class="admin-edit-back">&larr; Volver al listado</button>
              <h2 id="modalTitle">Editar</h2>
              <p id="editScreenSubtitle" class="admin-edit-subtitle"></p>
              <a id="editViewLink" class="admin-edit-viewlink" href="#" target="_blank" rel="noopener" hidden>Ver en el sitio ↗</a>
            </div>
            <div class="admin-edit-actions">
              <button type="button" id="cancelBtn" class="btn-secondary">Cancelar</button>
              <button type="button" id="saveBtn" class="btn-primary">Guardar cambios</button>
            </div>
          </div>
          <form id="editForm" enctype="multipart/form-data" novalidate>
            <div id="formFields" class="admin-edit-grid"></div>
          </form>
          <div class="admin-edit-footer">
            <button type="button" id="cancelBtnBottom" class="btn-secondary">Cancelar</button>
            <button type="button" id="saveBtnBottom" class="btn-primary">Guardar cambios</button>
          </div>
        </section>
        <?php endif; ?>
        </div>
      </section>
  </div>
  <?php if ($adminSection === 'popup'): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./assets/js/admin-popup.js?v=<?= filemtime(__DIR__ . '/assets/js/admin-popup.js') ?>"></script>
  <?php else: ?>
  <script src="./assets/js/quill.js?v=<?= filemtime(__DIR__ . '/assets/js/quill.js') ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://www.google.com/recaptcha/api.js?hl=es" async defer></script>
  <script src="./assets/js/login.js"></script>
  <script src="./assets/js/admin.v2.js?v=<?= filemtime(__DIR__ . '/assets/js/admin.v2.js') ?>"></script>
  <?php endif; ?>
</body>
</html>