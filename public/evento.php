<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Agenda.php';

$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$event = null;
$loadError = false;

if ($eventId && $eventId > 0) {
    try {
        $database = new Database();
        $connection = $database->getConnection();
        if ($connection instanceof PDO) {
            new Agenda($connection);
            $statement = $connection->prepare('SELECT id, titulo, fecha_evento, fecha_fin, hora_evento, lugar, descripcion, link_inscripcion, categoria, imagen FROM eventos WHERE id = :id');
            $statement->bindValue(':id', $eventId, PDO::PARAM_INT);
            $statement->execute();
            $event = $statement->fetch(PDO::FETCH_ASSOC) ?: null;
        } else {
            $loadError = true;
        }
    } catch (Throwable $exception) {
        $loadError = true;
    }
}

  if (!$event) {
    http_response_code($loadError ? 500 : 404);
  }

// Otros eventos para seguir navegando. Se priorizan los que aun no han
// ocurrido y, a falta de esos, los mas recientes. La ordenacion por la
// diferencia de dias mantiene el mas cercano primero en ambos casos.
$relatedEvents = array();
if ($event) {
    try {
        $relatedStatement = $connection->prepare(
            'SELECT id, titulo, fecha_evento, fecha_fin, hora_evento, lugar, categoria, imagen
             FROM eventos
             WHERE id <> :id AND fecha_evento IS NOT NULL
             ORDER BY (fecha_evento >= CURDATE()) DESC, ABS(DATEDIFF(fecha_evento, CURDATE())) ASC
             LIMIT 3'
        );
        $relatedStatement->bindValue(':id', (int) $event['id'], PDO::PARAM_INT);
        $relatedStatement->execute();
        $relatedEvents = $relatedStatement->fetchAll(PDO::FETCH_ASSOC) ?: array();
    } catch (Throwable $exception) {
        $relatedEvents = array();
    }
}

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

// Texto plano a partir de la descripcion, que guarda HTML del editor.
$toPlainText = static function ($value) {
    $text = (string) $value;
    if (preg_match('/<\/?[a-z][\s\S]*?>/i', $text)) {
        $text = preg_replace('/<\/(p|li|h[1-4]|blockquote|div)>|<br\s*\/?>/i', ' ', $text);
        $text = strip_tags($text);
    }
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    return trim((string) preg_replace('/\s+/u', ' ', $text));
};

