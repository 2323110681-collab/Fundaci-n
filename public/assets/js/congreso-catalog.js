(() => {
  const grid = document.getElementById('congress-catalog-grid');
  const searchInput = document.getElementById('congress-search');
  const modalitySelect = document.getElementById('congress-modality');
  const yearSelect = document.getElementById('congress-year');
  const clearButton = document.getElementById('clear-congress-filters');
  const noResults = document.getElementById('congress-no-results');
  if (!grid || !searchInput || !modalitySelect || !yearSelect || !clearButton || !noResults) return;

  const normalize = value => String(value || '')
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
  const cards = Array.from(grid.querySelectorAll('.congress-catalog-card'));

  function applyFilters() {
    const search = normalize(searchInput.value);
    const modality = normalize(modalitySelect.value);
    const year = yearSelect.value;
    let visible = 0;
    cards.forEach(card => {
      const matches = normalize(card.dataset.search).includes(search)
        && (!modality || normalize(card.dataset.modality) === modality)
        && (!year || card.dataset.year === year);
      card.hidden = !matches;
      if (matches) visible += 1;
    });
    noResults.hidden = visible > 0;
  }

  searchInput.addEventListener('input', applyFilters);
  modalitySelect.addEventListener('change', applyFilters);
  yearSelect.addEventListener('change', applyFilters);
  clearButton.addEventListener('click', () => {
    searchInput.value = '';
    modalitySelect.value = '';
    yearSelect.value = '';
    applyFilters();
  });
})();
