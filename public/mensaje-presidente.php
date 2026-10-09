<?php
$pageTitle = 'Mensaje del presidente - FDU';
$activePage = 'presidente';
$pageStyles = array('assets/css/mensaje-presidente.css');
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
require_once dirname(__DIR__) . '/config/database.php';
Autoloader::register();
$members = null;
try {
  $database = new Database();
  $members = (new Miembros($database->getConnection()))->readAll();
} catch (Throwable $exception) {
  error_log('No se pudieron cargar los miembros en mensaje-presidente.php: ' . $exception->getMessage());
}
require __DIR__ . '/includes/header.php';
?>
<main class="section institutional-page reference-page message-page-section">
  <div class="container">
    <header class="message-page-hero">
      <span class="eyebrow">Mensaje del Presidente</span>
      <h1 class="page-title">Mensaje del Presidente</h1>
    </header>
    <section class="message-profile">
      <div class="message-profile-image">
        <img src="assets/images/president-message.jpg" alt="Julio Cesar Torres Isla, presidente de la Fundación">
      </div>
      <div class="message-profile-copy">
        <h2>Mg. Julio Cesar Torres Isla</h2>
        <span class="president-role">Presidente</span>
        <p>Mg. Julio Cesar Torres Isla, Presidente de la Fundación para el Desarrollo Universitario, reflexiona sobre los retos de la educación superior en la era digital y la importancia de las raíces humanistas en el desarrollo de Sur de Lima.</p>

        <div class="message-video-shell">
          <div class="message-video-card">
            <video controls playsinline preload="auto">
              <source src="assets/video/video_cepre.mp4" type="video/mp4">
              Tu navegador no soporta la reproducción de video.
            </video>
          </div>
        </div>
      </div>
    </section>

    <section class="message-quote-band">
      <blockquote>Nuestra misión trasciende las fronteras; estamos formando una generación capaz de combinar la sabiduría ancestral con la innovación moderna para resolver los retos más urgentes del mundo.</blockquote>
      <p>— Mg. Julio Cesar Torres Isla</p>
    </section>

    <section class="reference-section message-members">
      <span class="eyebrow">Equipo institucional</span>
      <h2>Miembros de la fundación</h2>
      <div class="members-grid" aria-live="polite">
        <?php if ($members === null): ?>
          <p class="members-loading">No se pudieron cargar los miembros en este momento.</p>
        <?php elseif ($members === array()): ?>
          <p class="members-loading">No hay miembros registrados para mostrar.</p>
        <?php else: ?>
          <?php foreach ($members as $member): ?>
            <article class="member-card">
              <?php
              $image = $member['imagen'] ?? null;
              if (is_string($image) && UploadPath::isManagedPath($image)) {
                $image = UploadPath::normalize($image);
              }
              ?>
              <?php if ($image): ?>
                <img class="member-photo" src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($member['nombre'] ?: 'Miembro de la fundación', ENT_QUOTES, 'UTF-8') ?>">
              <?php else: ?>
                <div class="member-photo member-photo-placeholder" role="img" aria-label="Foto pendiente"></div>
              <?php endif; ?>
              <h3><?= htmlspecialchars($member['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
              <p><?= htmlspecialchars($member['cargo'], ENT_QUOTES, 'UTF-8') ?></p>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
