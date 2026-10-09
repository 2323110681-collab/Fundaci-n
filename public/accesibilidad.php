<?php
$pageTitle = 'Declaración de accesibilidad - FDU';
$activePage = '';
$pageStyles = array();
require __DIR__ . '/includes/header.php';
?>
<main class="section institutional-page reference-page">
  <div class="container">
    <header class="reference-hero">
      <span class="eyebrow">Accesibilidad</span>
      <h1 class="page-title">Declaración de accesibilidad</h1>
      <p>Fundación para el Desarrollo Universitario de Lima Sur está comprometida con que su sitio web sea utilizable por el mayor número de personas, incluidas aquellas con discapacidad.</p>
    </header>

    <section class="reference-section">
      <span class="eyebrow">Compromiso</span>
      <h2>Nuestro compromiso</h2>
      <p>Queremos asegurar que nuestro sitio web sea accesible para todas las personas, especialmente para aquellas con discapacidad visual, auditiva, motora, cognitiva o con cualquier otra limitación permanente o temporal.</p>
      <p>Este sitio se ha desarrollado pensando en cumplir con las Pautas de Accesibilidad para el Contenido Web (WCAG) en su nivel 2.1, nivel AA. El sitio incluye también herramientas de asistencia activadas desde la barra superior: aumento del tamaño del texto, modo de alto contraste y lectura en voz alta de la página.</p>
    </section>

    <section class="reference-section">
      <span class="eyebrow">Estado de cumplimiento</span>
      <h2>Contraste y navegación</h2>
      <div class="institutional-grid">
        <article>
          <h3>Contraste visual</h3>
          <p>Los colores de texto y fondo se han calculado para superar la razón de contraste mínima de 4.5:1 exigida por las WCAG 2.1 AA en el texto normal, tanto en el tema claro como en el oscuro y en el modo de alto contraste.</p>
        </article>
        <article>
          <h3>Navegación por teclado</h3>
          <p>Todos los enlaces, botones y campos de formulario se recorren con la tecla Tab y se activan con Enter o Espacio. El foco es siempre visible mediante un contorno de alto contraste.</p>
        </article>
        <article>
          <h3>Estructura y lectores de pantalla</h3>
          <p>Las páginas utilizan encabezados jerárquicos, puntos de referencia semánticos y textos alternativos en las imágenes. Los cambios de estado se anuncian mediante atributos <code>aria-live</code> y <code>aria-pressed</code>.</p>
        </article>
        <article>
          <h3>Formularios</h3>
          <p>Cada campo de formulario tiene una etiqueta asociada, los mensajes de error se identifican más allá del color y los campos obligatorios se señalan con texto, no solo con asteriscos.</p>
        </article>
      </div>
    </section>

    <section class="reference-section">
      <span class="eyebrow">Contenido no accesible</span>
      <h2>Limitaciones conocidas</h2>
      <p>Junto a los contenidos anteriores, pueden presentar barreras para algunas personas los siguientes:
      <ul>
        <li>Los documentos descargables en formato PDF pueden no incluir etiquetas de accesibilidad. Se están revisando de forma prioritaria.</li>
        <li>Los mapas incrustados de Google Maps dependen de un servicio de terceros sobre el que no tenemos control. Disponen de una descripción textual alternativa en el pie de página.</li>
        <li>Algunos contenidos publicados por terceros o incrustados pueden no cumplir todavía estas pautas.</li>
      </ul>
    </section>

    <section class="reference-section">
      <span class="eyebrow">Medidas adoptadas</span>
      <h2>Cómo evaluamos este sitio</h2>
      <p>La revisión de accesibilidad se realiza de forma periódica mediante combinación de pruebas automáticas y revisión manual con teclado y lector de pantalla. Los resultados se registran y dan lugar a un plan de corrección con plazos definidos.</p>
      <p class="reference-highlight">Este sitio fue actualizado por última vez el <?= date('d/m/Y') ?>.</p>
    </section>

    <section class="reference-section">
      <span class="eyebrow">Contacto</span>
      <h2>Comuníquese con nosotros</h2>
      <p>Si encuentra un contenido inaccesible o necesita la información en un formato alternativo, puede comunicárselo por cualquiera de estos medios:</p>
      <div class="institutional-grid">
        <article>
          <h3>Correo electrónico</h3>
          <p><a href="mailto:informes@fundaciondu.org">informes@fundaciondu.org</a></p>
        </article>
        <article>
          <h3>Teléfono</h3>
          <p>+51 916 330 009</p>
        </article>
        <article>
          <h3>Dirección</h3>
          <p>Av. Bolivar S/N, sector 3 grupo 1, mz. A, sublote 3 - Villa El Salvador</p>
        </article>
        <article>
          <h3>Tiempo de respuesta</h3>
          <p>Atendemos las solicitudes en un plazo máximo de 20 días hábiles, conforme a la normativa de protección de datos personales.</p>
        </article>
      </div>
    </section>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
