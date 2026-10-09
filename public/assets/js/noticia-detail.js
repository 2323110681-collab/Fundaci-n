(function() {
  const API_BASE = 'index.php';

  const HTML_TAG_PATTERN = /<\/?[a-z][\s\S]*?>/i;
  const RICH_TAGS = new Set(['P', 'BR', 'STRONG', 'B', 'EM', 'I', 'U', 'S', 'OL', 'UL', 'LI', 'H1', 'H2', 'H3', 'H4', 'BLOCKQUOTE', 'A']);
  const RICH_DROP = new Set(['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'SVG', 'MATH', 'FORM', 'INPUT', 'BUTTON', 'TEXTAREA', 'SELECT', 'LINK', 'META']);

  function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
  }

  async function fetchNoticia(id) {
    try {
      const url = new URL(API_BASE, window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/'));
      url.searchParams.set('resource', 'noticias');
      url.searchParams.set('id', id);

      const res = await fetch(url.toString());
      if (!res.ok) throw new Error('Error al cargar la noticia');
      const json = await res.json();
      return json.data || null;
    } catch (error) {
      console.error(error);
      return null;
    }
  }

  function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Las fechas "YYYY-MM-DD" se leen como fecha local. Con new Date('2026-09-28') el navegador
  // usa UTC y en Perú (UTC-5) terminaba mostrando el día anterior.
  function formatDate(raw) {
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(raw || ''));
    if (!match) return '';
    const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleDateString('es-PE', { day: '2-digit', month: 'long', year: 'numeric' });
  }

  function plainText(value) {
    const text = String(value ?? '');
    if (!HTML_TAG_PATTERN.test(text)) return text.replace(/\s+/g, ' ').trim();
    const doc = new DOMParser().parseFromString(text, 'text/html');
    doc.body.querySelectorAll('p, li, br, h1, h2, h3, h4, blockquote').forEach(el => el.after(' '));
    return (doc.body.textContent || '').replace(/\s+/g, ' ').trim();
  }

  // Muestra el contenido con su formato (negrita, listas, enlaces...) y descarta todo lo demás.
  // Las noticias antiguas, guardadas como texto simple, se convierten en párrafos.
  function renderRichText(value) {
    const text = String(value ?? '').replace(/\r\n?/g, '\n').trim();
    if (!text) return '';
    if (!HTML_TAG_PATTERN.test(text)) {
      return text.split(/\n{2,}/)
        .map(paragraph => paragraph.trim())
        .filter(Boolean)
        .map(paragraph => `<p>${escapeHtml(paragraph).replace(/\n/g, '<br>')}</p>`)
        .join('');
    }
    const doc = new DOMParser().parseFromString(text, 'text/html');
    const clean = node => {
      Array.from(node.childNodes).forEach(child => {
        if (child.nodeType === 3) return;
        if (child.nodeType !== 1 || RICH_DROP.has(child.tagName.toUpperCase())) { child.remove(); return; }
        clean(child);
        const tag = child.tagName.toUpperCase();
        if (!RICH_TAGS.has(tag)) { child.replaceWith(...child.childNodes); return; }
        const href = tag === 'A' ? (child.getAttribute('href') || '').trim() : '';
        Array.from(child.attributes).forEach(attr => child.removeAttribute(attr.name));
        if (tag === 'A' && /^(https?:|mailto:|tel:)/i.test(href)) {
          child.setAttribute('href', href);
          child.setAttribute('target', '_blank');
          child.setAttribute('rel', 'noopener noreferrer');
        }
      });
    };
    clean(doc.body);
    return doc.body.innerHTML;
  }

  function parseSourceUrl(value) {
    try {
      const url = new URL(String(value || '').trim());
      return url.protocol === 'http:' || url.protocol === 'https:' ? url : null;
    } catch (error) {
      return null;
    }
  }

  function renderNoticia(noticia) {
    const imageEl = document.getElementById('detailImage');
    const categoryEl = document.getElementById('detailCategory');
    const titleEl = document.getElementById('detailTitle');
    const dateEl = document.getElementById('detailDate');
    const authorEl = document.getElementById('detailAuthor');
    const descriptionEl = document.getElementById('detailDescription');
    const contentEl = document.getElementById('detailContent');
    const linkEl = document.getElementById('detailLink');

    if (!noticia) {
      if (titleEl) titleEl.textContent = 'Noticia no encontrada';
      if (dateEl) dateEl.hidden = true;
      if (descriptionEl) {
        descriptionEl.textContent = 'No se encontró la noticia solicitada.';
        descriptionEl.hidden = false;
      }
      if (linkEl) linkEl.hidden = true;
      return;
    }

    if (imageEl) {
      imageEl.src = noticia.imagen || 'assets/images/placeholder-news.jpg';
      imageEl.alt = `Imagen de ${noticia.titulo || 'Noticia'}`;
    }
    if (categoryEl) categoryEl.textContent = noticia.categoria || 'Noticia';
    if (titleEl) titleEl.textContent = noticia.titulo || 'Noticia sin título';

    if (dateEl) {
      const eventDate = formatDate(noticia.fecha_evento);
      const updatedDate = formatDate(noticia.updated_at);
      const label = eventDate || (updatedDate ? `Actualizada el ${updatedDate}` : '');
      dateEl.textContent = label;
      dateEl.hidden = !label;
    }
    if (authorEl) {
      authorEl.textContent = noticia.autor ? `Por ${noticia.autor}` : '';
      authorEl.hidden = !noticia.autor;
    }

    // Resumen y cuerpo: nunca se muestra el mismo texto dos veces.
    const summaryText = plainText(noticia.descripcion_corta);
    const bodyText = plainText(noticia.contenido);
    const showSummary = summaryText !== '';
    const showBody = bodyText !== '' && bodyText !== summaryText;
    if (descriptionEl) {
      descriptionEl.textContent = showSummary ? summaryText : '';
      descriptionEl.hidden = !showSummary;
    }
    if (contentEl) {
      contentEl.innerHTML = showBody ? renderRichText(noticia.contenido) : '';
      contentEl.hidden = !showBody;
    }

    // Enlace a la fuente: solo si existe y es http(s).
    if (linkEl) {
      const source = parseSourceUrl(noticia.link);
      if (source) {
        const hostname = source.hostname.replace(/^www\./i, '');
        linkEl.href = source.href;
        linkEl.textContent = `Leer fuente completa · ${hostname} ↗`;
        linkEl.hidden = false;
      } else {
        linkEl.removeAttribute('href');
        linkEl.textContent = '';
        linkEl.hidden = true;
      }
    }
  }

  async function init() {
    const id = getQueryParam('id');
    if (!id) {
      renderNoticia(null);
      return;
    }

    const noticia = await fetchNoticia(id);
    renderNoticia(noticia);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
