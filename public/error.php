<?php
$errorCode = (isset($_GET['code']) && (string) $_GET['code'] === '403') ? 403 : 404;
http_response_code($errorCode);

$pageTitle = $errorCode === 403 ? 'Acceso denegado - FDU' : 'Página no encontrada - FDU';
$activePage = '';
$pageStyles = array('assets/css/error-page.css');
$scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/error.php'));
$pageBaseHref = rtrim(dirname($scriptPath), '/') . '/';
require __DIR__ . '/includes/header.php';
?>
<main class="error-page">
  <section class="error-card" aria-labelledby="error-title">
    <p class="error-code"><?= $errorCode ?></p>
    <?php if ($errorCode === 403): ?>
      <h1 id="error-title">No tienes permiso para acceder a este documento</h1>
      <p>El acceso a este recurso está restringido.</p>
    <?php else: ?>
      <h1 id="error-title">No encontramos esta página</h1>
      <p>El archivo o la página que buscas no existe o ya no está disponible.</p>
    <?php endif; ?>
    <a class="error-home-link" href="home">Volver al inicio</a>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
