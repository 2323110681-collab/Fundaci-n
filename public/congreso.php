<?php
$pageTitle = 'Congreso Internacional CDIA - FDU';
$activePage = 'congreso';
$pageStyles = array('assets/css/congreso-page.css');
$rawCongressId = $_GET['id'] ?? null;
$requestedCongressId = $rawCongressId === null ? null : filter_var(
  $rawCongressId,
  FILTER_VALIDATE_INT,
  array('options' => array('min_range' => 1))
);
if ($rawCongressId !== null && $requestedCongressId === false) {
  http_response_code(404);
  exit('No se encontró el congreso solicitado.');
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Models/Congreso.php';
require_once dirname(__DIR__) . '/app/Helpers/HtmlSanitizer.php';
require_once dirname(__DIR__) . '/app/Helpers/UploadPath.php';
try {
  $database = new Database();
  $connection = $database->getConnection();
  if (!$connection instanceof PDO) {
    throw new RuntimeException('No se pudo conectar con la base de datos.');
  }
  $congressModel = new Congreso($connection);
  if ($requestedCongressId === null) {
    $congresses = $congressModel->readAll(true);
  } else {
    $congress = $congressModel->read((int) $requestedCongressId);
    if ($congress === null) {
      http_response_code(404);
      exit('No se encontró el congreso solicitado.');
    }
  }
} catch (Throwable $exception) {
  error_log('No se pudo cargar la información de congresos: ' . $exception->getMessage());
  http_response_code(500);
  exit('No se pudo cargar la información del congreso.');
}

if ($requestedCongressId === null) {
  $pageTitle = 'Congresos - FDU';
  $pageStyles[] = 'assets/css/programs-services-page.css';
  $escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
  };
  require __DIR__ . '/includes/header.php';
  ?>
  <main class="section programs-page-section congress-catalog-page">
    <div class="container">
      <div class="programs-header">
        <h1 class="page-title">Congresos</h1>
        <p class="section-subtitle">Explora nuestros congresos, conoce sus detalles y encuentra la información de inscripción.</p>
      </div>
      <?php if ($congresses): ?>
        <div class="congress-catalog-filters" role="search" aria-label="Filtrar congresos">
          <span class="filter-label">FILTRAR POR:</span>
          <input class="filter-select" id="congress-search" type="search" placeholder="Buscar congreso..." aria-label="Buscar por nombre o descripción">
          <select class="filter-select" id="congress-modality" aria-label="Filtrar por modalidad">
            <option value="">Modalidad</option>
            <?php
            $modalities = array();
            foreach ($congresses as $item) {
              $modality = trim((string) ($item['modality'] ?? ''));
              if ($modality !== '') {
                $modalities[$modality] = $modality;
              }
            }
            natcasesort($modalities);
            foreach ($modalities as $modality):
            ?>
              <option value="<?= $escape($modality) ?>"><?= $escape($modality) ?></option>
            <?php endforeach; ?>
          </select>
          <select class="filter-select" id="congress-year" aria-label="Filtrar por año">
            <option value="">Año</option>
            <?php
            $years = array();
            foreach ($congresses as $item) {
              if (preg_match('/\b(?:19|20)\d{2}\b/', (string) ($item['date'] ?? ''), $match)) {
                $years[$match[0]] = $match[0];
              }
            }
            krsort($years, SORT_NUMERIC);
            foreach ($years as $year):
            ?>
              <option value="<?= $escape($year) ?>"><?= $escape($year) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn-clear-filters" id="clear-congress-filters" type="button">Eliminar filtros</button>
        </div>
        <div class="programs-grid congress-catalog-grid" id="congress-catalog-grid" aria-live="polite">
          <?php foreach ($congresses as $item): ?>
            <?php
              $title = (string) ($item['title'] ?? '');
              $description = trim((string) ($item['summary'] ?? ''));
              if ($description === '') {
                $description = trim((string) ($item['intro'] ?? ''));
              }
              if ($description === '') {
                $description = (string) ($item['subtitle'] ?? '');
              }
              if ($description === '') {
                $description = 'Conoce a nuestros ponentes, tarifas y opciones de inscripción.';
              }
              $date = (string) ($item['date'] ?? '');
              $time = (string) ($item['time'] ?? '');
              $modality = (string) ($item['modality'] ?? '');
              preg_match('/\b(?:19|20)\d{2}\b/', $date, $match);
              $year = $match[0] ?? '';
              $searchText = implode(' ', array($title, $description, $item['subtitle'] ?? '', $date, $time, $modality, $item['venue'] ?? ''));
              $logo = trim((string) ($item['logo'] ?? ''));
              if ($logo === '') {
                $logo = 'assets/images/congreso/logo-cdia.jpg';
              }
            ?>
            <article class="program-card congress-catalog-card"
              data-search="<?= $escape($searchText) ?>"
              data-modality="<?= $escape(mb_strtolower($modality, 'UTF-8')) ?>"
              data-year="<?= $escape($year) ?>">
              <div class="program-card-img congress-catalog-image">
                <img src="<?= $escape($logo) ?>" alt="Logo de <?= $escape($title) ?>" loading="lazy">
                <span class="card-tag">Congreso</span>
              </div>
              <div class="program-card-body">
                <h3><a href="congreso?id=<?= (int) $item['id'] ?>"><?= $escape($title) ?></a></h3>
                <p><?= $escape($description) ?></p>
                <div class="program-partner"><span><?= $escape(trim(implode(' · ', array_filter(array($date, $time, $modality))))) ?></span></div>
                <a class="link-gold" href="congreso?id=<?= (int) $item['id'] ?>">Ver información del congreso</a>
              </div>
            </article>
          <?php endforeach; ?>
          <p class="congress-catalog-empty" id="congress-no-results" hidden>No hay congresos que coincidan con los filtros.</p>
        </div>
      <?php else: ?>
        <p class="congress-catalog-empty">Aún no hay congresos publicados.</p>
      <?php endif; ?>
    </div>
  </main>
  <?php require __DIR__ . '/includes/footer.php'; ?>
  <?php if ($congresses): ?>
    <script src="assets/js/congreso-catalog.js?v=<?= filemtime(__DIR__ . '/assets/js/congreso-catalog.js') ?>"></script>
  <?php endif; ?>
  </body>
  </html>
  <?php
  exit;
}

