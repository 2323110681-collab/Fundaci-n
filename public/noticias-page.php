<?php
$pageTitle = 'Ultimas Noticias - FDU';
$activePage = 'noticias';
$pageStyles = array('assets/css/noticias-page.css');
require __DIR__ . '/includes/header.php';
?>

  <!-- CONTENIDO PRINCIPAL: NOTICIAS -->
  <main class="section news-page-section">
    <div class="container">

      <h1 class="page-title">Últimas Noticias</h1>
      <p id="pageInfo" class="news-page-info"></p>

      <div class="news-page-layout">
        
        <!-- SECCIÓN IZQUIERDA: LISTA Y GRID DE NOTICIAS -->
        <div class="news-content-area">
          
          <!-- NOTICIA DESTACADA (FEATURED) -->
          <article class="featured-news-card" id="featured-news-card"></article>

          <!-- GRID DE NOTICIAS SECUNDARIAS -->
          <div id="newsGrid" class="news-grid"></div>

          <!-- PAGINACIÓN -->
          <div id="paginationContainer" class="pagination"></div>

        </div>

        <!-- SECCIÓN DERECHA: BARRA LATERAL (SIDEBAR) -->
        <aside class="news-sidebar">
          <!-- BUSCADOR INTERNO -->
          <div class="sidebar-widget">
            <h3>Buscar noticias</h3>
            <div class="search-box">
              <input id="newsSearchInput" type="text" placeholder="Palabras claves...">
              <button id="newsSearchButton" type="button" aria-label="Buscar"><img src="assets/icons/search.svg" alt="" aria-hidden="true"></button>
            </div>
          </div>
          <!-- CATEGORÍAS -->
          <div class="sidebar-widget">
            <h3>Categorías</h3>
            <ul id="categoryList" class="widget-list"></ul>
          </div>
          <!-- ARCHIVO HISTÓRICO -->
          <div class="sidebar-widget">
            <h3>Archivo</h3>
            <div id="archiveContainer" class="archive-accordion"></div>
          </div>

          <!-- CAJA DE SUSCRIPCIÓN (NEWSLETTER) -->
          <div class="sidebar-widget newsletter-card">
            <div class="newsletter-icon">✉</div>
            <h3>Mantente al día</h3>
            <p>Recibe nuestro resumen semanal con lo más destacado de la fundación y los resultados de investigaciones a nivel mundial.</p>
            <button class="btn btn-primary btn-block">Suscríbete</button>
          </div>
        </aside>
      </div>
    </div>
  </main>
  <?php require __DIR__ . '/includes/footer.php'; ?>
  <script src="assets/js/noticias-page.js?v=<?= filemtime(__DIR__ . '/assets/js/noticias-page.js') ?>"></script>
</body>
</html>

