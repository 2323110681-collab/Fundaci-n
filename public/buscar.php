<?php
$pageTitle = 'Buscar - FDU';
$activePage = '';
$pageStyles = array('assets/css/search-page.css');
require __DIR__ . '/includes/header.php';
?>
<main class="section global-search-page">
  <div class="container">
    <header class="global-search-heading">
      <span class="eyebrow">Buscar en todo el sitio</span>
      <h1 class="page-title">Resultados de búsqueda</h1>
      <p class="global-search-query"><span>Coincidencias para:</span> <strong id="globalSearchQuery"></strong></p>
    </header>
    <p id="globalSearchStatus" class="global-search-status" role="status" aria-live="polite">Buscando...</p>
    <p id="globalSearchWarning" class="global-search-warning" hidden>Algunos apartados no pudieron consultarse.</p>
    <div id="globalSearchResults" class="global-search-results"></div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/search-page.js?v=<?= filemtime(__DIR__ . '/assets/js/search-page.js') ?>"></script>
</body>
</html>