$pageTitle = $congress['title'] . ' - FDU';
$escape = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$gmailCompose = static function ($email) use ($escape) {
  return 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode((string) $email);
};
$flagEntity = static function ($countryCode) {
  if (!is_string($countryCode) || preg_match('/^[A-Z]{2}$/', $countryCode) !== 1) {
    return '';
  }
  return '&#x' . strtoupper(dechex(127397 + ord($countryCode[0]))) . ';&#x' . strtoupper(dechex(127397 + ord($countryCode[1]))) . ';';
};
$speakersByCountry = array();
foreach ($congress['speakers'] as $speaker) {
  $country = (string) ($speaker['country'] ?? 'Otros');
  $speakersByCountry[$country][] = $speaker;
}
$preferredCountryOrder = array('Brasil', 'Perú', 'Chile', 'China');
$configuredCountryOrder = is_array($congress['countries'] ?? null) ? $congress['countries'] : array();
$countryOrder = array_values(array_unique(array_merge($configuredCountryOrder, $preferredCountryOrder)));
$orderedSpeakersByCountry = array();
foreach ($countryOrder as $country) {
  if (is_string($country) && isset($speakersByCountry[$country])) {
    $orderedSpeakersByCountry[$country] = $speakersByCountry[$country];
    unset($speakersByCountry[$country]);
  }
}
$speakersByCountry = array_merge($orderedSpeakersByCountry, $speakersByCountry);
$countryNames = array_keys($speakersByCountry);
foreach ($congress['countries'] as $country) {
  $country = is_string($country) ? trim($country) : '';
  if ($country !== '' && !in_array($country, $countryNames, true)) {
    $countryNames[] = $country;
  }
}

