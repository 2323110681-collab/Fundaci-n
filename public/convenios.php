<?php
$pageTitle = 'Convenios - FDU';
$activePage = 'convenios';
$pageStyles = array('assets/css/convenios-page.css');
require __DIR__ . '/includes/header.php';
?>
<main class="section institutional-page convenios-page-section">
  <div class="container">
    <header class="convenios-page-header">
      <span class="eyebrow">Alianzas institucionales</span>
      <h1 class="page-title">Convenios Internacionales</h1>
      <p class="section-subtitle">Conectamos a la comunidad universitaria con instituciones públicas, privadas y académicas para impulsar proyectos de formación, investigación e innovación.</p>
    </header>
    <div id="conveniosGrid" class="convenios-grid" aria-live="polite"></div>
    <div id="conveniosPagination" class="pagination" aria-label="Paginación de convenios"></div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/convenios-page.js?v=<?= filemtime(__DIR__ . '/assets/js/convenios-page.js') ?>"></script>
</body>
</html>
