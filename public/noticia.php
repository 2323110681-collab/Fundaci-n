<?php
require_once __DIR__ . '/../config/database.php';

$noticiaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$noticia = null;
$loadError = false;

if ($noticiaId && $noticiaId > 0) {
    try {
        $database = new Database();
        $connection = $database->getConnection();
        if ($connection instanceof PDO) {
            $statement = $connection->prepare('SELECT id, titulo, categoria, autor, imagen, fecha_evento, descripcion_corta, contenido, updated_at FROM noticias WHERE id = :id');
            $statement->bindValue(':id', $noticiaId, PDO::PARAM_INT);
            $statement->execute();
            $noticia = $statement->fetch(PDO::FETCH_ASSOC) ?: null;
        } else {
            $loadError = true;
        }
    } catch (Throwable $exception) {
        $loadError = true;
    }
}

if (!$noticia && !$loadError) {
    http_response_code(404);
}

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

// Texto plano a partir del contenido (que puede traer HTML del editor o texto simple antiguo).
$toPlainText = static function ($value) {
    $text = (string) $value;
    if (preg_match('/<\/?[a-z][\s\S]*?>/i', $text)) {
        $text = preg_replace('/<\/(p|li|h[1-4]|blockquote)>|<br\s*\/?>/i', ' ', $text);
        $text = strip_tags($text);
    }
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    return trim((string) preg_replace('/\s+/u', ' ', $text));
};

// Datos para la vista previa al compartir (WhatsApp, Facebook, LinkedIn, Google...).
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

$pageTitle = $noticia
    ? $noticia['titulo'] . ' - Fundación DU'
    : ($loadError ? 'Noticia - Fundación DU' : 'Noticia no encontrada - Fundación DU');
$activePage = 'noticias';
$pageStyles = array('assets/css/noticia-detail.css');

$pageMeta = '';
if ($noticia) {
    $summary = $toPlainText($noticia['descripcion_corta'] ?? '');
    if ($summary === '') {
        $summary = $toPlainText($noticia['contenido'] ?? '');
    }
    if ($summary === '') {
        $summary = 'Noticias de la Fundación DU.';
    }
    $summary = mb_strimwidth($summary, 0, 200, '…', 'UTF-8');

    $pageUrl = $origin . $baseDir . '/noticia?id=' . (int) $noticia['id'];
    $imageUrl = $absoluteUrl($noticia['imagen'] ?? '') ?: ($origin . $baseDir . '/assets/images/logo-fdu.png');
    $hasNewsImage = !empty($noticia['imagen']);

    $tags = array(
        '<meta name="description" content="' . $escape($summary) . '">',
        '<link rel="canonical" href="' . $escape($pageUrl) . '">',
        '<meta property="og:type" content="article">',
        '<meta property="og:site_name" content="Fundación DU">',
        '<meta property="og:locale" content="es_PE">',
        '<meta property="og:title" content="' . $escape($noticia['titulo']) . '">',
        '<meta property="og:description" content="' . $escape($summary) . '">',
        '<meta property="og:url" content="' . $escape($pageUrl) . '">',
        '<meta property="og:image" content="' . $escape($imageUrl) . '">',
        '<meta name="twitter:card" content="' . ($hasNewsImage ? 'summary_large_image' : 'summary') . '">',
        '<meta name="twitter:title" content="' . $escape($noticia['titulo']) . '">',
        '<meta name="twitter:description" content="' . $escape($summary) . '">',
        '<meta name="twitter:image" content="' . $escape($imageUrl) . '">',
    );
    if (!empty($noticia['categoria'])) {
        $tags[] = '<meta property="article:section" content="' . $escape($noticia['categoria']) . '">';
    }
    if (!empty($noticia['fecha_evento']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $noticia['fecha_evento'])) {
        $tags[] = '<meta property="article:published_time" content="' . $escape($noticia['fecha_evento']) . '">';
    }
    $pageMeta = "  " . implode("\n  ", $tags) . "\n";
} elseif (!$loadError) {
    $pageMeta = "  <meta name=\"robots\" content=\"noindex\">\n";
}

require __DIR__ . '/includes/header.php';
?>

<main class="section news-detail-page">
  <div class="container">
    <a class="news-detail-back" href="noticias">Volver a noticias</a>
    <article class="news-detail" aria-labelledby="detailTitle">
      <div class="news-detail-image">
        <img id="detailImage" src="assets/images/placeholder-news.jpg" alt="" loading="lazy">
        <span id="detailCategory" class="card-tag">Noticia</span>
      </div>
      <div class="news-detail-content">
        <p id="detailDate" class="news-detail-date">Cargando noticia...</p>
        <p id="detailAuthor" class="news-detail-author" hidden></p>
        <h1 id="detailTitle">Cargando noticia...</h1>
        <p id="detailDescription" class="news-detail-summary" hidden></p>
        <div id="detailContent" class="news-detail-body" hidden></div>
        <a id="detailLink" class="news-detail-source" href="#" target="_blank" rel="noopener noreferrer" hidden></a>
      </div>
    </article>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/noticia-detail.js?v=<?= filemtime(__DIR__ . '/assets/js/noticia-detail.js') ?>"></script>
</body>
</html>
