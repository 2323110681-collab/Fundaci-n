<?php
$pageTitle = 'Eventos Calendario - FDU';
$activePage = 'calendario';
$pageStyles = array('assets/css/calendario-page.css');
require __DIR__ . '/includes/header.php';
?>

  <!-- CONTENIDO PRINCIPAL: CALENDARIO -->
  <main class="section calendar-page-section">
    <div class="container">

      <!-- ENCABEZADO Y CONTROLES DE VISTA -->
      <div class="calendar-header-row">
        <div>
          <h1 class="page-title">Eventos Calendario</h1>
          <p class="section-subtitle">Descubre las conferencias académicas, los talleres culturales y las ceremonias institucionales que se celebran en la red de la Fundación DU.</p>
        </div>
        <div class="view-toggle">
          <button class="toggle-btn active" data-view="month" type="button">Vista Mensual</button>
          <button class="toggle-btn" data-view="list" type="button">Vista de Lista</button>
        </div>
      </div>

      <!-- BARRA DE FILTROS -->
      <div class="filter-bar">
        <div class="filter-controls">
          <span class="filter-label">FILTRADO POR:</span>
          <select id="typeFilterSelect" class="filter-select">
            <option value="">Tipos</option>
          </select>
          <select id="locationFilterSelect" class="filter-select">
            <option value="">Lugares</option>
          </select>
        </div>
        <button id="clearFiltersButton" class="btn-clear-filters" type="button">Eliminar Filtros</button>
      </div>

      <!-- GRID PRINCIPAL: CALENDARIO Y DETALLE -->
      <div class="calendar-layout">
        <div class="calendar-main">
          <!-- CALENDARIO DE MES -->
          <div class="calendar-widget" id="calendarWidget">
            <div class="calendar-month-header">
              <button id="prevMonth" type="button" class="month-nav">&lt;</button>
              <h2 id="calendarMonthTitle">Octubre 2026</h2>
              <button id="nextMonth" type="button" class="month-nav">&gt;</button>
            </div>
            <div class="calendar-grid" id="calendarGrid"></div>
          </div>

          <div class="event-list-panel hidden" id="eventListPanel">
            <div class="list-panel-header">
              <h2>Eventos en lista</h2>
              <p id="eventListInfo" class="list-panel-info">Carga de eventos...</p>
            </div>
            <div id="eventListContainer" class="event-list"></div>
          </div>
        </div>

        <!-- TARJETA LATERAL DE DETALLE DEL EVENTO SELECCIONADO -->
        <aside class="event-detail-card" id="eventDetailCard">
          <span class="card-tag" id="eventDetailTag">Evento</span>
          <h2 id="eventDetailTitle">Selecciona un evento</h2>

          <div class="event-meta-list">
            <div class="meta-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <div>
                <strong>Fecha</strong>
                <p id="detailDate">-</p>
              </div>
            </div>

            <div class="meta-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <div>
                <strong>Hora</strong>
                <p id="detailTime">-</p>
              </div>
            </div>

            <div class="meta-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <div>
                <strong>Lugar</strong>
                <p id="detailLocation">-</p>
              </div>
            </div>
          </div>

          <div class="event-description">
            <h3>DESCRIPCIÓN</h3>
            <p id="eventDetailDescription">Selecciona un día del calendario para ver los detalles del evento.</p>
          </div>

          <a id="eventDetailLink" href="#" class="btn btn-navy-full" target="_blank" rel="noopener noreferrer">Ver inscripción</a>
        </aside>

      </div>
    </div>
  </main>

  <!-- FOOTER -->
  <?php require __DIR__ . '/includes/footer.php'; ?>
  <script src="assets/js/calendario-page.js"></script>
</body>
</html>

