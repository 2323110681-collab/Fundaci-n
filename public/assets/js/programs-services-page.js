(() => {
  const API_BASE = 'index.php';
  const PAGE_SIZE = 6;
  let currentPage = 1;

  function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
  }

  async function fetchProgramas(id = null, limit = null) {
    try {
      const url = new URL(API_BASE, window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/'));
      url.searchParams.set('resource', 'programas');
      if (id) url.searchParams.set('id', String(id));
      if (limit) url.searchParams.set('limit', String(limit));
      const res = await fetch(url.toString());
      if (!res.ok) throw new Error('Error al cargar programas');
      const json = await res.json();
      return json.data || null;
    } catch (err) {
      console.error('fetchProgramas', err);
      return id ? null : [];
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

  const htmlToPlainText = value => {
    const text = String(value ?? '');
    if (!/<\/?[a-z][\s\S]*?>/i.test(text)) return text.trim();
    const doc = new DOMParser().parseFromString(text, 'text/html');
    doc.body.querySelectorAll('p, li, br, h1, h2, h3, h4, blockquote').forEach(el => el.after(' '));
    return (doc.body.textContent || '').replace(/\s+/g, ' ').trim();
  };

  // Muestra la descripción con formato (negrita, listas, enlaces...) y elimina todo lo demás.
  const RICH_TAGS = new Set(['P', 'BR', 'STRONG', 'B', 'EM', 'I', 'U', 'S', 'OL', 'UL', 'LI', 'H1', 'H2', 'H3', 'H4', 'BLOCKQUOTE', 'A']);
  const RICH_DROP = new Set(['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'SVG', 'MATH', 'FORM', 'INPUT', 'BUTTON', 'TEXTAREA', 'SELECT', 'LINK', 'META']);
  const renderRichText = value => {
    const text = String(value ?? '').trim();
    if (!text) return '';
    if (!/<\/?[a-z][\s\S]*?>/i.test(text)) return `<p>${escapeHtml(text).replace(/\n/g, '<br>')}</p>`;
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
  };


  function normalizeText(value) {
    return String(value ?? '')
      .trim()
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  // Antes existia populateFilterOptions(), que hacia lo mismo que renderFilters()
  // y nunca llego a invocarse: se elimino para que solo haya una fuente de verdad.
  function renderFilters(programas) {
    const filtersContainer = document.getElementById('program-filters');
    if (!filtersContainer) return;

    const categorias = [...new Set((programas || []).map(p => p.categoria).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'es', { sensitivity: 'base' }));
    // El campo de la base se llama "autor", pero en programas guarda la
    // institucion asociada (UNESCO, MIT, AWS...), no a un autor.
    const instituciones = [...new Set((programas || []).map(p => p.autor).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'es', { sensitivity: 'base' }));

    const categoryOptions = ['<option value="">Categoría</option>']
      .concat(categorias.map(value => `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`))
      .join('');

    const convenioOptions = ['<option value="">Convenio</option>']
      .concat(instituciones.map(value => `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`))
      .join('');

    filtersContainer.innerHTML = `
      <div class="filter-bar">
        <div class="filter-controls">
          <span class="filter-label">FILTRADO POR:</span>
          <select class="filter-select" id="filter-category">
            ${categoryOptions}
          </select>
          <select class="filter-select" id="filter-convenio">
            ${convenioOptions}
          </select>
        </div>
        <button class="btn-clear-filters" type="button">Eliminar Filtros</button>
      </div>
    `;
  }

  function renderList(container, items) {
    if (!container) return;
    container.innerHTML = '';
    if (!items || items.length === 0) {
      container.innerHTML = '<p>No hay programas disponibles.</p>';
      return;
    }

    items.forEach(p => {
      const card = document.createElement('article');
      card.className = 'program-card';
      const img = escapeHtml(p.imagen || 'assets/images/placeholder-programa.jpg');
      const title = escapeHtml(p.titulo || 'Sin título');
      const cat = escapeHtml(p.categoria || 'General');
      const partner = escapeHtml(p.autor || '');
      const link = p.id ? `programas?id=${encodeURIComponent(p.id)}` : (p.link || '#');
      const description = escapeHtml(htmlToPlainText(p.descripcion).slice(0, 220));

      card.innerHTML = `
        <div class="program-card-img">
          <img src="${img}" alt="${title}">
          <span class="card-tag">${cat}</span>
        </div>
        <div class="program-card-body">
          <h3><a href="${escapeHtml(link)}">${title}</a></h3>
          <p>${description}</p>
          ${partner ? `<div class="program-partner"><span>${partner}</span></div>` : ''}
        </div>
      `;

      container.appendChild(card);
    });
  }

  function renderPagination(container, totalItems, onPageChange) {
    if (!container) return;
    container.innerHTML = '';
    const totalPages = Math.ceil(totalItems / PAGE_SIZE);
    if (totalItems <= PAGE_SIZE) return;

    for (let page = 1; page <= totalPages; page += 1) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = `page-btn ${page === currentPage ? 'active' : ''}`;
      button.textContent = String(page);
      button.setAttribute('aria-label', `Página ${page}`);
      if (page === currentPage) button.setAttribute('aria-current', 'page');
      button.addEventListener('click', () => {
        currentPage = page;
        onPageChange();
      });
      container.appendChild(button);
    }
  }

  function renderDetail(container, item) {
    if (!container) return;
    if (!item) {
      container.innerHTML = '<p>Programa no encontrado.</p>';
      return;
    }
    const img = escapeHtml(item.imagen || 'assets/images/placeholder-programa.jpg');
    const title = escapeHtml(item.titulo || 'Sin título');
    const cat = escapeHtml(item.categoria || 'General');
    const partner = escapeHtml(item.autor || '');
    const content = renderRichText(item.descripcion);
    const textSection = (title, value) => value
      ? `<section class="program-info-section"><h2>${title}</h2><p>${escapeHtml(value).replace(/\n/g, '<br>')}</p></section>`
      : '';
    const facts = [
      ['Duración', item.duracion],
      ['Modalidad', item.modalidad ? ({ presencial: 'Presencial', virtual: 'Virtual', hibrida: 'Híbrida' }[item.modalidad] || item.modalidad) : ''],
      ['Horario', item.horario],
      ['Frecuencia', item.frecuencia],
      ['Fecha de inicio', item.fecha_inicio],
      ['Fecha de término', item.fecha_fin],
      ['Lugar', item.lugar],
    ].filter(([, value]) => value);
    const factsHtml = facts.length
      ? `<dl class="program-facts">${facts.map(([label, value]) => `<div><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(value)}</dd></div>`).join('')}</dl>`
      : '';
    const modules = Array.isArray(item.temario) ? item.temario : [];
    const modulesHtml = modules.length
      ? `<section class="program-info-section"><h2>Temario</h2><ol class="program-modules">${modules.map((module, index) => `<li><span class="module-number">${String(index + 1).padStart(2, '0')}</span><div><h3>${escapeHtml(module.titulo || '')}</h3>${module.descripcion ? `<p>${escapeHtml(module.descripcion)}</p>` : ''}</div></li>`).join('')}</ol></section>`
      : '';
    const teachers = Array.isArray(item.docentes) ? item.docentes : [];
    const teachersHtml = teachers.length
      ? `<section class="program-info-section"><h2>Docentes y expositores</h2><div class="program-teachers">${teachers.map(teacher => {
        const teacherEmail = escapeHtml(teacher.correo || '');
        const linkedin = /^https?:\/\//i.test(teacher.linkedin || '') ? escapeHtml(teacher.linkedin) : '';
        const photo = /^https?:\/\//i.test(teacher.foto || '') || /^\/(?!\/)/.test(teacher.foto || '') ? escapeHtml(teacher.foto) : '';
        return `<article class="program-teacher">${photo ? `<img src="${photo}" alt="${escapeHtml(teacher.nombre || 'Docente')}" loading="lazy">` : '<div class="program-teacher-placeholder" aria-hidden="true"></div>'}<div><h3>${escapeHtml(teacher.nombre || '')}</h3>${teacher.cargo ? `<p class="teacher-role">${escapeHtml(teacher.cargo)}</p>` : ''}${teacher.descripcion ? `<p>${escapeHtml(teacher.descripcion)}</p>` : ''}<div class="teacher-links">${teacherEmail ? `<a href="mailto:${teacherEmail}">${teacherEmail}</a>` : ''}${linkedin ? `<a href="${linkedin}" target="_blank" rel="noopener noreferrer">LinkedIn</a>` : ''}</div></div></article>`;
      }).join('')}</div></section>`
      : '';
    const whatsappNumber = String(item.contacto_whatsapp || '').replace(/\D/g, '');
    const phoneNumber = String(item.contacto_telefono || '').replace(/[^\d+]/g, '');
    const contactLinks = [
      phoneNumber ? `<a href="tel:${escapeHtml(phoneNumber)}">${escapeHtml(item.contacto_telefono)}</a>` : '',
      whatsappNumber ? `<a href="https://wa.me/${escapeHtml(whatsappNumber)}" target="_blank" rel="noopener noreferrer">WhatsApp: ${escapeHtml(item.contacto_whatsapp)}</a>` : '',
      item.contacto_correo ? `<a href="mailto:${escapeHtml(item.contacto_correo)}">${escapeHtml(item.contacto_correo)}</a>` : '',
    ].filter(Boolean);
    const enrollmentHtml = contactLinks.length || item.inversion || item.descuentos || (item.vacantes !== null && item.vacantes !== undefined && item.vacantes !== '')
      ? `<aside class="program-enrollment"><h2>Inscripción</h2>${item.inversion ? `<p><strong>Inversión:</strong> ${escapeHtml(item.inversion)}</p>` : ''}${item.descuentos ? `<p><strong>Descuentos:</strong> ${escapeHtml(item.descuentos)}</p>` : ''}${item.vacantes !== null && item.vacantes !== undefined && item.vacantes !== '' ? `<p><strong>Vacantes disponibles:</strong> ${escapeHtml(item.vacantes)}</p>` : ''}${contactLinks.length ? `<div class="program-contact-links">${contactLinks.join('')}</div>` : ''}</aside>`
      : '';

    container.innerHTML = `
      <div class="program-detail">
        <div class="program-detail-media">
          <img src="${img}" alt="${title}">
          <span class="card-tag">${cat}</span>
        </div>
        <div class="program-detail-body">
          <h1>${title}</h1>
          ${partner ? `<p class="program-partner">${partner}</p>` : ''}
          ${content ? `<div class="program-content">${content}</div>` : ''}
          ${factsHtml}
          <div class="program-detail-columns">
            <div class="program-detail-main">
              ${textSection('Dirigido a', item.dirigido_a)}
              ${textSection('Objetivos', item.objetivos)}
              ${modulesHtml}
              ${textSection('Requisitos', item.requisitos)}
              ${textSection('Certificación', item.certificacion)}
              ${teachersHtml}
            </div>
            ${enrollmentHtml}
          </div>
        </div>
      </div>
    `;
  }

  async function init() {
    const listContainer = document.querySelector('.programs-grid');
    const paginationContainer = document.getElementById('program-pagination');
    const detailContainer = document.querySelector('.programs-page-section .container');
    const id = getQueryParam('id');

    if (id) {
      const section = document.querySelector('.programs-page-section');
      if (section) section.classList.add('is-detail-view');

      const item = await fetchProgramas(id);
      const mainContent = document.querySelector('.programs-page-section .container');
      if (mainContent) {
        mainContent.querySelector('.filter-bar')?.remove();
        mainContent.querySelector('.pagination')?.remove();

        const detailWrapper = document.createElement('div');
        detailWrapper.className = 'program-detail-wrapper';
        mainContent.querySelector('.programs-grid')?.remove();
        mainContent.insertBefore(detailWrapper, mainContent.querySelector('.pagination'));
        renderDetail(detailWrapper, item);
      }
      return;
    }

    const programas = await fetchProgramas(null, 0);
    renderFilters(programas || []);
    let visibleProgramas = programas || [];
    const renderProgramPage = () => {
      const start = (currentPage - 1) * PAGE_SIZE;
      renderList(listContainer, visibleProgramas.slice(start, start + PAGE_SIZE));
      renderPagination(paginationContainer, visibleProgramas.length, renderProgramPage);
    };
    renderProgramPage();

    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(sel => sel.addEventListener('change', () => {
      const selCat = document.getElementById('filter-category')?.value || '';
      const selConv = document.getElementById('filter-convenio')?.value || '';
      const normalizedCat = normalizeText(selCat);
      const normalizedConv = normalizeText(selConv);

      const filtered = (programas || []).filter(p => {
        const matchCat = !normalizedCat || normalizeText(p.categoria || '').includes(normalizedCat);
        const matchConv = !normalizedConv || normalizeText(p.autor || '').includes(normalizedConv);
        return matchCat && matchConv;
      });

      visibleProgramas = filtered;
      currentPage = 1;
      renderProgramPage();
    }));

    const clearBtn = document.querySelector('.btn-clear-filters');
    if (clearBtn) clearBtn.addEventListener('click', () => {
      document.querySelectorAll('.filter-select').forEach(s => s.value = '');
      visibleProgramas = programas || [];
      currentPage = 1;
      renderProgramPage();
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();

})();
