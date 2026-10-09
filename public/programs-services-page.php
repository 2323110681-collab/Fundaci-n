<?php
$pageTitle = 'Programas y Servicios - FDU';
$activePage = 'publicaciones';
$pageStyles = array('assets/css/programs-services-page.css');
require __DIR__ . '/includes/header.php';
?>

  <!-- CONTENIDO PRINCIPAL: PROGRAMAS Y SERVICIOS -->
  <main class="section programs-page-section">
    <div class="container">

      <!-- ENCABEZADO -->
      <div class="programs-header">
        <h1 class="page-title">Programas y Servicios</h1>
        <p class="section-subtitle">Descubre nuestro amplio catálogo de programas de desarrollo profesional, certificaciones internacionales y servicios académicos diseñados para los líderes globales del mañana.</p>
      </div>

      <div id="program-filters"></div>

      <div class="programs-grid" aria-live="polite"></div>
      <div id="program-pagination" class="pagination" aria-label="Paginación de programas"></div>

    </div>
  </main>

  <!-- FOOTER -->
  <?php require __DIR__ . '/includes/footer.php'; ?>
  <script src="assets/js/programs-services-page.js?v=<?= filemtime(__DIR__ . '/assets/js/programs-services-page.js') ?>"></script>
</body>
</html>

