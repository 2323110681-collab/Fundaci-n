(() => {
  const plain = value => {
    const text = String(value ?? '');
    if (!/<\/?[a-z][\s\S]*?>/i.test(text)) return text.trim();
    const doc = new DOMParser().parseFromString(text, 'text/html');
    doc.body.querySelectorAll('p, li, br, h1, h2, h3, h4, blockquote').forEach(el => el.after(' '));
    return (doc.body.textContent || '').replace(/\s+/g, ' ').trim();
  };
  const sections = [
    {
      resource: 'noticias',
      label: 'Noticias',
      title: item => item.titulo || 'Noticia',
      summary: item => item.descripcion_corta || plain(item.contenido) || item.categoria || '',
      fields: item => [item.categoria],
      href: (item, query) => `noticias?q=${encodeURIComponent(item.titulo || query)}`
    },
    {
      resource: 'programas',
      label: 'Programas',
      title: item => item.titulo || 'Programa',
      summary: item => plain(item.descripcion) || item.autor || item.categoria || '',
      fields: item => [item.categoria, item.autor],
      href: item => item.id ? `programas?id=${encodeURIComponent(item.id)}` : 'programas'
    },
    {
      resource: 'agenda',
      label: 'Eventos',
      title: item => item.titulo || 'Evento',
      summary: item => [item.fecha_evento && item.fecha_fin && item.fecha_fin !== item.fecha_evento ? `${item.fecha_evento} – ${item.fecha_fin}` : item.fecha_evento, item.hora_evento, item.lugar, plain(item.descripcion)].filter(Boolean).join(' · '),
      fields: item => [item.categoria, item.fecha_evento, item.fecha_fin, item.hora_evento, item.lugar],
      href: () => 'calendario'
    },
    {
      resource: 'convenios',
      label: 'Convenios',
      title: item => item.institucion || 'Convenio',
      summary: item => [item.tipo, item.ciudad, item.pais, item.descripcion].filter(Boolean).join(' · '),
      fields: item => [item.tipo, item.ciudad, item.pais],
      href: () => 'convenios'
    }
  ];

  const query = new URLSearchParams(window.location.search).get('q')?.trim() || '';
  const queryLabel = document.getElementById('globalSearchQuery');
  const status = document.getElementById('globalSearchStatus');
  const warning = document.getElementById('globalSearchWarning');
  const resultsContainer = document.getElementById('globalSearchResults');

  function normalize(value) {
    return String(value ?? '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  async function fetchSection(resource) {
    const url = new URL('index.php', window.location.href);
    url.searchParams.set('resource', resource);
    const response = await fetch(url);
    if (!response.ok) throw new Error(`No se pudo consultar ${resource}`);
    const payload = await response.json();
    return Array.isArray(payload.data) ? payload.data : [];
  }

  function createResultCard(section, item) {
    const card = document.createElement('article');
    card.className = 'search-result-card';

    const type = document.createElement('span');
    type.className = 'search-result-type';
    type.textContent = section.label;

    const title = document.createElement('h3');
    title.textContent = section.title(item);

    const summary = section.summary(item);
    const description = document.createElement('p');
    description.textContent = summary;

    const link = document.createElement('a');
    link.className = 'search-result-link';
    link.href = section.href(item, query);
    link.textContent = 'Abrir apartado';

    card.append(type, title);
    if (summary) card.appendChild(description);
    card.appendChild(link);
    return card;
  }

  function renderResults(sectionResults, failedSections) {
    const matches = [];
    sectionResults.forEach(({ section, items }) => {
      const terms = normalize(query).split(' ').filter(Boolean);
      const sectionMatches = items.filter(item => {
        const searchable = normalize([
          section.title(item),
          section.summary(item),
          ...section.fields(item)
        ].join(' '));
        return terms.every(term => searchable.includes(term));
      });
      if (sectionMatches.length) matches.push({ section, items: sectionMatches });
    });

    const resultCount = matches.reduce((count, group) => count + group.items.length, 0);
    status.textContent = resultCount === 0
      ? 'No se encontraron resultados.'
      : `${resultCount} ${resultCount === 1 ? 'resultado encontrado.' : 'resultados encontrados.'}`;
    warning.hidden = failedSections === 0;
    resultsContainer.replaceChildren();

    matches.forEach(({ section, items }) => {
      const group = document.createElement('section');
      group.className = 'search-results-group';
      const heading = document.createElement('h2');
      heading.textContent = section.label;
      const list = document.createElement('div');
      list.className = 'search-results-list';
      items.forEach(item => list.appendChild(createResultCard(section, item)));
      group.append(heading, list);
      resultsContainer.appendChild(group);
    });
  }

  async function initialize() {
    queryLabel.textContent = query;
    if (query.length < 2) {
      status.textContent = 'Escribe al menos dos caracteres para buscar.';
      return;
    }

    status.textContent = 'Buscando...';
    const results = await Promise.all(sections.map(async section => {
      try {
        return { section, items: await fetchSection(section.resource), failed: false };
      } catch (error) {
        console.error(error);
        return { section, items: [], failed: true };
      }
    }));

    renderResults(
      results.filter(result => !result.failed),
      results.filter(result => result.failed).length
    );
  }

  initialize();
})();