require __DIR__ . '/includes/header.php';
?>
<?php
$registrationUrl = (string) ($congress['registration_url'] ?? '');
$registrationQr = (string) ($congress['registration_qr'] ?? '');
$registrationScheme = strtolower((string) parse_url($registrationUrl, PHP_URL_SCHEME));
$hasRegistrationUrl = filter_var($registrationUrl, FILTER_VALIDATE_URL) && in_array($registrationScheme, array('http', 'https'), true);
$hasRegistrationQr = $registrationQr !== '';
$hasRegistrationBlock = $hasRegistrationUrl || $hasRegistrationQr;
$registrationPrimaryLink = $hasRegistrationUrl ? $registrationUrl : ($hasRegistrationQr ? $registrationQr : '#');
$payment = is_array($congress['payment'] ?? null) ? $congress['payment'] : array();
$hasSpeakers = !empty($congress['speakers']);
$hasFees = !empty($congress['fees']);
$certifications = array_values(array_filter((array) ($congress['certifications'] ?? array()), static function ($item) {
  return is_string($item) && trim(strip_tags($item)) !== '';
}));
$hasPayment = trim((string) ($payment['bank'] ?? '') . (string) ($payment['holder'] ?? '') . (string) ($payment['account'] ?? '') . (string) ($payment['cci'] ?? '')) !== '' || !empty($payment['yape_enabled']);
$hasSponsors = !empty($congress['sponsors']);
$subtitle = trim((string) ($congress['subtitle'] ?? ''));
$intro = trim((string) ($congress['intro'] ?? ''));
$summary = trim((string) ($congress['summary'] ?? ''));
$congressTime = trim((string) ($congress['time'] ?? ''));
$heroChips = array_filter(array(
  'date' => trim((string) ($congress['date'] ?? '')),
  'time' => $congressTime,
  'modality' => trim((string) ($congress['modality'] ?? '')),
  'venue' => trim((string) ($congress['venue'] ?? '')),
), static function ($value) { return $value !== ''; });
$chipIcons = array(
  'date' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg>',
  'time' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
  'modality' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="12" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
  'venue' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
);
$subnav = array();
if ($hasSpeakers) { $subnav['ponentes'] = 'Ponentes'; }
if ($hasFees || $certifications) { $subnav['tarifas'] = 'Tarifas'; }
if ($hasPayment) { $subnav['pago'] = 'Medios de pago'; }
if ($hasSponsors) { $subnav['auspiciadores'] = 'Auspiciadores'; }
$subnav['contacto'] = 'Contacto';
?>
<main class="congress-page">
  <section class="congress-hero">
    <div class="container congress-hero-inner">
      <?php if (!empty($congress['logo'])): ?>
        <div class="congress-logo-frame">
          <img class="congress-logo" src="<?= $escape($congress['logo']) ?>" alt="<?= $escape($congress['title']) ?>" width="320" height="320">
        </div>
      <?php endif; ?>
      <div class="congress-hero-copy">
        <p class="congress-eyebrow">Fundación para el Desarrollo Universitario</p>
        <h1><?= $escape($congress['title']) ?></h1>
        <?php if ($subtitle !== ''): ?><p class="congress-subtitle"><?= $escape($subtitle) ?></p><?php endif; ?>
        <?php if ($summary !== ''): ?><p class="congress-summary"><?= $escape($summary) ?></p><?php endif; ?>
        <?php if ($intro !== ''): ?><p class="congress-intro"><?= $escape($intro) ?></p><?php endif; ?>
        <?php if ($heroChips): ?>
          <ul class="congress-hero-chips" aria-label="Datos principales">
            <?php foreach ($heroChips as $chipKey => $chipValue): ?>
              <li><?= $chipIcons[$chipKey] ?><span><?= $escape($chipValue) ?></span></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <div class="congress-hero-actions">
          <a class="congress-primary-link" href="<?= $hasRegistrationUrl ? $escape($registrationUrl) : $escape($gmailCompose($congress['contact_email'])) ?>" target="_blank" rel="noopener noreferrer">
            <?= $hasRegistrationUrl ? 'Inscribirme' : 'Solicitar información' ?>
          </a>
          <?php if ($hasSpeakers): ?><a class="congress-secondary-link" href="congreso?id=<?= (int) $congress['id'] ?>#ponentes">Conocer a los ponentes</a><?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <div class="container congress-content">
    <section class="congress-facts" aria-label="Datos del congreso">
      <div class="congress-fact"><span class="congress-fact-icon"><?= $chipIcons['date'] ?></span><div><span>Fecha</span><strong><?= $escape($congress['date']) ?><?= $congressTime !== '' ? ' · ' . $escape($congressTime) : '' ?></strong></div></div>
      <div class="congress-fact"><span class="congress-fact-icon"><?= $chipIcons['venue'] ?></span><div><span>Modalidad y sede</span><strong><?= $escape(trim($congress['modality'] . ' · ' . $congress['venue'], ' ·')) ?></strong></div></div>
      <div class="congress-fact"><span class="congress-fact-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10"/></svg></span><div><span>Inscripción</span><strong><?= $hasRegistrationUrl ? 'Abierta' : 'Por confirmar' ?></strong></div></div>
    </section>
    <?php if (in_array('Por confirmar', array($congress['date'], $congress['modality'], $congress['venue']), true) || !$hasRegistrationUrl): ?>
      <p class="congress-pending-note">Algunos datos aún están por confirmar. Para consultar novedades, escribe a <a href="<?= $escape($gmailCompose($congress['contact_email'])) ?>" target="_blank" rel="noopener noreferrer"><?= $escape($congress['contact_email']) ?></a>.</p>
    <?php endif; ?>

    <nav class="congress-subnav" aria-label="Secciones del congreso">
      <div>
        <?php foreach ($subnav as $anchor => $label): ?>
          <a href="congreso?id=<?= (int) $congress['id'] ?>#<?= $escape($anchor) ?>"><?= $escape($label) ?></a>
        <?php endforeach; ?>
      </div>
    </nav>

    <?php if ($hasSpeakers): ?>
    <section class="congress-section" id="ponentes" aria-labelledby="congress-speakers-title">
      <div class="congress-section-heading">
        <p class="congress-eyebrow"><?= $escape(implode(' · ', $countryNames)) ?></p>
        <h2 id="congress-speakers-title">Ponentes</h2>
        <p>Perfiles y enlaces profesionales de las personas participantes.</p>
      </div>

      <?php foreach ($speakersByCountry as $country => $countrySpeakers): ?>
        <?php if ($countrySpeakers): ?>
          <section class="congress-country" aria-labelledby="country-<?= $escape(strtolower($country)) ?>">
            <h3 id="country-<?= $escape(strtolower($country)) ?>"><?= $flagEntity($countrySpeakers[0]['flag']) ?> Ponentes de <?= $escape($country) ?></h3>
            <div class="congress-speaker-grid">
              <?php foreach ($countrySpeakers as $speaker): ?>
                <?php
                $photoPath = trim((string) ($speaker['photo'] ?? ''));
                if (preg_match('/^[A-Za-z0-9_.-]+\.(?:jpe?g|png|gif|webp)$/i', $photoPath) === 1
                  && is_file(__DIR__ . '/assets/images/congreso/' . $photoPath)) {
                  $photoPath = 'assets/images/congreso/' . $photoPath;
                }
                $isExternalPhoto = filter_var($photoPath, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($photoPath, PHP_URL_SCHEME)), array('http', 'https'), true);
                if (UploadPath::isManagedPath($photoPath)) {
                  $photoPath = UploadPath::normalize($photoPath);
                  $isLocalPhoto = UploadPath::diskPath($photoPath) !== null;
                } else {
                $isLocalPhoto = preg_match('#^(?:assets|uploads)/[A-Za-z0-9_./-]+$#D', $photoPath)
                  && strpos($photoPath, '..') === false
                  && is_file(__DIR__ . '/' . $photoPath);
                }
                $hasPhoto = $isExternalPhoto || $isLocalPhoto;
                ?>
                <article class="congress-speaker-card">
                  <?php if ($hasPhoto): ?>
                    <img class="congress-speaker-photo" src="<?= $escape($photoPath) ?>" alt="Foto de <?= $escape($speaker['name']) ?>" loading="lazy">
                  <?php else: ?>
                    <div class="congress-speaker-photo congress-speaker-photo-placeholder" role="img" aria-label="Foto no incluida para <?= $escape($speaker['name']) ?>">
                      <span><?= $flagEntity($speaker['flag']) ?></span>
                      <small>Foto por confirmar</small>
                    </div>
                  <?php endif; ?>
                  <div class="congress-speaker-copy">
                    <p class="congress-speaker-country"><?= $flagEntity($speaker['flag']) ?> <?= $escape($speaker['country']) ?></p>
                    <h4><?= $escape($speaker['name']) ?></h4>
                    <p class="congress-speaker-institution"><?= $escape($speaker['institution']) ?></p>
                    <details class="congress-speaker-bio">
                      <summary>Ver biografía completa</summary>
                      <div class="congress-speaker-biography"><?= HtmlSanitizer::clean((string) $speaker['biography'], 30000, true) ?? $escape($speaker['biography']) ?></div>
                    </details>
                    <?php if (!empty($speaker['links'])): ?>
                      <div class="congress-speaker-links" aria-label="Enlaces profesionales">
                        <?php foreach ($speaker['links'] as $profileUrl): ?>
                          <?php
                          $scheme = strtolower((string) parse_url($profileUrl, PHP_URL_SCHEME));
                          if (!filter_var($profileUrl, FILTER_VALIDATE_URL) || !in_array($scheme, array('http', 'https'), true)) {
                            continue;
                          }
                          $profileHost = strtolower((string) parse_url($profileUrl, PHP_URL_HOST));
                          $profileLabel = strpos($profileHost, 'orcid.org') !== false
                            ? 'ORCID'
                            : (strpos($profileHost, 'lattes.cnpq.br') !== false
                              ? 'Lattes'
                              : (strpos($profileHost, 'concytec.gob.pe') !== false || strpos($profileHost, 'dina.concytec.gob.pe') !== false
                                ? 'CTI Vitae'
                                : (strpos($profileHost, 'linkedin.com') !== false ? 'LinkedIn' : 'Perfil')));
                          ?>
                          <a href="<?= $escape($profileUrl) ?>" target="_blank" rel="noopener noreferrer"><?= $escape($profileLabel) ?></a>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php if ($hasFees || $certifications): ?>
    <section class="congress-section" id="tarifas" aria-labelledby="congress-fees-title">
      <div class="congress-section-heading">
        <p class="congress-eyebrow">Participación</p>
        <h2 id="congress-fees-title">Tarifas</h2>
        <p>Consulta las tarifas vigentes de participación.</p>
      </div>
      <?php if ($hasFees): ?>
      <div class="congress-fee-grid">
        <?php foreach ($congress['fees'] as $fee): ?>
          <article class="congress-fee-card"><h3><?= $escape($fee['audience']) ?></h3><strong><?= $escape($fee['amount']) ?></strong></article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if ($certifications): ?>
        <div class="congress-certifications">
          <h3>Certificaciones</h3>
          <?php foreach ($certifications as $certification): ?>
            <div class="congress-certification-body"><?= HtmlSanitizer::clean($certification, 12000) ?? $escape($certification) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($hasRegistrationBlock): ?>
    <section class="congress-section congress-registration-cta" aria-labelledby="congress-registration-title">
      <div class="congress-registration-card">
        <div class="congress-registration-copy">
          <p class="congress-eyebrow">Inscripción</p>
          <h2 id="congress-registration-title">Inscríbete aquí</h2>
          <p>Usa el enlace directo o escanea el código QR para completar tu inscripción en segundos.</p>
          <?php if ($hasRegistrationUrl): ?>
            <a class="congress-primary-link" href="<?= $escape($registrationUrl) ?>" target="_blank" rel="noopener noreferrer">Ir al enlace de inscripción</a>
          <?php endif; ?>
        </div>
        <a class="congress-registration-qr" href="<?= $escape($registrationPrimaryLink) ?>" target="<?= $hasRegistrationUrl ? '_blank' : '_self' ?>" rel="<?= $hasRegistrationUrl ? 'noopener noreferrer' : '' ?>" aria-label="Abrir enlace de inscripción mediante QR">
          <?php
            $qrImage = $registrationQr !== '' ? $registrationQr : 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . rawurlencode($registrationUrl) . '&margin=1&ecc=H';
          ?>
          <img src="<?= $escape($qrImage) ?>" alt="Código QR para inscribirse" width="180" height="180" loading="lazy">
        </a>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($hasPayment): ?>
    <section class="congress-section congress-payment-section" id="pago" aria-labelledby="congress-payment-title">
      <div class="congress-section-heading">
        <p class="congress-eyebrow">Inscripción</p>
        <h2 id="congress-payment-title">Medios de pago</h2>
      </div>
      <div class="congress-payment-card">
        <h3><?= $escape($congress['payment']['bank']) ?></h3>
        <dl>
          <div><dt>Titular</dt><dd><?= $escape($congress['payment']['holder']) ?></dd></div>
          <div><dt>Cuenta en soles</dt><dd><span><?= $escape($congress['payment']['account']) ?></span><?php if ($congress['payment']['account'] !== ''): ?><button type="button" class="congress-copy" data-copy="<?= $escape($congress['payment']['account']) ?>" aria-label="Copiar número de cuenta">Copiar</button><?php endif; ?></dd></div>
          <div><dt>CCI</dt><dd><span><?= $escape($congress['payment']['cci']) ?></span><?php if ($congress['payment']['cci'] !== ''): ?><button type="button" class="congress-copy" data-copy="<?= $escape($congress['payment']['cci']) ?>" aria-label="Copiar CCI">Copiar</button><?php endif; ?></dd></div>
        </dl>
        <?php if (!empty($congress['payment']['yape_enabled'])): ?>
