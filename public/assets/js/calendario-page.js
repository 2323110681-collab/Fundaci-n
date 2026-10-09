(function() {
  const API_BASE = 'index.php?resource=agenda';
  const dayNames = ['DOM', 'LUN', 'MAR', 'MIE', 'JUE', 'VIE', 'SAB'];
  const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

  const calendarGrid = document.getElementById('calendarGrid');
  const calendarMonthTitle = document.getElementById('calendarMonthTitle');
  const prevMonthButton = document.getElementById('prevMonth');
  const nextMonthButton = document.getElementById('nextMonth');
  const typeFilterSelect = document.getElementById('typeFilterSelect');
  const locationFilterSelect = document.getElementById('locationFilterSelect');
  const clearFiltersButton = document.getElementById('clearFiltersButton');
  const viewToggleButtons = document.querySelectorAll('.view-toggle .toggle-btn');
  const eventListPanel = document.getElementById('eventListPanel');
  const eventListInfo = document.getElementById('eventListInfo');
  const eventListContainer = document.getElementById('eventListContainer');
  const eventDetailTag = document.getElementById('eventDetailTag');
  const eventDetailTitle = document.getElementById('eventDetailTitle');
  const detailDate = document.getElementById('detailDate');
  const detailTime = document.getElementById('detailTime');
  const detailLocation = document.getElementById('detailLocation');
  const detailDescription = document.getElementById('eventDetailDescription');
  const detailLink = document.getElementById('eventDetailLink');

  let events = [];
  let filteredEvents = [];
  let viewMode = 'month';
  let currentDate = new Date();
  let selectedDate = null;
  let selectedEventId = null;

  function sanitize(value) {
    if (value === null || value === undefined) return '';
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // La API entrega fecha_evento como "YYYY-MM-DD". new Date() interpreta ese
  // formato como medianoche UTC, que en America/Lima cae en el dia anterior y
  // hacia que cada evento se mostrara un dia antes de su fecha real. Las fechas
  // sin hora se construyen con las partes locales.
  // Las claves internas del calendario son "DD/MM/AAAA" y se parsean igual, para
  // no depender del parser de respaldo de new Date(), que las lee como M/D/A y
  // confundia, por ejemplo, 06/12/2026 con el 12 de junio.
  function parseLocalDate(raw) {
    if (raw instanceof Date) return raw;
    if (typeof raw === 'string') {
      const isoDate = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if (isoDate) {
        return new Date(Number(isoDate[1]), Number(isoDate[2]) - 1, Number(isoDate[3]));
      }
      const esDate = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
      if (esDate) {
        return new Date(Number(esDate[3]), Number(esDate[2]) - 1, Number(esDate[1]));
      }
    }
    return new Date(raw);
  }

  function formatDate(raw, options = {}) {
    if (!raw) return '-';
    const date = parseLocalDate(raw);
    if (Number.isNaN(date.getTime())) return sanitize(raw);
    return new Intl.DateTimeFormat('es-PE', options).format(date);
  }

  // La descripcion se guarda como HTML del editor. En la tarjeta del calendario
  // interesa un extracto legible, no el HTML completo: ese se muestra entero en
  // la ficha del evento al pulsar el titulo.
  function htmlToPlainText(html) {
    if (!html) return '';
    let text = String(html);
    if (/<[^>]*>/.test(text)) {
      text = text.replace(/<\/(p|li|h[1-4]|blockquote|div)>/gi, ' ');
      text = text.replace(/<br\s*\/?>/gi, ' ');
      text = text.replace(/<[^>]*>/g, '');
    }
    return text
      .replace(/&nbsp;/gi, ' ')
      .replace(/&hellip;/gi, '…')
      .replace(/&quot;/gi, '"')
      .replace(/&#0?39;/gi, "'")
      .replace(/&lt;/gi, '<')
      .replace(/&gt;/gi, '>')
      .replace(/&amp;/gi, '&')
      .replace(/\s+/g, ' ')
      .trim();
  }

  // Recorta en la ultima palabra completa para no partirla a la mitad.
  function shortenText(text, limit) {
    const value = String(text || '').trim();
    if (value.length <= limit) return value;
    const cut = value.slice(0, limit);
    const lastSpace = cut.lastIndexOf(' ');
    return (lastSpace > limit * 0.6 ? cut.slice(0, lastSpace) : cut).trim() + '…';
  }

  function formatEventDate(raw) {
    return formatDate(raw, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  }

  function formatEventDateRange(event, options = { day: '2-digit', month: 'short', year: 'numeric' }) {
    const start = formatDate(event.fecha_evento, options);
    if (!event.fecha_fin || event.fecha_fin === event.fecha_evento) return start;
    return `${start} – ${formatDate(event.fecha_fin, options)}`;
  }

  function formatEventTime(raw) {
    if (!raw) return '-';
    return sanitize(raw);
  }

  function normalizeValue(value) {
    return String(value || '').trim().toLowerCase();
  }

  function categoryClassName(value) {
    const slug = String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');
    return `category-${slug || 'general'}`;
  }

  function fetchEvents() {
    return fetch(API_BASE)
      .then(response => {
        if (!response.ok) throw new Error('No se pudieron cargar los eventos');
        return response.json();
      })
      .then(json => Array.isArray(json.data) ? json.data : []);
  }

  function getUniqueValues(items, key) {
    const values = {};
    items.forEach(item => {
      const value = normalizeValue(item[key]);
      if (!value) return;
      values[value] = item[key].trim();
    });
    return values;
  }

  function fillSelectOptions(select, items, placeholder) {
    select.innerHTML = `<option value="">${sanitize(placeholder)}</option>`;
    Object.entries(items).sort((a, b) => a[1].localeCompare(b[1], 'es', { sensitivity: 'base' }))
      .forEach(([value, label]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        select.appendChild(option);
      });
  }

  function applyFilters() {
    const selectedType = normalizeValue(typeFilterSelect.value);
    const selectedLocation = normalizeValue(locationFilterSelect.value);

    filteredEvents = events.filter(event => {
      const matchesType = !selectedType || normalizeValue(event.categoria) === selectedType;
      const matchesLocation = !selectedLocation || normalizeValue(event.lugar) === selectedLocation;
      return matchesType && matchesLocation;
    });
  }

  function dateKeyOptions() {
    return { year: 'numeric', month: '2-digit', day: '2-digit' };
  }

  // La clave de un evento se obtiene siempre de fecha_evento, que ya es un
  // formato ISO sin hora. dateKey ya viene en el mismo formato, asi que se
  // comparan las claves directamente en vez de reconvertirlas.
  function getEventsForDate(dateKey) {
    const selectedDay = parseDateKey(dateKey);
    if (!selectedDay) return [];
    const selectedTime = selectedDay.getTime();
    return filteredEvents.filter(event => {
      if (!event.fecha_evento) return false;
      const start = parseLocalDate(event.fecha_evento);
      const end = parseLocalDate(event.fecha_fin || event.fecha_evento);
      return selectedTime >= start.getTime() && selectedTime <= end.getTime();
    });
  }

  function getMonthTitle(date) {
    return `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
  }

  // Clave de dia en el mismo formato que data-date de cada celda. Se construye
  // con las partes locales y no con toISOString(), que convertiria a UTC y
  // desplazaria el dia en zonas con offset negativo como America/Lima.
  function dateKeyFrom(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return formatDate(`${year}-${month}-${day}`, dateKeyOptions());
  }

  function buildDayCell(date, isCurrentMonth, eventCount) {
    const dateKey = dateKeyFrom(date);
    const dayCell = document.createElement('button');
    dayCell.type = 'button';
    dayCell.className = 'day-cell';
    if (!isCurrentMonth) dayCell.classList.add('prev-month');
    if (eventCount > 0) dayCell.classList.add('has-event');
    if (selectedDate === dateKey) dayCell.classList.add('selected-day');
    if (dateKey === dateKeyFrom(new Date())) {
      dayCell.classList.add('today');
    }
    dayCell.dataset.date = dateKey;
    let countHtml = '';
    if (eventCount > 1) {
      countHtml = `<span class="event-count" aria-hidden="true">${eventCount}</span>`;
    } else if (eventCount === 1) {
      countHtml = '<span class="event-dot" aria-hidden="true"></span>';
    }
    dayCell.innerHTML = `<span class="date-number">${date.getDate()}</span>${countHtml}`;
    return dayCell;
  }

  function renderCalendar() {
    calendarMonthTitle.textContent = getMonthTitle(currentDate);
    calendarGrid.innerHTML = '';

    dayNames.forEach(name => {
      const dayName = document.createElement('div');
      dayName.className = 'day-name';
      dayName.textContent = name;
      calendarGrid.appendChild(dayName);
    });

    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstOfMonth = new Date(year, month, 1);
    const startDay = firstOfMonth.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const prevMonthDays = new Date(year, month, 0).getDate();
    const totalCells = 42;

    for (let index = 0; index < totalCells; index++) {
      let cellDate;
      let inCurrentMonth = false;

      if (index < startDay) {
        cellDate = new Date(year, month - 1, prevMonthDays - startDay + index + 1);
      } else if (index < startDay + daysInMonth) {
        cellDate = new Date(year, month, index - startDay + 1);
        inCurrentMonth = true;
      } else {
        cellDate = new Date(year, month + 1, index - startDay - daysInMonth + 1);
      }

      // Se pasa la clave ya formateada: getEventsForDate la normaliza con
      // formatDate, que ahora interpreta las fechas sin hora en hora local.
      const eventsForDay = getEventsForDate(dateKeyFrom(cellDate));
      const dayCell = buildDayCell(cellDate, inCurrentMonth, eventsForDay.length);
      calendarGrid.appendChild(dayCell);
    }
  }

  // El titulo de la tarjeta abre la ficha completa del evento. Se construye el
  // enlace con createElement en vez de innerHTML para no interpolar el titulo.
  function renderDetailTitle(event) {
    eventDetailTitle.textContent = '';
    const label = event.titulo || 'Evento sin título';
    if (!event.id) {
      eventDetailTitle.textContent = label;
      return;
    }
    const link = document.createElement('a');
    link.className = 'event-detail-link';
    link.href = `evento?id=${encodeURIComponent(event.id)}`;
    link.textContent = label;
    eventDetailTitle.appendChild(link);
  }

  function renderEventDetail(event) {
    if (!event) {
      eventDetailTag.className = 'card-tag event-category category-general';
      eventDetailTag.textContent = 'Evento';
      eventDetailTitle.textContent = 'Selecciona un evento';
      detailDate.textContent = '-';
      detailTime.textContent = '-';
      detailLocation.textContent = '-';
      detailDescription.textContent = 'Selecciona un día del calendario para ver los detalles del evento.';
      detailLink.href = '#';
      detailLink.setAttribute('aria-disabled', 'true');
      detailLink.textContent = 'Ver inscripción';
      return;
    }

    eventDetailTag.className = `card-tag event-category ${categoryClassName(event.categoria)}`;
    eventDetailTag.textContent = event.categoria ? event.categoria.toUpperCase() : 'Evento';
    renderDetailTitle(event);
    detailDate.textContent = formatEventDateRange(event, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    detailTime.textContent = formatEventTime(event.hora_evento);
    detailLocation.textContent = event.lugar || '-';
    const summary = shortenText(htmlToPlainText(event.descripcion), 180);
    detailDescription.textContent = summary || 'No hay descripción disponible para este evento.';

    if (event.link_inscripcion) {
      detailLink.href = event.link_inscripcion;
      detailLink.removeAttribute('aria-disabled');
      detailLink.textContent = 'Ver inscripción';
    } else {
      detailLink.href = '#';
      detailLink.setAttribute('aria-disabled', 'true');
      detailLink.textContent = 'Sin enlace de inscripción';
    }
  }

  function renderEventList() {
    if (!eventListContainer) return;
    const sortedEvents = [...filteredEvents].sort((a, b) => {
      const aDate = parseLocalDate(a.fecha_evento || '');
      const bDate = parseLocalDate(b.fecha_evento || '');
      return aDate - bDate;
    });

    eventListContainer.innerHTML = '';

    if (!sortedEvents.length) {
      eventListInfo.textContent = 'No hay eventos que coincidan con los filtros.';
      return;
    }

    eventListInfo.textContent = `${sortedEvents.length} evento${sortedEvents.length === 1 ? '' : 's'} disponible${sortedEvents.length === 1 ? '' : 's'}.`;

    sortedEvents.forEach(event => {
      const dateLabel = formatEventDateRange(event);
      const item = document.createElement('div');
      item.className = 'event-list-item';
      if (String(event.id) === String(selectedEventId)) {
        item.classList.add('active');
      }
      item.dataset.eventId = event.id;
      item.innerHTML = `
        <div class="event-list-item-header">
          <strong><a href="evento?id=${encodeURIComponent(event.id)}">${sanitize(event.titulo || 'Sin título')}</a></strong>
          <span class="event-category ${categoryClassName(event.categoria)}">${sanitize(event.categoria || 'Sin categoría')}</span>
        </div>
        <p>${sanitize(dateLabel)} · ${sanitize(event.lugar || '-')}</p>
        <small>${sanitize(event.hora_evento || '')}</small>
      `;
      eventListContainer.appendChild(item);
    });
  }

  function updateSelectedEvent(eventId) {
    const event = filteredEvents.find(item => String(item.id) === String(eventId));
    selectedEventId = event ? event.id : null;
    if (event) {
      selectedDate = formatDate(event.fecha_evento, dateKeyOptions());
      renderEventDetail(event);
    } else {
      renderEventDetail(null);
    }
    renderCalendar();
    renderEventList();
  }

  function selectDate(dateKey) {
    selectedDate = dateKey;
    const eventsForDay = getEventsForDate(dateKey);
    if (eventsForDay.length) {
      updateSelectedEvent(eventsForDay[0].id);
    } else {
      selectedEventId = null;
      renderEventDetail(null);
      renderCalendar();
      renderEventList();
    }
  }

  function changeView(mode) {
    viewMode = mode;
    viewToggleButtons.forEach(button => {
      button.classList.toggle('active', button.textContent.trim().toLowerCase().includes(mode));
    });
    if (mode === 'list') {
      eventListPanel.classList.remove('hidden');
      calendarGrid.parentElement.classList.add('hidden');
    } else {
      eventListPanel.classList.add('hidden');
      calendarGrid.parentElement.classList.remove('hidden');
    }
  }

  function attachListeners() {
    prevMonthButton.addEventListener('click', () => {
      currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1);
      renderCalendar();
    });

    nextMonthButton.addEventListener('click', () => {
      currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
      renderCalendar();
    });

    calendarGrid.addEventListener('click', event => {
      const button = event.target.closest('.day-cell');
      if (!button || !button.dataset.date) return;
      selectDate(button.dataset.date);
    });

    typeFilterSelect.addEventListener('change', () => {
      applyFilters();
      openInitialDate();
    });

    locationFilterSelect.addEventListener('change', () => {
      applyFilters();
      openInitialDate();
    });

    clearFiltersButton.addEventListener('click', () => {
      typeFilterSelect.value = '';
      locationFilterSelect.value = '';
      applyFilters();
      openInitialDate();
    });

    viewToggleButtons.forEach(button => {
      button.addEventListener('click', () => {
        const mode = button.dataset.view || 'month';
        changeView(mode);
      });
    });

    eventListContainer.addEventListener('click', event => {
      // Si el clic viene del enlace del titulo hay que dejarlo pasar. Antes,
      // updateSelectedEvent() repintaba la lista y eliminaba el <a> del DOM
      // durante el mismo clic, lo que hacia que el navegador cancelara la
      // navegacion y el titulo no llegaba a abrir el evento.
      if (event.target.closest('a')) return;
      const button = event.target.closest('.event-list-item');
      if (!button || !button.dataset.eventId) return;
      updateSelectedEvent(button.dataset.eventId);
    });
  }

  // Dia con el que se abre el calendario. Si hoy tiene eventos se usa hoy; si
  // no, se salta al proximo dia con evento para que la pantalla no quede
  // vacia al cargar. Se ordena por fecha real y no por la clave DD/MM/AAAA,
  // porque ese formato ordena mal al compararlo como texto.
  function findInitialDateKey() {
    const todayKey = dateKeyFrom(new Date());
    if (getEventsForDate(todayKey).length > 0) return todayKey;

    const startOfToday = new Date().setHours(0, 0, 0, 0);
    const upcoming = filteredEvents
      .filter(event => event.fecha_evento && parseLocalDate(event.fecha_evento).getTime() >= startOfToday)
      .sort((a, b) => parseLocalDate(a.fecha_evento).getTime() - parseLocalDate(b.fecha_evento).getTime());

    if (upcoming.length === 0) return todayKey;
    return formatDate(upcoming[0].fecha_evento, dateKeyOptions());
  }

  // La clave es DD/MM/AAAA, un formato que new Date() no interpreta de forma
  // fiable, asi que se separa a mano para recuperar el dia local.
  function parseDateKey(dateKey) {
    const parts = String(dateKey).match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!parts) return null;
    return new Date(Number(parts[3]), Number(parts[2]) - 1, Number(parts[1]));
  }

  // Si el dia inicial cae en otro mes, se navega hasta el para que la celda
  // seleccionada quede dentro de la vista.
  function syncCurrentDateToKey(dateKey) {
    const parsed = parseDateKey(dateKey);
    if (!parsed || Number.isNaN(parsed.getTime())) return;
    if (parsed.getMonth() === currentDate.getMonth() && parsed.getFullYear() === currentDate.getFullYear()) return;
    currentDate = parsed;
  }

  // Reposiciona la vista sobre un dia util: hoy si tiene eventos y, si no, el
  // proximo que los tenga. Se usa al cargar y tambien tras cambiar un filtro,
  // que antes dejaba el detalle vacio.
  function openInitialDate() {
    const dateKey = findInitialDateKey();
    syncCurrentDateToKey(dateKey);
    selectDate(dateKey);
  }

  function initialize() {
    currentDate = new Date();
    eventListPanel.classList.add('hidden');
    changeView('month');

    fetchEvents()
      .then(items => {
        events = items.map(item => ({ ...item }));
        fillSelectOptions(typeFilterSelect, getUniqueValues(events, 'categoria'), 'Tipos');
        fillSelectOptions(locationFilterSelect, getUniqueValues(events, 'lugar'), 'Lugares');
        applyFilters();
        // Antes solo se pintaba el calendario y se llamaba renderEventDetail(null),
        // de modo que ningun dia quedaba seleccionado y ningun evento aparecia
        // hasta pulsar una celda. Ahora se abre con un dia ya elegido.
        openInitialDate();
      })
      .catch(() => {
        calendarGrid.innerHTML = '<div class="calendar-error">No se pudieron cargar los eventos.</div>';
        eventListInfo.textContent = 'No se pudieron cargar los eventos.';
        renderEventDetail(null);
      });
  }

  attachListeners();
  initialize();
})();
