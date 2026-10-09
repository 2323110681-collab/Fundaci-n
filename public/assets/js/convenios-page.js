(() => {
  const pageSize = 3;
  let currentPage = 1;
  let convenios = [];
  const grid = document.getElementById('conveniosGrid');
  const pagination = document.getElementById('conveniosPagination');

  const escapeHtml = value => String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');

  async function fetchConvenios() {
    const response = await fetch('index.php?resource=convenios');
    if (!response.ok) throw new Error('No se pudieron cargar los convenios');
    const json = await response.json();
    return Array.isArray(json.data) ? json.data : [];
  }

  function render() {
    const start = (currentPage - 1) * pageSize;
    grid.innerHTML = convenios.slice(start, start + pageSize).map((item, index) => `
      <article class="convenio-card">
        <div class="convenio-card-number">${String(start + index + 1).padStart(2, '0')}</div>
        <div>
          ${item.imagen ? `<img class="convenio-card-image" src="${escapeHtml(item.imagen)}" alt="${escapeHtml(item.institucion)}">` : ''}
          <span class="convenio-card-type">${escapeHtml(item.tipo || 'Convenio')}</span>
          <h3>${escapeHtml(item.institucion)}</h3>
          <p class="convenio-location">${escapeHtml([item.ciudad, item.pais].filter(Boolean).join(', '))}</p>
          <p>${escapeHtml(item.descripcion || '')}</p>
        </div>
      </article>
    `).join('');

    pagination.innerHTML = '';
    if (convenios.length <= pageSize) return;
    const totalPages = Math.max(1, Math.ceil(convenios.length / pageSize));
    for (let page = 1; page <= totalPages; page += 1) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = `page-btn ${page === currentPage ? 'active' : ''}`;
      button.textContent = String(page);
      button.setAttribute('aria-label', `Página ${page}`);
      if (page === currentPage) button.setAttribute('aria-current', 'page');
      button.addEventListener('click', () => { currentPage = page; render(); });
      pagination.appendChild(button);
    }
  }

  fetchConvenios().then(items => {
    convenios = items;
    render();
  }).catch(error => {
    console.error(error);
    grid.innerHTML = '<p>No hay convenios disponibles.</p>';
  });
})();
