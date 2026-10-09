<?php
$activePage = $activePage ?? '';
$pageTitle = $pageTitle ?? 'FDU';
if (empty($pageBaseHref)) {
  $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
  $scriptDirectory = rtrim(dirname($scriptPath), '/');
  if (preg_match('~/public$~i', $scriptDirectory)) {
    $scriptDirectory = substr($scriptDirectory, 0, -7);
  }
  $pageBaseHref = ($scriptDirectory === '' ? '/' : $scriptDirectory . '/');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if (!empty($pageBaseHref)): ?>
    <base href="<?= htmlspecialchars($pageBaseHref, ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/home.css?v=<?= filemtime(__DIR__ . '/../assets/css/home.css') ?>">
  <?php if (!empty($pageStyles)): ?>
    <?php foreach ($pageStyles as $style): ?>
      <?php $stylePath = __DIR__ . '/../' . ltrim($style, './'); ?>
      <?php $styleVersion = is_file($stylePath) ? '?v=' . filemtime($stylePath) : ''; ?>
      <link rel="stylesheet" href="<?= htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . $styleVersion ?>">
    <?php endforeach; ?>
  <?php endif; ?>
  <link rel="stylesheet" href="assets/css/pagination.css?v=<?= filemtime(__DIR__ . '/../assets/css/pagination.css') ?>">
  <link rel="icon" type="image/png" href="assets/images/logo-fdu.png">
  <script defer src="assets/js/site-controls.js?v=<?= filemtime(__DIR__ . '/../assets/js/site-controls.js') ?>"></script>
  <?php if (!empty($pageMeta)) { echo $pageMeta; } ?>
</head>
<body>
  <div class="top-bar">
    <div class="container top-bar-inner">
      <div class="lang-switcher" role="group" aria-label="Idioma del sitio">
        <button type="button" class="active" data-site-language="es" aria-label="Español" aria-pressed="true">ES</button>
        <span class="separator" aria-hidden="true"></span>
        <button type="button" data-site-language="en" aria-label="English" aria-pressed="false">EN</button>
        <span class="separator" aria-hidden="true"></span>
        <button type="button" data-site-language="zh" aria-label="中文" aria-pressed="false">中文</button>
      </div>
      <div class="accessibility-tools">
        <button type="button" aria-label="Aumentar tamaño de texto" aria-pressed="false" class="acc-btn" data-accessibility-action="text-size"><img src="assets/icons/text-size.svg" alt="" aria-hidden="true" width="20" height="20"></button>
        <button type="button" aria-label="Activar alto contraste" aria-pressed="false" class="acc-btn" data-accessibility-action="contrast"><img src="assets/icons/contrast.svg" alt="" aria-hidden="true" width="20" height="20"></button>
        <button type="button" aria-label="Leer página en voz alta" aria-pressed="false" class="acc-btn" data-accessibility-action="speech"><img src="assets/icons/speaker.svg" alt="" aria-hidden="true" width="20" height="20"></button>
      </div>
    </div>
  </div>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="<?= htmlspecialchars($pageBaseHref, ENT_QUOTES, 'UTF-8') ?>">
        <img src="assets/images/logo-fdu.png" alt="Logo FDU" width="72" height="72">
        <span class="brand-name"><span>Fundación para el</span><span>Desarrollo Universitario</span></span>
      </a>
      <nav class="main-nav" aria-label="Navegación principal">
        <a href="<?= htmlspecialchars($pageBaseHref, ENT_QUOTES, 'UTF-8') ?>" class="<?= $activePage === 'inicio' ? 'active' : '' ?>">Inicio</a>
        <div class="nav-dropdown <?= in_array($activePage, array('nosotros', 'presidente'), true) ? 'active' : '' ?>">
          <a href="nosotros" aria-haspopup="true" aria-expanded="false" aria-controls="nav-nosotros-menu">Nosotros <span class="nav-chevron" aria-hidden="true"></span></a>
          <div id="nav-nosotros-menu" class="nav-dropdown-menu">
            <a href="nosotros">Sobre Nosotros</a>
            <a href="mensaje-presidente">Mensaje del presidente</a>
          </div>
        </div>
        <div class="nav-dropdown <?= in_array($activePage, array('publicaciones', 'congreso'), true) ? 'active' : '' ?>">
          <a href="programas" aria-haspopup="true" aria-expanded="false" aria-controls="nav-programas-menu">Programas y Servicios <span class="nav-chevron" aria-hidden="true"></span></a>
          <div id="nav-programas-menu" class="nav-dropdown-menu">
            <a href="programas">Programas y Servicios</a>
            <a href="congreso">Congresos</a>
          </div>
        </div>
        <a href="convenios" class="<?= $activePage === 'convenios' ? 'active' : '' ?>">Convenios</a>
        <a href="calendario" class="<?= $activePage === 'calendario' ? 'active' : '' ?>">Calendario</a>
        <a href="noticias" class="<?= $activePage === 'noticias' ? 'active' : '' ?>">Noticias</a>
        <a href="contactos" class="<?= $activePage === 'contactos' ? 'active' : '' ?>">Contactos</a>
      </nav>
      <form class="header-search" role="search" action="buscar" method="get">
        <input type="search" name="q" minlength="2" required placeholder="Buscar..." aria-label="Buscar" value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <button class="header-search-submit" type="submit" aria-label="Buscar">
          <img src="assets/icons/search.svg" alt="" aria-hidden="true" width="18" height="18">
        </button>
      </form>
      <button class="menu-toggle" aria-label="Abrir menú" aria-expanded="false" data-menu-toggle><span></span><span></span><span></span></button>
    </div>
  </header>