// Datos absolutos para la vista previa al compartir (WhatsApp, Facebook, Google).
$scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')) ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (!preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host)) {
    $host = 'localhost';
}
$origin = $scheme . '://' . $host;
$baseDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$absoluteUrl = static function ($path) use ($origin, $baseDir) {
    $path = trim((string) $path);
    if ($path === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if ($path[0] === '/') {
        return $origin . $path;
    }
    return $origin . $baseDir . '/' . $path;
};

$dateLabel = '';
$dateEndLabel = '';
$dateMonths = array('enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre');
if ($event && !empty($event['fecha_evento'])) {
    $eventDate = DateTimeImmutable::createFromFormat('!Y-m-d', $event['fecha_evento']);
    if ($eventDate) {
        $dateLabel = $eventDate->format('j') . ' de ' . $dateMonths[(int) $eventDate->format('n') - 1] . ' de ' . $eventDate->format('Y');
    }
}
if ($event && !empty($event['fecha_fin']) && $event['fecha_fin'] !== $event['fecha_evento']) {
    $eventEndDate = DateTimeImmutable::createFromFormat('!Y-m-d', $event['fecha_fin']);
    if ($eventEndDate) {
        $dateEndLabel = $eventEndDate->format('j') . ' de ' . $dateMonths[(int) $eventEndDate->format('n') - 1] . ' de ' . $eventEndDate->format('Y');
    }
}
$eventDateRangeLabel = $dateLabel . ($dateEndLabel !== '' ? ' – ' . $dateEndLabel : '');

$pageTitle = $event ? $event['titulo'] . ' - Fundación DU' : 'Evento no encontrado - Fundación DU';
$activePage = 'calendario';
$pageStyles = array('assets/css/evento-page.css');

// Vista previa al compartir y buscador. Sin esto la ficha solo se encuentra por su titulo.
$pageMeta = '';
if ($event) {
    $summary = $toPlainText($event['descripcion']);
    if ($summary === '') {
        $summary = 'Evento de la Fundación DU.';
    }
    $summary = mb_strimwidth($summary, 0, 200, '…', 'UTF-8');

    $pageUrl = $origin . $baseDir . '/evento?id=' . (int) $event['id'];
    $imageUrl = $absoluteUrl($event['imagen'] ?? '') ?: ($origin . $baseDir . '/assets/images/logo-fdu.png');
    $hasEventImage = !empty($event['imagen']);

    $tags = array(
        '<meta name="description" content="' . $escape($summary) . '">',
        '<link rel="canonical" href="' . $escape($pageUrl) . '">',
        '<meta property="og:type" content="article">',
        '<meta property="og:site_name" content="Fundación DU">',
        '<meta property="og:locale" content="es_PE">',
        '<meta property="og:title" content="' . $escape($event['titulo']) . '">',
        '<meta property="og:description" content="' . $escape($summary) . '">',
        '<meta property="og:url" content="' . $escape($pageUrl) . '">',
        '<meta property="og:image" content="' . $escape($imageUrl) . '">',
        '<meta name="twitter:card" content="' . ($hasEventImage ? 'summary_large_image' : 'summary') . '">',
        '<meta name="twitter:title" content="' . $escape($event['titulo']) . '">',
        '<meta name="twitter:description" content="' . $escape($summary) . '">',
        '<meta name="twitter:image" content="' . $escape($imageUrl) . '">',
    );
    if (!empty($event['categoria'])) {
        $tags[] = '<meta property="article:section" content="' . $escape($event['categoria']) . '">';
    }
    if (!empty($event['fecha_evento']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $event['fecha_evento'])) {
        $tags[] = '<meta property="article:published_time" content="' . $escape($event['fecha_evento']) . '">';
    }
    $pageMeta = "  " . implode("\n  ", $tags) . "\n";
} elseif (!$loadError) {
    $pageMeta = "  <meta name=\"robots\" content=\"noindex\">\n";
}

require __DIR__ . '/includes/header.php';
?>

<main class="section event-page-section">
  <div class="container">
    <nav class="event-breadcrumb" aria-label="Ruta de navegación">
      <a href="calendario">Todos los eventos</a>
      <span aria-hidden="true">/</span>
      <span><?= $event ? $escape($event['titulo']) : 'Evento no encontrado' ?></span>
    </nav>

    <?php if (!$event): ?>
      <section class="event-not-found">
        <p class="event-eyebrow">EVENTOS FDU</p>
        <h1><?= $loadError ? 'No se pudo cargar el evento' : 'Evento no encontrado' ?></h1>
        <p><?= $loadError ? 'Ocurrió un problema al consultar la información. Inténtalo nuevamente más tarde.' : 'El evento solicitado no existe o ya no está disponible.' ?></p>
        <a class="event-back-link" href="calendario">Volver al calendario</a>
      </section>
    <?php else: ?>
      <article class="event-detail-layout">
        <div class="event-detail-main">
          <header class="event-page-heading">
            <p class="event-eyebrow"><?= $escape($event['categoria'] ?: 'Evento') ?></p>
            <h1><?= $escape($event['titulo']) ?></h1>
            <div class="event-date-highlight">
              <span>Fecha del evento</span>
              <strong><?= $escape($eventDateRangeLabel ?: $event['fecha_evento']) ?></strong>
            </div>
          </header>

          <?php if (!empty($event['imagen'])): ?>
            <figure class="event-poster">
              <img src="<?= $escape($event['imagen']) ?>" alt="Imagen de <?= $escape($event['titulo']) ?>" loading="lazy">
            </figure>
          <?php endif; ?>

          <section class="event-about">
            <?php if (!empty($event['descripcion'])): ?>
              <div class="event-description">
                <?= $event['descripcion'] ?>
              </div>
            <?php else: ?>
              <p>Pronto compartiremos más información sobre este evento.</p>
            <?php endif; ?>
          </section>
        </div>

        <aside class="event-info-panel" aria-labelledby="eventInfoTitle">
          <h2 id="eventInfoTitle">Detalles</h2>
          <dl class="event-facts">
            <div>
              <dt>Fecha</dt>
              <dd><?= $escape($eventDateRangeLabel ?: $event['fecha_evento']) ?></dd>
            </div>
            <?php if (!empty($event['hora_evento'])): ?>
              <div>
                <dt>Hora</dt>
                <dd><?= $escape(substr($event['hora_evento'], 0, 5)) ?></dd>
              </div>
            <?php endif; ?>
            <?php if (!empty($event['categoria'])): ?>
              <div>
                <dt>Categoría</dt>
                <dd><?= $escape($event['categoria']) ?></dd>
              </div>
            <?php endif; ?>
            <?php if (!empty($event['lugar'])): ?>
              <div>
                <dt>Recinto</dt>
                <dd><?= $escape($event['lugar']) ?></dd>
              </div>
            <?php endif; ?>
          </dl>

          <?php
          $registrationUrl = trim((string) ($event['link_inscripcion'] ?? ''));
          $registrationScheme = strtolower((string) parse_url($registrationUrl, PHP_URL_SCHEME));
          $hasRegistrationLink = filter_var($registrationUrl, FILTER_VALIDATE_URL) && in_array($registrationScheme, array('http', 'https'), true);
          ?>
          <?php if ($hasRegistrationLink): ?>
            <a class="event-register-button" href="<?= $escape($registrationUrl) ?>" target="_blank" rel="noopener noreferrer">Ver inscripción</a>
          <?php else: ?>
            <p class="event-registration-unavailable">Inscripción no disponible</p>
          <?php endif; ?>

          <div class="event-share">
            <h3 class="event-share-title">Compartir este evento</h3>
            <div class="event-share-actions">
              <a class="event-share-btn event-share-whatsapp"
                 href="https://wa.me/?text=<?= rawurlencode($event['titulo'] . ' — ' . $pageUrl) ?>"
                 target="_blank" rel="noopener noreferrer">
                <svg class="event-share-icon" width="18" height="18" viewBox="0 0 448 512" fill="currentColor" aria-hidden="true"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222.5 100.1-222.5 222.5 0 39.1 10.2 77.3 29.6 110.9L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-100.1 224.1-222.5 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-5.5-2.8-23.7-8.7-45.1-27.8-16.7-14.9-27.9-33.2-31.2-38.8-3.2-5.6-.3-8.6 2.5-11.3 2.5-2.5 5.6-6.5 8.4-9.8 2.8-3.2 3.7-5.6 5.6-9.4 1.9-3.7 1-7.1-.5-9.8-1.5-2.8-13.3-32-18.2-43.9-4.8-11.8-9.7-10.2-13.3-10.5-3.5-.2-7.5-.2-11.5-.2-4 0-10.4 1.4-15.8 7.4-5.5 5.9-20.8 20.3-20.8 49.4 0 29.1 21.3 57.2 24.3 61.2 2.9 4.1 42.3 64.6 102.2 90.4 14.2 2.1 25.3 3.4 33.9 4.4 14.2 1.4 27.2 1.2 37.4-.8 5.6-1.1 17.4-7.1 19.9-14 2.5-6.9 2.5-13.1 1.8-14.4-1-1.8-3.7-2.8-6.5-4.1z"/></svg>
                <span>WhatsApp</span>
              </a>
              <button type="button" class="event-share-btn event-share-copy" data-share-copy data-share-done="Enlace copiado" data-share-error="No se pudo copiar el enlace">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                <span data-share-text>Copiar enlace</span>
              </button>
            </div>
            <p class="event-share-status" data-share-status role="status" aria-live="polite"></p>
          </div>
        </aside>
      </article>

      <?php if ($relatedEvents): ?>
        <section class="event-related" aria-labelledby="eventRelatedTitle">
          <div class="event-related-header">
            <h2 id="eventRelatedTitle">Otros eventos</h2>
            <a href="calendario" class="event-related-all">Ver todos los eventos</a>
          </div>
          <ul class="event-related-list">
            <?php foreach ($relatedEvents as $related):
                $relatedDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $related['fecha_evento']);
                $relatedEndDate = !empty($related['fecha_fin']) && $related['fecha_fin'] !== $related['fecha_evento']
                    ? DateTimeImmutable::createFromFormat('!Y-m-d', (string) $related['fecha_fin'])
                    : null;
                $relatedDateLabel = $relatedDate
                    ? $relatedDate->format('j') . ' ' . mb_substr($dateMonths[(int) $relatedDate->format('n') - 1], 0, 3) . '. ' . $relatedDate->format('Y')
                    : (string) $related['fecha_evento'];
                if ($relatedEndDate) {
                    $relatedDateLabel .= ' – ' . $relatedEndDate->format('j') . ' ' . mb_substr($dateMonths[(int) $relatedEndDate->format('n') - 1], 0, 3) . '. ' . $relatedEndDate->format('Y');
                }
            ?>
              <li class="event-related-item">
                <a href="evento?id=<?= (int) $related['id'] ?>">
                  <?php if (!empty($related['imagen'])): ?>
                    <span class="event-related-thumb">
                      <img src="<?= $escape($related['imagen']) ?>" alt="" loading="lazy" aria-hidden="true">
                    </span>
                  <?php endif; ?>
                  <span class="event-related-body">
                    <?php if (!empty($related['categoria'])): ?>
                      <span class="event-related-category"><?= $escape($related['categoria']) ?></span>
                    <?php endif; ?>
                    <strong class="event-related-title"><?= $escape($related['titulo']) ?></strong>
                    <span class="event-related-meta">
                      <span><?= $escape($relatedDateLabel) ?></span>
                      <?php if (!empty($related['lugar'])): ?>
                        <span><?= $escape($related['lugar']) ?></span>
                      <?php endif; ?>
                    </span>
                  </span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
  <script src="assets/js/evento-page.js?v=<?= filemtime(__DIR__ . '/assets/js/evento-page.js') ?>"></script>
</body>
</html>