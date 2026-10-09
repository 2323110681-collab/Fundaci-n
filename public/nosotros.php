<?php
$pageTitle = 'Nosotros - FDU';
$activePage = 'nosotros';
$pageStyles = array();
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
Autoloader::register();
$members = array();
try {
  $database = new Database();
  $members = (new Miembros($database->getConnection()))->readAll();
} catch (Throwable $exception) {
  $members = array();
}
require __DIR__ . '/includes/header.php';
?>
<main class="section institutional-page reference-page">
  <div class="container">
    <header class="reference-hero">
      <span class="eyebrow">Conoce quiénes somos</span>
      <h1 class="page-title">Nosotros</h1>
      <p>Conoce quiénes somos, qué nos impulsa y los valores que guían nuestro compromiso con el desarrollo universitario del sur de Lima.</p>
    </header>

    <section class="reference-section">
      <span class="eyebrow">Quiénes somos</span>
      <div class="who-we-are-grid">
        <div>
          <h2>Una institución al servicio del desarrollo de Lima Sur</h2>
          <p>La Fundación para el Desarrollo Universitario de Lima Sur (FDU Lima Sur) es una institución orientada a la investigación aplicada, la formación profesional y la extensión universitaria en el sur de Lima.</p>
          <p>Ejecuta programas, proyectos, capacitación, asistencia técnica, consultoría e investigación aplicada, en coordinación con entidades públicas y privadas, instituciones académicas y organismos nacionales e internacionales.</p>
          <p class="reference-highlight">Alianza estratégica: Convenio Marco vigente con la UNTELS para el desarrollo de proyectos académicos conjuntos.</p>
        </div>
        <div class="foundation-image-frame"><img src="assets/images/fundacion.png" alt="Fundación para el Desarrollo Universitario"></div>
      </div>
    </section>

    <section class="reference-section">
      <span class="eyebrow">Nuestro norte</span>
      <h2>Misión y Visión</h2>
      <div class="institutional-grid">
        <article><h3>Misión</h3><p>Somos una fundación dedicada a promover la investigación aplicada, la formación profesional y la extensión universitaria en Lima Sur, generando conocimiento, capacidades e innovación que contribuyan al desarrollo educativo y social de la comunidad.</p></article>
        <article><h3>Visión</h3><p>Ser reconocida hacia el 2030 como la institución articuladora referente en el desarrollo universitario de Lima Sur, líder en investigación aplicada, innovación educativa y responsabilidad social.</p></article>
      </div>
    </section>

    <section class="reference-section">
      <span class="eyebrow">Nuestros principios</span>
      <h2>Valores Institucionales</h2>
      <div class="values-grid">
        <article><strong>01</strong><h3>Excelencia académica</h3><p>Búsqueda permanente de la calidad en investigación, formación y extensión.</p></article>
        <article><strong>02</strong><h3>Innovación</h3><p>Apertura a nuevos enfoques, metodologías y soluciones educativas.</p></article>
        <article><strong>03</strong><h3>Responsabilidad social</h3><p>Compromiso con el bienestar y el desarrollo sostenible de Lima Sur.</p></article>
        <article><strong>04</strong><h3>Cooperación institucional</h3><p>Alianzas efectivas con entidades públicas, privadas y académicas.</p></article>
        <article><strong>05</strong><h3>Transparencia</h3><p>Gestión clara y rendición de cuentas ante la Junta y los grupos de interés.</p></article>
        <article><strong>06</strong><h3>Compromiso ético</h3><p>Integridad en el uso de recursos de convenios, cooperación y donaciones.</p></article>
      </div>
    </section>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