<?php
          $walletName = trim((string) ($congress['payment']['wallet_name'] ?? ''));
          $walletKey = mb_strtolower($walletName, 'UTF-8');
          $walletLogo = strpos($walletKey, 'yape') !== false ? 'assets/images/pagos/yape.png' : (strpos($walletKey, 'plin') !== false ? 'assets/images/pagos/plin.png' : '');
          ?>
          <h3 class="congress-wallet-title"><?php if ($walletLogo !== ''): ?><img src="<?= $escape($walletLogo) ?>" alt="" width="34" height="34"><?php endif; ?><span><?= $escape($walletName !== '' ? $walletName : 'Otro medio de pago') ?></span></h3>
          <p class="congress-yape"><span><strong><?= $escape($congress['payment']['yape']) ?></strong><br><?= $escape($congress['payment']['yape_holder']) ?></span><?php if ($congress['payment']['yape'] !== ''): ?><button type="button" class="congress-copy" data-copy="<?= $escape($congress['payment']['yape']) ?>" aria-label="Copiar dato de pago">Copiar</button><?php endif; ?></p>
        <?php endif; ?>
        <?php if ($congress['payment']['note'] !== ''): ?>
          <p class="congress-payment-note"><?= $escape($congress['payment']['note']) ?></p>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($hasSponsors): ?>
    <section class="congress-section congress-sponsors" id="auspiciadores" aria-labelledby="congress-sponsors-title">
      <div>
        <p class="congress-eyebrow">Auspiciadores</p>
        <h2 id="congress-sponsors-title">Organizaciones participantes</h2>
      </div>
      <?php if ($congress['sponsors']): ?>
        <div class="congress-sponsor-list">
          <?php foreach ($congress['sponsors'] as $sponsor): ?>
            <?php
              if (is_string($sponsor)) {
                $sponsor = array('name' => $sponsor, 'logo' => '', 'description' => '');
              }
              $sponsorName = (string) ($sponsor['name'] ?? '');
              $sponsorLogo = (string) ($sponsor['logo'] ?? '');
              $sponsorDescription = (string) ($sponsor['description'] ?? '');
            ?>
            <article class="congress-sponsor-card">
              <?php if ($sponsorLogo !== '' && preg_match('#^(?:https?://|/|assets/)[^\s]+$#i', $sponsorLogo)): ?>
                <img src="<?= $escape($sponsorLogo) ?>" alt="<?= $sponsorName !== '' ? 'Logo de ' . $escape($sponsorName) : 'Logo del auspiciador' ?>" loading="lazy">
              <?php endif; ?>
              <?php if ($sponsorName !== ''): ?>
                <strong><?= $escape($sponsorName) ?></strong>
              <?php endif; ?>
              <?php if ($sponsorDescription !== ''): ?>
                <div><?= HtmlSanitizer::clean($sponsorDescription, 12000) ?? $escape($sponsorDescription) ?></div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="congress-contact" id="contacto" aria-labelledby="congress-contact-title">
      <div class="congress-contact-heading">
        <p class="congress-eyebrow">Estamos para ayudarte</p>
        <h2 id="congress-contact-title">Informes y contacto</h2>
        <p>Comunícate con nuestro equipo para resolver tus consultas sobre el congreso.</p>
      </div>
      <div class="congress-contact-list">
        <a class="congress-contact-item" href="<?= $escape($gmailCompose($congress['contact_email'])) ?>" target="_blank" rel="noopener noreferrer">
          <span class="congress-contact-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
          </span>
          <span class="congress-contact-copy"><small>Correo electrónico</small><strong><?= $escape($congress['contact_email']) ?></strong><span class="congress-contact-action">Escríbenos <span aria-hidden="true">→</span></span></span>
        </a>
        <a class="congress-contact-item" href="tel:+51929932513">
          <span class="congress-contact-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M7 3H4a1 1 0 0 0-1 1c0 9.4 7.6 17 17 17a1 1 0 0 0 1-1v-3l-5-2-2 3a14 14 0 0 1-7-7l3-2-2-6z"/></svg>
          </span>
          <span class="congress-contact-copy"><small>Teléfonos</small><strong>929 932 513 <span aria-hidden="true">/</span> 940 404 384</strong><span class="congress-contact-action">Llámanos <span aria-hidden="true">→</span></span></span>
        </a>
        <a class="congress-contact-item congress-contact-whatsapp" href="https://wa.me/51929932513" target="_blank" rel="noopener noreferrer">
          <span class="congress-contact-icon congress-contact-whatsapp-icon" aria-hidden="true">
            <img src="assets/images/whatsapp-icon.png" alt="" width="40" height="40" loading="lazy">
          </span>
          <span class="congress-contact-copy"><small>WhatsApp</small><strong>929 932 513</strong><span class="congress-contact-action">Escríbenos por WhatsApp <span aria-hidden="true">→</span></span></span>
        </a>
      </div>
    </section>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/congreso-detail.js?v=<?= filemtime(__DIR__ . '/assets/js/congreso-detail.js') ?>"></script>
</body>
</html>
