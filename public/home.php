<?php
$pageTitle = 'Fundación para el Desarrollo Universitario | FDU Lima Sur';
$pageDescription = 'Sitio oficial de la Fundación para el Desarrollo Universitario de Lima Sur (FDU). Conoce nuestros programas académicos, especializaciones, congresos y convenios.';
$siteStructuredData = array(
  '@context' => 'https://schema.org',
  '@type' => 'WebSite',
  'name' => 'Fundación para el Desarrollo Universitario',
  'alternateName' => array('Fundación DU', 'FDU Lima Sur'),
  'url' => 'https://fundaciondu.org/',
  'publisher' => array(
    '@type' => 'Organization',
    'name' => 'Fundación para el Desarrollo Universitario de Lima Sur',
    'url' => 'https://fundaciondu.org/',
    'logo' => 'https://fundaciondu.org/assets/images/logo-fdu.png',
  ),
);
$pageMeta = '<meta name="description" content="' . htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') . '">' . "\n"
  . '  <link rel="canonical" href="https://fundaciondu.org/">' . "\n"
  . '  <meta property="og:type" content="website">' . "\n"
  . '  <meta property="og:site_name" content="Fundación para el Desarrollo Universitario">' . "\n"
  . '  <meta property="og:title" content="' . htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . '">' . "\n"
  . '  <meta property="og:description" content="' . htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') . '">' . "\n"
  . '  <meta property="og:url" content="https://fundaciondu.org/">' . "\n"
  . '  <script type="application/ld+json">'
  . json_encode($siteStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
  . '</script>';
$activePage = 'inicio';
$pageStyles = array('assets/css/home-popup.css');
require __DIR__ . '/includes/header.php';
?>

  <main>
    <section class="hero" id="inicio">
      <div class="hero-bg" role="img" aria-label="Campus universitario al atardecer"></div>
      <div class="hero-overlay"></div>
      <div class="container hero-content">
        <h1>Fundación para el Desarrollo Universitario de Lima Sur</h1>
        <p>Impulsamos el futuro de la educación con excelencia académica, programas especializados, congresos y convenios para formar líderes comprometidos con el desarrollo.</p>
        <div class="hero-actions">
          <a href="programas" class="btn btn-primary">Ver Programas</a>
          <a href="nosotros" class="btn btn-secondary">Nuestra Misión</a>
        </div>
      </div>
    </section>
    <section class="section programs-section" id="programas">
      <div class="container">
        <div class="section-title">
          <div class="title-accent"></div>
          <div>
            <h2>Programas, Especializaciones y Congresos</h2>
            <p class="section-subtitle">Formación continua, programas académicos y encuentros especializados</p>
          </div>
        </div>
        <div id="programasContainer" class="cards-grid programs-grid"></div>
      </div>
    </section>

    <section class="section president-section" id="presidente">
      <div class="container president-inner">
        <div class="president-image">
          <img src="assets/images/president-message.jpg" alt="Presidente de la FDU">
        </div>
        <div class="president-content">
          <span class="eyebrow">Mensaje del Presidente</span>
          <h2>Liderazgo con Propósito Social</h2>
          <blockquote>
            “Nuestra misión en FDU trasciende el aula. Buscamos formar no solo profesionales competentes, sino ciudadanos comprometidos con el progreso ético y tecnológico del Perú y el mundo.”
          </blockquote>
          <p>Dr. Alejandro Villavicencio, Presidente de la Fundación para el Desarrollo Universitario, reflexiona sobre los retos de la educación superior en la era digital y la importancia de las raíces humanistas en el desarrollo de Sur de Lima.</p>
          <a href="mensaje-presidente" class="link-gold">Leer Mensaje Completo</a>
        </div>
      </div>
    </section>
    <section class="section news-events-section" id="noticias">
      <div class="container news-events-inner">
        <div class="news-column">
          <div class="column-header">
            <h2>Últimas Noticias</h2>
            <a href="noticias" class="link-gold">Ver Todo</a>
          </div>
          <div id="noticiasContainer" class="news-list"></div>
        </div>
        <div class="agenda-column" id="calendario">
          <div class="column-header">
            <h2>Agenda Académica</h2>
            <a href="calendario" class="link-gold">Calendario Completo</a>
          </div>
          <div id="agendaContainer" class="agenda-list"></div>
        </div>
      </div>
    </section>
  </main>
  <?php require __DIR__ . '/includes/footer.php'; ?>
  <script src="assets/js/home.js?v=<?= filemtime(__DIR__ . '/assets/js/home.js') ?>"></script>
    <script src="assets/js/home-popup.js?v=<?= filemtime(__DIR__ . '/assets/js/home-popup.js') ?>"></script>
  </body>
  </html>
