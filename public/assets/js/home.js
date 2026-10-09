(function(){
  const apiBase = 'index.php';

  async function fetchData(resource, limit = 6, view = '') {
    try {
      const viewParam = view ? `&view=${encodeURIComponent(view)}` : '';
      const res = await fetch(`${apiBase}?resource=${resource}${limit?`&limit=${limit}`:''}${viewParam}`);
      if (!res.ok) throw new Error(res.statusText || 'Error fetching');
      const json = await res.json();
      return json.data || [];
    } catch (err) {
      console.error('fetchData', resource, err);
      return [];
    }
  }

  function renderProgramas(container, items, congress) {
    if (!container) return;
    container.innerHTML = '';
    const latestCongresses = Array.isArray(congress) ? congress.slice(-3) : (congress ? [congress] : []);
    if (!items.length && !latestCongresses.length) {
      container.innerHTML = '<p>No hay programas disponibles.</p>';
      return;
    }

    items.forEach(p => {
      const card = document.createElement('article');
      card.className = 'card program-card';
      const categoryLabel = escapeHtml(p.categoria || 'General');
      const titleText = escapeHtml(p.titulo || 'Sin título');
      const descriptionText = escapeHtml(htmlToPlainText(p.descripcion));
      const authorText = escapeHtml(p.autor || '');
      const programLink = p.id ? `programas?id=${encodeURIComponent(p.id)}` : (p.link || '#');
      card.innerHTML = `
        <div class="card-media">
          <img src="${escapeHtml(p.imagen || 'assets/images/placeholder-programa.jpg')}" alt="${titleText}">
          <span class="card-tag">Programa · ${categoryLabel}</span>
        </div>
        <div class="card-body">
          <h3 class="card-title">
            <a href="${escapeHtml(programLink)}">${titleText}</a>
          </h3>
          <p class="card-description">${descriptionText}</p>
          ${authorText ? `<p class="card-partner">${authorText}</p>` : ''}
        </div>
      `;
      container.appendChild(card);
    });

    latestCongresses.forEach(item => {
      const titleText = escapeHtml(item.title || item.titulo || 'Congreso');
      const logo = escapeHtml(item.logo || 'assets/images/congreso/logo-cdia.jpg');
      const link = escapeHtml(item.link || `congreso?id=${encodeURIComponent(item.id || 1)}`);
      const description = htmlToPlainText(item.summary || item.descripcion || item.intro || item.subtitle || item.subtitulo || '');
      const descriptionText = escapeHtml(description || 'Conoce a nuestros ponentes, tarifas y opciones de inscripción.');
      const congressTime = item.time ? `<p class="card-partner">Hora: ${escapeHtml(item.time)}</p>` : '';
      const card = document.createElement('article');
      card.className = 'card program-card congress-card';
      card.innerHTML = `
        <div class="card-media">
          <img src="${logo}" alt="Logo de ${titleText}">
          <span class="card-tag">Congreso</span>
        </div>
        <div class="card-body">
          <h3 class="card-title"><a href="${link}">${titleText}</a></h3>
          <p class="card-description">${descriptionText}</p>
          ${congressTime}
          <a class="link-gold" href="${link}">Ver informaci\u00f3n del congreso</a>
        </div>
      `;
      container.appendChild(card);
    });
  }

  function renderNoticias(container, items) {
    if (!container) return;
    container.innerHTML = '';
    if (!items.length) {
      container.innerHTML = '<p>No hay noticias.</p>';
      return;
    }

    items.forEach(n => {
      const item = document.createElement('div');
      item.className = 'news-item';

      const thumb = document.createElement('div');
      thumb.className = 'news-thumb';
      const img = document.createElement('img');
      img.src = n.imagen || 'assets/images/placeholder-news.jpg';
      img.alt = n.titulo || 'Noticia';
      thumb.appendChild(img);

      const content = document.createElement('div');
      content.className = 'news-content';

      const meta = document.createElement('p');
      meta.className = 'news-meta';
      meta.textContent = formatDate(n.fecha_evento || n.updated_at || '');

      const title = document.createElement('h3');
      title.className = 'news-title';
      const newsLink = n.id ? `noticia?id=${encodeURIComponent(n.id)}` : (n.link || '#');
      const titleLink = document.createElement('a');
      titleLink.href = newsLink;
      titleLink.textContent = n.titulo || '';
      title.appendChild(titleLink);

      const excerpt = document.createElement('p');
      excerpt.className = 'news-excerpt';
      excerpt.textContent = n.descripcion_corta || htmlToPlainText(n.contenido) || '';

      const more = document.createElement('a');
      more.className = 'link-gold';
      more.href = newsLink;
      more.textContent = 'Leer noticia';

      content.appendChild(meta);
      content.appendChild(title);
      content.appendChild(excerpt);
      if (n.autor) {
        const author = document.createElement('p');
        author.className = 'news-author';
        author.textContent = `Por ${n.autor}`;
        content.appendChild(author);
      }
      content.appendChild(more);

      item.appendChild(thumb);
      item.appendChild(content);
      container.appendChild(item);
    });
  }

  function renderAgenda(container, items) {
    if (!container) return;
    container.innerHTML = '';
    if (!items.length) {
      container.innerHTML = '<p>No hay eventos en la agenda.</p>';
      return;
    }

    items.forEach(e => {
      const ev = document.createElement('div');
      ev.className = 'agenda-item';

      const dateBox = document.createElement('div');
      dateBox.className = 'agenda-date';
      const parts = formatDateParts(e.fecha_evento || '');
      const endParts = e.fecha_fin && e.fecha_fin !== e.fecha_evento ? formatDateParts(e.fecha_fin) : null;
      const day = document.createElement('div');
      day.className = 'day';
      day.textContent = parts.day || '';
      const month = document.createElement('div');
      month.className = 'month';
      month.textContent = parts.month || '';
      dateBox.appendChild(day);
      dateBox.appendChild(month);
      if (endParts) {
        const dateRange = document.createElement('small');
        dateRange.className = 'agenda-date-end';
        dateRange.textContent = `al ${endParts.day} ${endParts.month}`;
        dateBox.appendChild(dateRange);
      }

      const body = document.createElement('div');
      body.className = 'agenda-body';

      const title = document.createElement('h4');
      title.className = 'agenda-title';
      const titleLink = document.createElement('a');
      titleLink.href = e.id ? `evento?id=${encodeURIComponent(e.id)}` : 'calendario';
      titleLink.textContent = e.titulo || '';
      title.appendChild(titleLink);

      const meta = document.createElement('div');
      meta.className = 'agenda-meta';
      // clock icon + time and place
      const clock = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 7v6l4 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/></svg>';
      const timePlace = `${escapeHtml(e.hora_evento || '')} ${e.lugar ? '· ' + escapeHtml(e.lugar) : ''}`;
      meta.innerHTML = `${clock} ${timePlace}`;

      const link = document.createElement('a');
      link.className = 'agenda-link';
      link.href = e.link_inscripcion || e.link || '#';
      link.textContent = e.link_inscripcion || e.link ? 'Registrarse' : 'Detalles';

      body.appendChild(title);
      body.appendChild(meta);
      body.appendChild(link);

      ev.appendChild(dateBox);
      ev.appendChild(body);
      container.appendChild(ev);
    });
  }

  function formatDate(raw) {
    if (!raw) return '';
    const d = parseLocalDate(raw);
    if (isNaN(d)) return raw;
    return d.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  function parseLocalDate(raw) {
    const match = String(raw).match(/^(\d{4})-(\d{2})-(\d{2})$/);
    return match
      ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
      : new Date(raw);
  }

  function formatDateParts(raw) {
    if (!raw) return { day: '', month: '', dateStr: '' };
    const d = parseLocalDate(raw);
    if (isNaN(d)) return { day: '', month: '', dateStr: raw };
    const day = String(d.getDate()).padStart(2, '0');
    const month = d.toLocaleString('es-ES', { month: 'short' }).toUpperCase().slice(0,3);
    const dateStr = d.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });
    return { day, month, dateStr };
  }

  function htmlToPlainText(value){
    const text = String(value ?? '');
    if (!/<\/?[a-z][\s\S]*?>/i.test(text)) return text.trim();
    const doc = new DOMParser().parseFromString(text, 'text/html');
    doc.body.querySelectorAll('p, li, br, h1, h2, h3, h4, blockquote').forEach(el => el.after(' '));
    return (doc.body.textContent || '').replace(/\s+/g, ' ').trim();
  }

  function escapeHtml(text){
    if (text === null || text === undefined) return '';
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  async function init(){
    const programasContainer = document.getElementById('programasContainer');
    const noticiasContainer = document.getElementById('noticiasContainer');
    const agendaContainer = document.getElementById('agendaContainer');

    const [programas, noticias, agenda, congressItems] = await Promise.all([
      fetchData('programas', 3),
      fetchData('noticias', 3),
      fetchData('agenda', 3),
      fetchData('congreso', 0, 'summary'),
    ]);

    renderProgramas(programasContainer, programas, congressItems);
    renderNoticias(noticiasContainer, noticias);
    renderAgenda(agendaContainer, agenda);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
