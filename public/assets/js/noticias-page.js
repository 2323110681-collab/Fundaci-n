(function() {
  const API_BASE = 'index.php';
  const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  const pageSize = 6;

  const featuredContainer = document.querySelector('.featured-news-card');
  const newsGrid = document.getElementById('newsGrid');
  const paginationContainer = document.getElementById('paginationContainer');
  const categoryContainer = document.getElementById('categoryList');
  const archiveContainer = document.getElementById('archiveContainer');
  const pageInfo = document.getElementById('pageInfo');
  const searchInput = document.getElementById('newsSearchInput');
  const searchButton = document.getElementById('newsSearchButton');

  let allNews = [];
  let selectedCategory = '';
  let selectedArchive = '';
  let searchQuery = new URLSearchParams(window.location.search).get('q') || '';
  let currentPage = 1;

  if (searchInput) searchInput.value = searchQuery;

  async function fetchNoticias() {
    try {
      const url = new URL(API_BASE, window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/'));
      url.searchParams.set('resource', 'noticias');
      const response = await fetch(url.toString());
      if (!response.ok) throw new Error('Error al cargar noticias');
      const json = await response.json();
      return Array.isArray(json.data) ? json.data : [];
    } catch (error) {
      console.error(error);
      return [];
    }
  }

  function plainText(value) {
    const text = String(value ?? '');
    if (!/<\/?[a-z][\s\S]*?>/i.test(text)) return text.trim();
    const doc = new DOMParser().parseFromString(text, 'text/html');
    doc.body.querySelectorAll('p, li, br, h1, h2, h3, h4, blockquote').forEach(el => el.after(' '));
    return (doc.body.textContent || '').replace(/\s+/g, ' ').trim();
  }

  function sanitize(value) {
    if (value === null || value === undefined) return '';
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatDate(raw) {
    if (!raw) return '';
    const date = new Date(raw);
    if (Number.isNaN(date.getTime())) return raw;
    return date.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  function getDisplayDate(item) {
    const candidates = [
      item.fecha_evento,
      item.fecha,
      item.fecha_publicacion,
      item.published_at,
      item.updated_at,
      item.created_at
    ];
    for (const c of candidates) {
      if (c !== null && c !== undefined && String(c).trim() !== '') return String(c).trim();
    }
    return '';
  }

  function buildLink(item) {
    const linkValue = item.link ? String(item.link).trim() : '';
    const id = item.id ? String(item.id).trim() : '';

    if (id) return `noticia?id=${encodeURIComponent(id)}`;

    if (linkValue) {
      const normalized = linkValue.toLowerCase();
      if (normalized.startsWith('http://') || normalized.startsWith('https://') || normalized.startsWith('//')) {
        return linkValue;
      }
      if (normalized.includes('noticia-detail')) {
        return id ? `index.php?resource=noticias&id=${encodeURIComponent(id)}` : '#';
      }
      return linkValue;
    }

    return '#';
  }

  function getArchiveItems(items) {
    const counts = {};
    items.forEach(item => {
      const dateValue = item.fecha_evento || item.updated_at || '';
      const date = new Date(dateValue);
      if (Number.isNaN(date.getTime())) return;
      const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
      counts[key] = (counts[key] || 0) + 1;
    });

    return Object.keys(counts)
      .sort((a, b) => b.localeCompare(a))
      .map(key => {
        const [year, month] = key.split('-');
        return { key, label: `${monthNames[Number(month) - 1]} ${year}`, count: counts[key] };
      });
  }

  function getUniqueCategories(items) {
    return items.reduce((acc, item) => {
      const category = item.categoria ? String(item.categoria).trim() : 'Sin categor\u00eda';
      acc[category] = (acc[category] || 0) + 1;
      return acc;
    }, {});
  }

  function renderFeatured(item) {
    if (!featuredContainer) return;
    if (!item) {
      featuredContainer.innerHTML = '<div class="featured-news-body"><p>No hay noticias disponibles.</p></div>';
      return;
    }

    const imgSrc = item.imagen || 'assets/images/placeholder-news.jpg';
    const url = buildLink(item);
    const hasLink = url && url !== '#';

    featuredContainer.innerHTML = `
      <div class="featured-news-img">
        <img src="${sanitize(imgSrc)}" alt="${sanitize(item.titulo)}">
        <span class="card-tag">${sanitize(item.categoria || 'Noticia')}</span>
      </div>
        <div class="featured-news-body">
        ${(() => {
          const d = getDisplayDate(item);
          const txt = d ? formatDate(d) : 'Fecha no disponible';
          return `<span class="news-date">${sanitize(txt)}</span>`;
        })()}
        ${item.autor ? `<span class="news-author">Por ${sanitize(item.autor)}</span>` : ''}
        <h2>${hasLink ? `<a href="${sanitize(url)}">${sanitize(item.titulo || 'Sin t\u00edtulo')}</a>` : sanitize(item.titulo || 'Sin t\u00edtulo')}</h2>
        <p>${sanitize((item.descripcion_corta || plainText(item.contenido) || '').slice(0, 220))}</p>
        ${hasLink ? `<a href="${sanitize(url)}" class="link-gold">M\u00e1s informaci\u00f3n \u2192</a>` : ''}
      </div>
    `;
  }

  function renderGrid(items) {
    if (!newsGrid) return;
    newsGrid.innerHTML = '';
    if (!items.length) {
      newsGrid.innerHTML = '<p>No hay más noticias recientes.</p>';
      return;
    }

    items.forEach(item => {
      const imgSrc = item.imagen || 'assets/images/placeholder-news.jpg';
      const link = buildLink(item);
      const hasLink = link && link !== '#';
      const categoryClass = item.categoria ? item.categoria.toLowerCase().replace(/\s+/g, '-') : 'noticia';

      const card = document.createElement('article');
        card.className = 'news-card';
        card.innerHTML = `
          <div class="news-card-img">
            <img src="${sanitize(imgSrc)}" alt="${sanitize(item.titulo)}">
          </div>
          <div class="news-card-body">
            <div class="news-card-meta-row">
              <span class="card-tag ${sanitize(categoryClass)}">${sanitize(item.categoria || 'Noticia')}</span>
              ${(() => {
                const d = getDisplayDate(item);
                const txt = d ? formatDate(d) : 'Fecha no disponible';
                return `<span class="card-date">${sanitize(txt)}</span>`;
              })()}
            </div>
            ${item.autor ? `<span class="news-author">Por ${sanitize(item.autor)}</span>` : ''}
            <h3>${hasLink ? `<a href="${sanitize(link)}">${sanitize(item.titulo || 'Sin t\u00edtulo')}</a>` : sanitize(item.titulo || 'Sin t\u00edtulo')}</h3>
            <p>${sanitize((item.descripcion_corta || plainText(item.contenido) || '').slice(0, 140))}</p>
            ${hasLink ? `<a href="${sanitize(link)}" class="news-link">M\u00e1s informaci\u00f3n \u2192</a>` : ''}
          </div>
        `;

      newsGrid.appendChild(card);
    });
  }

  function renderCategories(categories) {
    if (!categoryContainer) return;
    categoryContainer.innerHTML = '';
    const entries = Object.entries(categories).sort((a, b) => b[1] - a[1]);

    const allItem = document.createElement('li');
    allItem.className = 'category-item';
    allItem.innerHTML = `<button type="button" data-category="" class="category-button ${selectedCategory === '' ? 'active' : ''}">Todos</button>`;
    categoryContainer.appendChild(allItem);

    entries.forEach(([category, count]) => {
      const li = document.createElement('li');
      li.className = 'category-item';
      li.innerHTML = `<button type="button" data-category="${sanitize(category)}" class="category-button ${selectedCategory === category ? 'active' : ''}"><span>${sanitize(category)}</span><span>${count}</span></button>`;
      categoryContainer.appendChild(li);
    });
  }

  function renderArchive(items) {
    if (!archiveContainer) return;
    archiveContainer.innerHTML = '';
    if (!items.length) {
      archiveContainer.innerHTML = '<p>No hay archivo disponible.</p>';
      return;
    }

    const list = document.createElement('ul');
    list.className = 'archive-list';
    const totalCount = items.reduce((sum, item) => sum + item.count, 0);

    const allItem = document.createElement('li');
    allItem.className = 'archive-item';
    allItem.innerHTML = `<button type="button" data-archive="" class="archive-button ${selectedArchive === '' ? 'active' : ''}"><span>Todos los meses</span><span class="count-badge">${totalCount}</span></button>`;
    list.appendChild(allItem);

    const years = items.reduce((acc, item) => {
      const year = item.key.split('-')[0];
      acc[year] = acc[year] || [];
      acc[year].push(item);
      return acc;
    }, {});

    Object.keys(years).sort((a, b) => b.localeCompare(a)).forEach(year => {
      const yearItems = years[year];
      const yearTotal = yearItems.reduce((sum, item) => sum + item.count, 0);

      const yearEntry = document.createElement('li');
      yearEntry.className = 'archive-year';
      yearEntry.innerHTML = `
        <div class="archive-year-label">
          <span>${sanitize(year)}</span>
          <span class="year-count">${yearTotal}</span>
        </div>
      `;

      const monthList = document.createElement('ul');
      monthList.className = 'archive-month-list';
      yearItems.forEach(item => {
        const monthItem = document.createElement('li');
        monthItem.className = 'archive-month';
        monthItem.innerHTML = `
          <button type="button" data-archive="${sanitize(item.key)}" class="archive-month-button ${selectedArchive === item.key ? 'active' : ''}">
            <span>${sanitize(item.label)}</span>
            <span class="count-badge">${item.count}</span>
          </button>
        `;
        monthList.appendChild(monthItem);
      });

      yearEntry.appendChild(monthList);
      list.appendChild(yearEntry);
    });

    archiveContainer.appendChild(list);
  }

  function filterNoticias() {
    const normalizeText = value => String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase();
    const query = normalizeText(searchQuery).trim();
    return allNews.filter(item => {
      const title = normalizeText(item.titulo);
      const description = normalizeText([item.descripcion_corta, plainText(item.contenido)].filter(Boolean).join(' '));
      const category = normalizeText(item.categoria);
      const dateValue = item.fecha_evento || item.updated_at || '';
      const date = new Date(dateValue);
      const archiveKey = Number.isNaN(date.getTime()) ? '' : `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;

      const matchesQuery = !query || title.includes(query) || description.includes(query);
      const matchesCategory = !selectedCategory || category === selectedCategory.toLowerCase();
      const matchesArchive = !selectedArchive || archiveKey === selectedArchive;

      return matchesQuery && matchesCategory && matchesArchive;
    });
  }

  function renderPagination(totalItems) {
    if (!paginationContainer) return;
    paginationContainer.innerHTML = '';
    const totalPages = Math.ceil(totalItems / pageSize);
    if (totalItems <= pageSize) return;

    const nav = document.createElement('nav');
    nav.setAttribute('aria-label', 'Navegación de páginas');

    const list = document.createElement('ul');
    list.className = 'paginacion';

    const addPageItem = (label, page, disabled = false, active = false) => {
      const li = document.createElement('li');
      const link = document.createElement('a');
      link.href = '#';
      link.textContent = label;
      link.className = active ? 'activa' : '';
      if (label === '‹') {
        link.setAttribute('aria-label', 'Anterior');
      } else if (label === '›') {
        link.setAttribute('aria-label', 'Siguiente');
      }
      if (active) {
        link.setAttribute('aria-current', 'page');
      }
      if (disabled) {
        link.setAttribute('aria-disabled', 'true');
        link.classList.add('disabled');
      }
      link.addEventListener('click', event => {
        event.preventDefault();
        if (disabled || page === currentPage) return;
        currentPage = page;
        updateUI();
      });

      li.appendChild(link);
      list.appendChild(li);
    };

    addPageItem('‹', Math.max(1, currentPage - 1), currentPage === 1);
    for (let page = 1; page <= Math.min(totalPages, 5); page += 1) {
      addPageItem(String(page), page, false, page === currentPage);
    }
    if (totalPages > 5) {
      const dots = document.createElement('li');
      dots.className = 'puntos';
      dots.textContent = '...';
      list.appendChild(dots);
      addPageItem(String(totalPages), totalPages, false, currentPage === totalPages);
    }
    addPageItem('›', Math.min(totalPages, currentPage + 1), currentPage === totalPages);

    nav.appendChild(list);
    paginationContainer.appendChild(nav);
  }

  function updateUI() {
    const filtered = filterNoticias();
    // La noticia destacada es la más reciente marcada como "Destacada"; si no hay ninguna, la más reciente.
    const featuredItem = filtered.find(item => Number(item.es_destacada) === 1) || filtered[0];
    const rest = filtered.filter(item => item !== featuredItem);
    const totalPages = Math.max(1, Math.ceil(rest.length / pageSize));
    if (currentPage > totalPages) currentPage = 1;

    const start = (currentPage - 1) * pageSize;
    const pageItems = rest.slice(start, start + pageSize);

    renderFeatured(featuredItem || null);
    renderGrid(pageItems);
    renderPagination(rest.length);

    if (pageInfo) {
      pageInfo.textContent = `Mostrando ${filtered.length} noticia${filtered.length === 1 ? '' : 's'}`;
    }
  }

  function init() {
    if (!featuredContainer || !newsGrid || !archiveContainer) return;
    fetchNoticias().then(news => {
      allNews = news;
      renderArchive(getArchiveItems(allNews));
      renderCategories(getUniqueCategories(allNews));
      updateUI();
    });
  }

  if (searchButton) {
    searchButton.addEventListener('click', () => {
      searchQuery = searchInput ? searchInput.value : '';
      currentPage = 1;
      updateUI();
    });
  }

  if (searchInput) {
    searchInput.addEventListener('keydown', event => {
      if (event.key === 'Enter') {
        event.preventDefault();
        searchQuery = searchInput.value;
        currentPage = 1;
        updateUI();
      }
    });
  }

  if (categoryContainer) {
    categoryContainer.addEventListener('click', event => {
      const button = event.target.closest('button.category-button');
      if (!button) return;
      selectedCategory = button.dataset.category || '';
      currentPage = 1;
      renderCategories(getUniqueCategories(allNews));
      updateUI();
    });
  }

  if (archiveContainer) {
    archiveContainer.addEventListener('click', event => {
      const button = event.target.closest('button.archive-button, button.archive-month-button');
      if (!button) return;
      selectedArchive = button.dataset.archive || '';
      currentPage = 1;
      renderArchive(getArchiveItems(allNews));
      updateUI();
    });
  }

  init();
})();

