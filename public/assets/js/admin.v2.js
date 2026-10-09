const API_ROOT = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/') + 'index.php';
const CONGRESS_LOGO_UPLOAD_URL = new URL(API_ROOT);
CONGRESS_LOGO_UPLOAD_URL.searchParams.set('resource', 'congreso');
CONGRESS_LOGO_UPLOAD_URL.searchParams.set('view', 'upload-image');

let csrfToken = '';

const sectionSelect = document.getElementById('sectionSelect') || document.getElementById('resourceSelect');
const searchInput = document.getElementById('searchInput');
const refreshBtn = document.getElementById('refreshBtn') || document.getElementById('btnListar');
const tableHead = document.getElementById('tableHead');
const tableBody = document.getElementById('tableBody');

const editScreen = document.getElementById('editScreen');
const editForm = document.getElementById('editForm');
const modalTitle = document.getElementById('modalTitle');
const formFields = document.getElementById('formFields');
const saveBtn = document.getElementById('saveBtn');
const editBackBtn = document.getElementById('editBackBtn');
const saveBtnBottom = document.getElementById('saveBtnBottom');
const cancelBtnBottom = document.getElementById('cancelBtnBottom');
const editScreenSubtitle = document.getElementById('editScreenSubtitle');
const cancelBtn = document.getElementById('cancelBtn');
const resourceTitle = document.getElementById('resourceTitle');
const resourceDescription = document.getElementById('resourceDescription');

const requestedSection = new URLSearchParams(window.location.search).get('section');
const validAdminSections = ['programas', 'noticias', 'agenda', 'miembros', 'convenios', 'congreso', 'usuarios'];
let currentSection = validAdminSections.includes(requestedSection)
    ? requestedSection
    : ((sectionSelect && sectionSelect.value) ? sectionSelect.value : (document.querySelector('.tab-button.active') ? document.querySelector('.tab-button.active').dataset.resource : 'programas'));
let currentData = [];
let editingItem = null;
let currentUser = null;
let sortState = { key: null, dir: 'asc' };
let currentPage = 1;
let loadError = '';
let lastLoadedSection = null;
let sessionRedirecting = false;
let allowUnload = false;
let editSnapshot = '';
const PAGE_SIZE = 10;
const topResourceSelect = document.getElementById('topResourceSelect');
const userTabButton = document.getElementById('tab-usuarios');
const resourceSelect = document.getElementById('resourceSelect');
const btnOpenLogin = document.getElementById('btnOpenLogin');
const loginModal = document.getElementById('loginModal');
const closeLogin = document.getElementById('closeLogin');
const loginBtn = document.getElementById('loginBtn');
const logoutBtn = document.getElementById('logoutBtn');
const btnLogout = document.getElementById('btnLogout');
const loginForm = document.getElementById('loginForm');
const sidebarUserCard = document.getElementById('sidebarUserCard');
const sidebarUserAvatar = document.getElementById('sidebarUserAvatar');
const sidebarUsername = document.getElementById('sidebarUsername');
const sidebarUserRole = document.getElementById('sidebarUserRole');
const adminSidebar = document.getElementById('adminSidebar');
const adminSidebarToggle = document.getElementById('adminSidebarToggle');
const adminSidebarBackdrop = document.querySelector('.admin-sidebar-backdrop');
const sidebarNavLinks = Array.from(document.querySelectorAll('#adminSectionNav .tab-button'));

// Cajon lateral en pantallas estrechas. Por encima de 900px el aside es un riel
// fijo y estas reglas solo sirve para el caso de cajon.
function setAdminSidebarOpen(isOpen){
  document.body.classList.toggle('admin-sidebar-open', isOpen);
  if(adminSidebarToggle) adminSidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  if(adminSidebarBackdrop) adminSidebarBackdrop.hidden = !isOpen;
}

function closeAdminSidebar(){
  setAdminSidebarOpen(false);
}

// El modo oscuro del panel esta deshabilitado por ahora: los colores de la
// paleta oscura no combinan bien con los del sitio y quedan zonas ilegibles.
// No se aplica la clase 'admin-dark' ni se sigue la preferencia del sistema,
// asi que el panel se abre siempre en claro. Las reglas html.admin-dark siguen
// en admin.css, de modo que reactivarlo sea volver a marcar esta parte.
// Las comprobaciones de .admin-dark que quedan mas abajo (configuracion del
// editor y del calendario) devuelven false mientras la clase no se aplique.
function clearAdminTheme() {
    document.documentElement.classList.remove('admin-dark');
    try {
        localStorage.removeItem('fdu-admin-theme');
    } catch (error) {
        return;
    }
}
clearAdminTheme();

const RICH_TOOLBAR = [
    [{ header: [1, 2, 3, false] }],
    ['bold', 'italic', 'underline', 'link'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['clean']
];
const createRichEditors = {};
let editRichEditor = null;

function escapeHtmlText(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// Las descripciones antiguas eran texto plano; se convierten en párrafos para conservar los saltos de línea.
function descriptionToEditorHtml(value) {
    const text = String(value ?? '');
    if (!text.trim()) return '';
    if (/<\/?[a-z][\s\S]*?>/i.test(text)) return text;
    return text.split(/\r?\n/).map(line => line.trim() ? `<p>${escapeHtmlText(line)}</p>` : '<p><br></p>').join('');
}

function createRichEditor(container, hiddenInput, options = {}) {
    if (!container || !hiddenInput || typeof window.Quill === 'undefined') return null;
    const quill = new Quill(container, {
        theme: 'snow',
        placeholder: options.placeholder || 'Escribe aquí...',
        modules: { toolbar: RICH_TOOLBAR }
    });
    const sync = () => {
        const isEmpty = quill.getText().trim() === '' && !quill.root.querySelector('img');
        hiddenInput.value = isEmpty ? '' : quill.root.innerHTML;
    };
    quill.on('text-change', sync);
    const editor = {
        quill,
        sync,
        setHtml(html) {
            const content = descriptionToEditorHtml(html);
            if (content) quill.setContents(quill.clipboard.convert({ html: content }), 'silent');
            else quill.setText('', 'silent');
            sync();
        },
        clear() {
            quill.setText('', 'silent');
            sync();
        }
    };
    if (options.html) editor.setHtml(options.html);
    else sync();
    return editor;
}

function initCreateRichEditors() {
    createRichEditors.agenda = createRichEditor(
        document.getElementById('editor-descripcion-crear'),
        document.getElementById('input-descripcion-crear'),
        { placeholder: 'Escribe la descripción del evento aquí...' }
    );
    createRichEditors.programas = createRichEditor(
        document.getElementById('editor-descripcion-programas'),
        document.getElementById('input-descripcion-programas'),
        { placeholder: 'Escribe la descripción del programa aquí...' }
    );
    createRichEditors.noticias = createRichEditor(
        document.getElementById('editor-contenido-noticias'),
        document.getElementById('input-contenido-noticias'),
        { placeholder: 'Escribe el contenido de la noticia aquí...' }
    );
}

const IMAGE_MAX_BYTES = 2 * 1024 * 1024;
const IMAGE_TYPE_LABELS = { 'image/jpeg': 'JPG', 'image/png': 'PNG', 'image/gif': 'GIF', 'image/webp': 'WEBP' };
const IMAGE_TYPES_DEFAULT = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const IMAGE_TYPES_NO_GIF = ['image/jpeg', 'image/png', 'image/webp'];

function imageFormatsText(types) {
    const labels = types.map(type => IMAGE_TYPE_LABELS[type]).filter(Boolean);
    return labels.length > 1 ? labels.slice(0, -1).join(', ') + ' o ' + labels[labels.length - 1] : labels.join('');
}

// Revisa formato y peso de las imágenes antes de enviarlas, para avisar de inmediato.
function validateImageFiles(form) {
    const inputs = Array.from(form.querySelectorAll('input[type="file"]'));
    for (const input of inputs) {
        const file = input.files && input.files[0];
        if (!file) continue;
        const accepted = (input.accept || '').split(',').map(item => item.trim().toLowerCase()).filter(item => item && item !== 'image/*');
        const allowed = accepted.length ? accepted : IMAGE_TYPES_DEFAULT;
        if (!allowed.includes((file.type || '').toLowerCase())) {
            showAdminAlert('Formato de imagen no permitido', `"${file.name}" no se puede usar. Los formatos permitidos son ${imageFormatsText(allowed)}.`, 'warning');
            input.value = '';
            return false;
        }
        if (file.size > IMAGE_MAX_BYTES) {
            showAdminAlert('La imagen pesa demasiado', `"${file.name}" supera los 2 MB permitidos. Reduce su tamaño e inténtalo de nuevo.`, 'warning');
            input.value = '';
            return false;
        }
    }
    return true;
}

// Contador "n/255" para campos con límite de caracteres.
function attachCharCounter(input, max) {
    if (!input || !max) return;
    input.maxLength = max;
    const counter = document.createElement('p');
    counter.className = 'edit-hint edit-counter';
    const update = () => { counter.textContent = `${input.value.length}/${max} caracteres`; };
    input.addEventListener('input', update);
    input.form?.addEventListener('reset', () => setTimeout(update, 0));
    input.insertAdjacentElement('afterend', counter);
    update();
}

function showAdminAlert(title, text, icon = 'success') {
    const isDark = document.documentElement.classList.contains('admin-dark');
    if (window.Swal && typeof window.Swal.fire === 'function') {
        return window.Swal.fire({
            title,
            text,
            icon,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#d9a20b',
            background: isDark ? '#17232d' : '#ffffff',
            color: isDark ? '#e8eef0' : '#1f2937'
        });
    }

    console.error('SweetAlert2 no está disponible:', title, text);
    return Promise.resolve();
}

function confirmAdminAction(title, text) {
    if (window.Swal && typeof window.Swal.fire === 'function') {
        const isDark = document.documentElement.classList.contains('admin-dark');
        return window.Swal.fire({
            title,
            text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            background: isDark ? '#17232d' : '#ffffff',
            color: isDark ? '#e8eef0' : '#1f2937'
        }).then(result => result.isConfirmed);
    }

    console.error('SweetAlert2 no está disponible para confirmar:', title, text);
    return Promise.resolve(false);
}

function validateAdminForm(form) {
    const startDate = form.querySelector('[name="fecha_evento"]');
    const endDate = form.querySelector('[name="fecha_fin"]');
    if (startDate && endDate) {
        endDate.min = startDate.value;
        endDate.setCustomValidity(endDate.value && startDate.value && endDate.value < startDate.value
            ? 'La fecha de fin no puede ser anterior a la fecha de inicio.'
            : '');
    }
    if (form.checkValidity()) return true;
    const invalidField = form.querySelector(':invalid');
    showAdminAlert('Revisa el formulario', invalidField?.validationMessage || 'Completa los campos requeridos.', 'warning');
    invalidField?.focus();
    return false;
}

async function fetchCurrentUser(){
    try{
        const res = await fetch(new URL('auth.php', window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/')), { credentials: 'same-origin' });
        const json = await res.json().catch(()=>null);
        if(json?.csrf_token) csrfToken = json.csrf_token;
        if(json && json.user){
            currentUser = json.user;
            updateSidebarUser(currentUser);
            configureAdminUI(currentUser);
            return currentUser;
        }
        updateSidebarUser(null);
        configureAdminUI(null);
        return null;
    }catch(e){
        console.error('fetchCurrentUser', e);
        updateSidebarUser(null);
        configureAdminUI(null);
        return null;
    }
}

function updateSidebarUser(user){
    if(!sidebarUserCard) return;
    sidebarUserCard.classList.toggle('hidden', !user);
    if(!user) return;

    const username = String(user.username || '');
    const words = username.trim().split(/\s+/).filter(Boolean);
    const initials = words.length > 1 ? words.slice(0, 2).map(word => word[0]).join('') : (words[0] || '').slice(0, 2);
    if(sidebarUsername) sidebarUsername.textContent = username;
    if(sidebarUserRole) sidebarUserRole.textContent = user.role === 'admin' ? 'Administrador' : (user.role || 'Usuario');
    if(sidebarUserAvatar) sidebarUserAvatar.textContent = initials.toUpperCase();
}

function configureAdminUI(user){
    const isAdmin = user && user.role === 'admin';
    if(userTabButton){
        userTabButton.classList.toggle('hidden', !isAdmin);
    }
    const userOption = document.querySelector('#topResourceSelect option[value="usuarios"]');
    if(userOption){
        userOption.classList.toggle('hidden', !isAdmin);
    }
    if(!isAdmin && currentSection === 'usuarios'){
        currentSection = 'programas';
        if(topResourceSelect) topResourceSelect.value = currentSection;
        if(sectionSelect) sectionSelect.value = currentSection;
        document.querySelectorAll('.tab-button').forEach(b => b.classList.toggle('active', b.dataset.resource === currentSection));
    }
}

function showLogin(){
    if(loginModal){
        loginModal.setAttribute('aria-hidden','false');
        loginModal.style.display = 'flex';
    }
}
function hideLogin(){
    if(loginModal){
        loginModal.setAttribute('aria-hidden','true');
        loginModal.style.display = 'none';
    }
}

function buildApiUrl(section, id=null){
    if(section === 'usuarios'){
        const url = new URL('auth.php', window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/'));
        url.searchParams.set('action', 'list-users');
        return url;
    }
    let url = API_ROOT + '?resource=' + encodeURIComponent(section);
    if(section === 'congreso') url += '&view=summary';
    if(id) url += '&id=' + encodeURIComponent(id);
    return url;
}

async function uploadCongressLogo(file){
    const data = new FormData();
    data.append('image', file);
    const response = await fetch(CONGRESS_LOGO_UPLOAD_URL, {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
        headers: { 'X-CSRF-Token': csrfToken }
    });
    const result = await response.json().catch(() => null);
    if(!response.ok || !result?.url){
        throw new Error(result?.error || `No se pudo subir el logo (error ${response.status}).`);
    }
    return result.url;
}

function setCongressLogoPreview(path){
    const preview = document.getElementById('logo-congreso-preview');
    if(!preview) return;
    const value = String(path || '').trim();
    if(!value){
        preview.removeAttribute('src');
        preview.hidden = true;
        return;
    }
    preview.src = value;
    preview.hidden = false;
}

// Show/hide create form fields according to resource
function updateCreateFormFields(resource){
    const createForm = document.getElementById('formCreate');
    if(!createForm) return;
    const fieldProgramas = createForm.querySelectorAll('.field-programas');
    const fieldNoticias = createForm.querySelectorAll('.field-noticias');
    const fieldAgenda = createForm.querySelectorAll('.field-agenda');
    const fieldMiembros = createForm.querySelectorAll('.field-miembros');
    const fieldConvenios = createForm.querySelectorAll('.field-convenios');
    const fieldCongreso = createForm.querySelectorAll('.field-congreso');
    const fieldUsuarios = createForm.querySelectorAll('.field-usuarios');

    const show = (list)=> list.forEach(el=> el.classList.remove('hidden'));
    const hide = (list)=> list.forEach(el=> el.classList.add('hidden'));

    if(resource === 'programas'){
        show(fieldProgramas); hide(fieldNoticias); hide(fieldAgenda); hide(fieldMiembros); hide(fieldConvenios); hide(fieldCongreso); hide(fieldUsuarios);
    } else if(resource === 'noticias'){
        show(fieldNoticias); hide(fieldProgramas); hide(fieldAgenda); hide(fieldMiembros); hide(fieldConvenios); hide(fieldCongreso); hide(fieldUsuarios);
    } else if(resource === 'agenda'){
        show(fieldAgenda); hide(fieldProgramas); hide(fieldNoticias); hide(fieldMiembros); hide(fieldConvenios); hide(fieldCongreso); hide(fieldUsuarios);
    } else if(resource === 'miembros'){
        show(fieldMiembros); hide(fieldProgramas); hide(fieldNoticias); hide(fieldAgenda); hide(fieldConvenios); hide(fieldCongreso); hide(fieldUsuarios);
    } else if(resource === 'convenios'){
        show(fieldConvenios); hide(fieldProgramas); hide(fieldNoticias); hide(fieldAgenda); hide(fieldMiembros); hide(fieldCongreso); hide(fieldUsuarios);
    } else if(resource === 'congreso'){
        show(fieldCongreso); hide(fieldProgramas); hide(fieldNoticias); hide(fieldAgenda); hide(fieldMiembros); hide(fieldConvenios); hide(fieldUsuarios);
    } else if(resource === 'usuarios'){
        hide(fieldProgramas); hide(fieldNoticias); hide(fieldAgenda); hide(fieldMiembros); hide(fieldConvenios); hide(fieldCongreso); show(fieldUsuarios);
    }

    // disable and remove `required` from hidden inputs so HTML5 won't block submission
    const allFields = Array.from(createForm.querySelectorAll('.field-programas, .field-noticias, .field-agenda, .field-miembros, .field-convenios, .field-congreso, .field-usuarios'));
    allFields.forEach(node => {
        const visible = !node.classList.contains('hidden');
        node.querySelectorAll('input, textarea, select').forEach(input => {
            if(!visible){
                if(input.hasAttribute('required')){
                    input.dataset.origRequired = 'true';
                    input.removeAttribute('required');
                }
                input.disabled = true;
            } else {
                input.disabled = false;
                if(input.dataset.origRequired === 'true'){
                    input.setAttribute('required','');
                    delete input.dataset.origRequired;
                }
            }
        });
    });

    // keep the hidden resource input in sync
    const hiddenResource = createForm.querySelector('input[name="resource"]');
    if(hiddenResource) hiddenResource.value = resource;
    updateResourceLabels(resource);
}

function updateResourceLabels(resource){
    const labelMap = {
        programas: { title: 'Crear Programa', description: 'Registra nuevos programas.' },
        noticias: { title: 'Crear Noticia', description: 'Registra nuevas noticias.' },
        agenda: { title: 'Crear Evento', description: 'Registra nuevos eventos.' },
        miembros: { title: 'Agregar Personal', description: 'Registra a las personas que trabajan en la fundación: nombre, cargo, correo y foto. El orden de la lista se asigna solo, en el orden en que se registren. Se muestran en "Nosotros" y en el mensaje del presidente.' },
        convenios: { title: 'Crear Convenio', description: 'Registra la institución, ubicación y coordenadas del convenio.' },
        congreso: { title: 'Presentación del Congreso', description: 'Crea un congreso nuevo con sus propios datos. Los congresos registrados aparecen en la tabla inferior.' },
        usuarios: { title: 'Crear Usuario', description: 'Crea usuarios con rol de editor o administrador.' }
    };
    const labels = labelMap[resource] || labelMap.programas;
    if(resourceTitle) resourceTitle.textContent = labels.title;
    if(resourceDescription) resourceDescription.textContent = labels.description;
}

const API_ERROR_MESSAGES = {
    'authentication required': 'Tu sesión expiró. Inicia sesión de nuevo.',
    'invalid CSRF token': 'La página quedó desactualizada. Inténtalo de nuevo.',
    'insufficient privileges': 'No tienes permisos para hacer esto.',
    'admin privileges required': 'Solo un administrador puede hacer esto.',
    'forbidden cross-site request': 'La solicitud fue bloqueada por seguridad. Recarga la página.'
};

function apiErrorMessage(json, res){
    return json?.message || API_ERROR_MESSAGES[json?.error] || json?.error || `Error ${res.status}`;
}

async function handleExpiredSession(){
    if(sessionRedirecting) return;
    sessionRedirecting = true;
    allowUnload = true;
    await showAdminAlert('Tu sesión expiró', 'Vuelve a iniciar sesión para continuar. Los cambios que no se hayan guardado no se pudieron conservar.', 'warning');
    window.location.href = 'login';
}

// 401 = sin sesión. 403 puede ser falta de permisos o sesión caducada: se consulta al servidor para distinguirlo.
async function isSessionExpired(res){
    if(res.status === 401) return true;
    if(res.status === 403){
        const user = await fetchCurrentUser();
        return !user;
    }
    return false;
}

function adminBaseUrl(){
    return window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');
}

function userActionUrl(action){
    const url = new URL('auth.php', adminBaseUrl());
    url.searchParams.set('action', action);
    return url;
}

function refreshCategoryOptions(){
    if(currentSection !== 'noticias' && currentSection !== 'agenda') return;
    const list = document.getElementById(currentSection === 'noticias' ? 'categorias-noticias-list' : 'categorias-agenda-list');
    if(!list) return;
    const categories = new Set();
    currentData.forEach(item => {
        const category = String(item.categoria ?? '').trim();
        if(category) categories.add(category);
    });
    list.replaceChildren(...Array.from(categories).sort((a, b) => a.localeCompare(b, 'es')).map(category => {
        const option = document.createElement('option');
        option.value = category;
        return option;
    }));
}

async function fetchData(){
    if(currentSection !== lastLoadedSection){
        lastLoadedSection = currentSection;
        sortState = { key: null, dir: 'asc' };
        currentPage = 1;
    }
    const listado = document.getElementById('listadoResultados');
    if(listado) listado.style.display = 'block';

    let res;
    try {
        res = await fetch(buildApiUrl(currentSection), { credentials: 'same-origin', cache: 'no-store' });
    } catch (error) {
        console.error('fetchData: network error', error);
        currentData = [];
        loadError = 'No se pudo conectar con el servidor. Revisa tu conexión e inténtalo de nuevo.';
        renderTable();
        return;
    }

    if(!res.ok){
        currentData = [];
        if(await isSessionExpired(res)){
            loadError = 'Tu sesión expiró.';
            renderTable();
            await handleExpiredSession();
            return;
        }
        const errorJson = await res.json().catch(() => null);
        loadError = res.status === 403 ? 'No tienes permisos para ver esta sección.' : apiErrorMessage(errorJson, res);
        renderTable();
        return;
    }

    let json = null;
    try {
        json = await res.json();
    } catch (err) {
        console.error('fetchData: failed to parse JSON response', err);
        currentData = [];
        loadError = 'El servidor devolvió una respuesta inesperada. Inténtalo de nuevo.';
        renderTable();
        return;
    }
    loadError = '';
    currentData = (json && Array.isArray(json.data)) ? json.data : [];
    refreshCategoryOptions();
    renderTable();
}

const NUMERIC_COLUMNS = ['id', 'orden', 'vacantes', 'es_destacada'];

function isEmptyValue(value){
    return value === null || value === undefined || value === '';
}

// Para buscar sin distinguir mayúsculas ni tildes, y sin tomar en cuenta las etiquetas HTML del contenido.
function normalizeSearchText(value){
    return String(value ?? '').replace(/<[^>]*>/g, ' ').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

function searchableText(item){
    return normalizeSearchText(Object.values(item).map(value => (value !== null && typeof value === 'object') ? JSON.stringify(value) : value).join(' '));
}

// Filtra y ordena TODOS los registros cargados; la paginación se aplica después.
function getVisibleRows(){
    const query = normalizeSearchText(searchInput ? searchInput.value : '').trim();
    const rows = query ? currentData.filter(item => searchableText(item).includes(query)) : currentData.slice();
    if(sortState.key){
        const key = sortState.key;
        const direction = sortState.dir === 'desc' ? -1 : 1;
        const numeric = NUMERIC_COLUMNS.includes(key);
        rows.sort((a, b) => {
            const emptyA = isEmptyValue(a[key]);
            const emptyB = isEmptyValue(b[key]);
            if(emptyA || emptyB) return emptyA === emptyB ? 0 : (emptyA ? 1 : -1);
            if(numeric){
                const diff = Number(a[key]) - Number(b[key]);
                if(!Number.isNaN(diff)) return diff * direction;
            }
            return String(a[key]).localeCompare(String(b[key]), 'es', { numeric: true, sensitivity: 'base' }) * direction;
        });
    }
    return rows;
}

function onSort(key){
    if(sortState.key === key){
        // Primer clic: ascendente. Segundo: descendente. Tercero: vuelve al orden original.
        sortState = sortState.dir === 'asc' ? { key, dir: 'desc' } : { key: null, dir: 'asc' };
    } else {
        sortState = { key, dir: 'asc' };
    }
    currentPage = 1;
    renderTable();
}

function publicUrlFor(section, item){
    const id = item && item.id !== undefined && item.id !== null ? encodeURIComponent(item.id) : '';
    switch(section){
        case 'programas': return id ? `programas?id=${id}` : null;
        case 'noticias': return id ? `noticia?id=${id}` : null;
        case 'agenda': return id ? `evento?id=${id}` : null;
        case 'miembros': return 'mensaje-presidente';
        case 'convenios': return 'convenios';
        case 'congreso': return id ? `congreso?id=${id}` : null;
        default: return null;
    }
}

function createSiteLink(url){
    const link = document.createElement('a');
    link.href = url;
    link.target = '_blank';
    link.rel = 'noopener';
    link.className = 'actions-btn btn-view';
    link.textContent = 'Ver en el sitio';
    link.title = 'Abrir la página pública en una pestaña nueva';
    return link;
}

function renderPager(total, totalPages){
    const pager = document.getElementById('tablePager');
    if(!pager) return;
    pager.innerHTML = '';
    if(!total) return;

    const from = (currentPage - 1) * PAGE_SIZE + 1;
    const to = Math.min(total, currentPage * PAGE_SIZE);
    const info = document.createElement('span');
    info.className = 'table-pager-info';
    info.textContent = `Mostrando ${from}â€“${to} de ${total}`;
    pager.appendChild(info);
    if(totalPages <= 1) return;

    const nav = document.createElement('div');
    nav.className = 'table-pager-nav';
    const addButton = (label, page, options = {}) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'pager-btn' + (options.active ? ' active' : '');
        button.textContent = label;
        button.disabled = Boolean(options.disabled);
        if(options.active) button.setAttribute('aria-current', 'page');
        if(options.aria) button.setAttribute('aria-label', options.aria);
        button.addEventListener('click', () => { currentPage = page; renderTable(); });
        nav.appendChild(button);
    };

    addButton('â€¹ Anterior', currentPage - 1, { disabled: currentPage === 1 });
    let previous = 0;
    Array.from(new Set([1, totalPages, currentPage - 1, currentPage, currentPage + 1]))
        .filter(page => page >= 1 && page <= totalPages)
        .sort((a, b) => a - b)
        .forEach(page => {
            if(page - previous > 1){
                const gap = document.createElement('span');
                gap.className = 'table-pager-gap';
                gap.textContent = 'â€¦';
                nav.appendChild(gap);
            }
            addButton(String(page), page, { active: page === currentPage, aria: `Página ${page}` });
            previous = page;
        });
    addButton('Siguiente â€º', currentPage + 1, { disabled: currentPage === totalPages });
    pager.appendChild(nav);
}

function renderTable(){
    tableHead.innerHTML = '';
    tableBody.innerHTML = '';
    const cols = getColumnsForSection(currentSection);

    const headRow = document.createElement('tr');
    cols.forEach(col => {
        const th = document.createElement('th');
        th.scope = 'col';
        if(col.key === 'actions' || col.key === 'imagen'){
            th.textContent = col.label;
        } else {
            const isSorted = sortState.key === col.key;
            th.setAttribute('aria-sort', isSorted ? (sortState.dir === 'desc' ? 'descending' : 'ascending') : 'none');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'th-sort';
            button.title = 'Ordenar por ' + col.label;
            button.append(col.label + ' ');
            const arrow = document.createElement('span');
            arrow.setAttribute('aria-hidden', 'true');
            arrow.textContent = isSorted ? (sortState.dir === 'desc' ? 'â–¼' : 'â–²') : 'â†•';
            button.appendChild(arrow);
            button.addEventListener('click', () => onSort(col.key));
            th.appendChild(button);
        }
        headRow.appendChild(th);
    });
    tableHead.appendChild(headRow);

    const rows = getVisibleRows();
    const totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
    if(currentPage > totalPages) currentPage = totalPages;
    const start = (currentPage - 1) * PAGE_SIZE;
    const pageRows = rows.slice(start, start + PAGE_SIZE);

    if(!pageRows.length){
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = cols.length;
        td.className = 'table-empty' + (loadError ? ' table-error' : '');
        const query = searchInput ? searchInput.value.trim() : '';
        td.textContent = loadError || (query ? `No hay resultados para «${query}».` : 'Todavía no hay registros en esta sección.');
        tr.appendChild(td);
        tableBody.appendChild(tr);
    }

    pageRows.forEach(row => {
        const tr = document.createElement('tr');
        cols.forEach(col => {
            const td = document.createElement('td');
            if(col.key === 'actions'){
                td.className = 'actions-cell';
                const siteUrl = publicUrlFor(currentSection, row);
                if(siteUrl && (currentSection !== 'congreso' || Number(row.publicado) === 1)) {
                    td.appendChild(createSiteLink(siteUrl));
                }
                if(currentUser && currentUser.role === 'admin'){
                    td.appendChild(createActionButton('Eliminar','btn-delete',()=>onDelete(row)));
                }
                if(currentUser && (currentUser.role === 'admin' || currentUser.role === 'editor')){
                    td.appendChild(createActionButton('Editar','btn-edit',()=>onEdit(row)));
                }
            } else if(col.key === 'imagen' && row[col.key]){
                const img = document.createElement('img'); img.src = row[col.key]; img.style.maxWidth='80px'; img.style.height='auto'; img.style.borderRadius='0.75rem'; td.appendChild(img);
            } else {
                td.textContent = col.format ? col.format(row[col.key], row) : (row[col.key] ?? '');
            }
            tr.appendChild(td);
        });
        tableBody.appendChild(tr);
    });
    renderPager(rows.length, totalPages);
}

function createActionButton(title, cls, handler){
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'actions-btn ' + (cls || '');
    b.textContent = title;
    b.addEventListener('click', handler);
    return b;
}

function getColumnsForSection(section){
    switch(section){
        case 'programas': return [
            {label:'ID', key:'id'}, {label:'Título', key:'titulo'}, {label:'Categoría', key:'categoria'}, {label:'Institución', key:'autor'}, {label:'Fecha Inicio', key:'fecha_inicio'}, {label:'Acciones', key:'actions'}
        ];
        case 'agenda': return [
            {label:'ID', key:'id'}, {label:'Título', key:'titulo'}, {label:'Categoría', key:'categoria'}, {label:'Fecha', key:'fecha_evento'}, {label:'Fecha de fin', key:'fecha_fin'}, {label:'Hora', key:'hora_evento'}, {label:'Lugar', key:'lugar'}, {label:'Imagen', key:'imagen'}, {label:'Acciones', key:'actions'}
        ];
        case 'miembros': return [
            {label:'ID', key:'id'}, {label:'Nombre', key:'nombre'}, {label:'Cargo', key:'cargo'}, {label:'Correo', key:'correo'}, {label:'Foto', key:'imagen'}, {label:'Acciones', key:'actions'}
        ];
        case 'convenios': return [
            {label:'ID', key:'id'}, {label:'Institución', key:'institucion'}, {label:'País', key:'pais'}, {label:'Ciudad', key:'ciudad'}, {label:'Acciones', key:'actions'}
        ];
        case 'congreso': return [
            {label:'ID', key:'id'}, {label:'Título', key:'titulo'}, {label:'Resumen', key:'summary'}, {label:'Fecha', key:'date'}, {label:'Hora', key:'time'}, {label:'Estado', key:'publicado', format: value => Number(value) === 1 ? 'Publicado' : 'Borrador'}, {label:'Acciones', key:'actions'}
        ];
        case 'usuarios': return [
            {label:'ID', key:'id'}, {label:'Usuario', key:'username'}, {label:'Rol', key:'role', format: value => value === 'admin' ? 'Administrador' : (value === 'editor' ? 'Editor' : (value ?? ''))}, {label:'Creado', key:'created_at'}, {label:'Acciones', key:'actions'}
        ];
        case 'noticias':
        default: return [
            {label:'ID', key:'id'}, {label:'Título', key:'titulo'}, {label:'Categoría', key:'categoria'}, {label:'Destacada', key:'es_destacada', format: value => Number(value) === 1 ? 'Sí' : ''}, {label:'Fecha Evento', key:'fecha_evento'}, {label:'Acciones', key:'actions'}
        ];
    }
}

async function onDelete(row){
    const id = row.id;
    if(currentSection === 'usuarios'){
        if(currentUser && String(currentUser.id) === String(id)){
            await showAdminAlert('No se puede eliminar', 'No puedes eliminar tu propia cuenta mientras tienes la sesión iniciada.', 'info');
            return;
        }
        if (!await confirmAdminAction('¿Eliminar usuario?', `Se eliminará al usuario «${row.username}». Esta acción no se puede deshacer.`)) return;
        try {
            const formData = new FormData();
            formData.append('id', id);
            const res = await fetch(userActionUrl('delete-user'), { method: 'POST', body: formData, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrfToken } });
            const json = await res.json().catch(() => null);
            if(res.ok){
                await fetchData();
                await showAdminAlert('Usuario eliminado', 'El usuario se eliminó correctamente.');
            } else if(await isSessionExpired(res)){
                await handleExpiredSession();
            } else {
                await showAdminAlert('No se pudo eliminar', apiErrorMessage(json, res), 'error');
            }
        } catch (error) {
            await showAdminAlert('Error al eliminar', error.message || 'Ocurrió un error inesperado.', 'error');
        }
        return;
    }
    const itemName = currentSection === 'congreso' ? (row.titulo || `congreso con ID ${id}`) : `registro con ID ${id}`;
    if (!await confirmAdminAction('¿Eliminar elemento?', `Se eliminará «${itemName}». Esta acción no se puede deshacer.`)) return;

    try {
        const url = buildApiUrl(currentSection, id);
        const formData = new FormData();
        formData.append('_method', 'DELETE');
        const res = await fetch(url, { method: 'POST', body: formData, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrfToken } });
        const json = await res.json().catch(() => null);
        if(res.ok){
            await fetchData();
            await showAdminAlert('Eliminado', 'El registro se eliminó correctamente.');
        } else if(await isSessionExpired(res)){
            await handleExpiredSession();
        } else {
            await showAdminAlert('No se pudo eliminar', apiErrorMessage(json, res), 'error');
        }
    } catch (error) {
        await showAdminAlert('Error al eliminar', error.message || 'Ocurrió un error inesperado.', 'error');
    }
}

const SECTION_SINGULAR = { programas: 'programa', noticias: 'noticia', agenda: 'evento', miembros: 'miembro', convenios: 'convenio', congreso: 'congreso', usuarios: 'usuario' };

function onEdit(item){
    if(currentSection === 'congreso'){
        window.location.href = `admin-congreso?id=${encodeURIComponent(item.id)}`;
        return;
    }
    openEditScreen(item, true);
}

function openEditScreen(item, pushHistory = true){
    editingItem = item;
    const singular = SECTION_SINGULAR[currentSection] || 'registro';
    modalTitle.textContent = 'Editar ' + singular;
    const name = item.titulo || item.nombre || item.institucion || item.username || '';
    if(editScreenSubtitle) editScreenSubtitle.textContent = (name ? name + ' · ' : '') + 'ID ' + item.id;
    const editViewLink = document.getElementById('editViewLink');
    if(editViewLink){
        const siteUrl = publicUrlFor(currentSection, item);
        editViewLink.hidden = !siteUrl;
        if(siteUrl) editViewLink.href = siteUrl;
    }
    populateFormFields(item);
    // Se toma la "foto" del formulario recién armado para detectar después si hay cambios sin guardar.
    setTimeout(captureEditSnapshot, 0);
    if(pushHistory){
        const url = new URL(window.location.href);
        url.searchParams.set('section', currentSection);
        url.searchParams.set('editar', item.id);
        history.pushState({ editar: item.id }, '', url);
    }
    showEditScreen();
}

function openEditFromUrl(){
    const id = new URLSearchParams(window.location.search).get('editar');
    if(!id) return;
    const item = currentData.find(row => String(row.id) === String(id));
    if(item){
        openEditScreen(item, false);
    } else {
        const url = new URL(window.location.href);
        url.searchParams.delete('editar');
        history.replaceState({}, '', url);
    }
}

function onView(item){
    showAdminAlert('Detalle del registro', JSON.stringify(item, null, 2), 'info');
}

function renderProgramJsonListEditor(container, fieldName, values = []){
    const definitions = fieldName === 'temario'
        ? [
            {key:'titulo', label:'Nombre del módulo', required:true},
            {key:'descripcion', label:'Descripción', type:'textarea'}
        ]
        : [
            {key:'foto', label:'Foto (URL o archivo local)', file:true},
            {key:'nombre', label:'Nombre', required:true},
            {key:'cargo', label:'Cargo'},
            {key:'descripcion', label:'Descripción', type:'textarea'},
            {key:'correo', label:'Correo', type:'email'},
            {key:'linkedin', label:'LinkedIn', type:'url'}
        ];
    const rows = document.createElement('div');
    rows.className = 'program-json-list';
    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'program-json-add';
    addButton.textContent = fieldName === 'temario' ? 'Agregar módulo' : 'Agregar docente/expositor';
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = fieldName;

    const syncValue = () => {
        const items = [];
        Array.from(rows.querySelectorAll('.program-json-item')).forEach((row, rowIndex) => {
            const item = {};
            const fileInput = row.querySelector('[data-program-json-upload]');
            const hasUploadedPhoto = Boolean(fileInput?.files?.length);
            const rowHasContent = hasUploadedPhoto || Array.from(row.querySelectorAll('[data-program-json-field]'))
                .some(field => field.value.trim() !== '');
            definitions.forEach(definition => {
                const control = row.querySelector(`[data-program-json-field="${definition.key}"]`);
                item[definition.key] = control.value.trim() || null;
                if (definition.required) {
                    control.required = rowHasContent;
                }
            });
            if (rowHasContent) {
                if (fileInput) fileInput.name = `docente_foto_${items.length}`;
                items.push(item);
            } else if (fileInput) {
                fileInput.name = `docente_foto_ignored_${rowIndex}`;
            }
        });
        hidden.value = JSON.stringify(items);
    };

    const addRow = value => {
        const row = document.createElement('fieldset');
        row.className = 'program-json-item';
        const legend = document.createElement('legend');
        const updateLegend = () => {
            const index = Array.from(rows.children).indexOf(row) + 1;
            legend.textContent = `${fieldName === 'temario' ? 'Módulo' : 'Docente/expositor'} ${index}`;
        };
        row.appendChild(legend);

        const fields = document.createElement('div');
        fields.className = 'program-json-fields';
        definitions.forEach(definition => {
            const label = document.createElement('label');
            label.className = 'program-json-field';
            label.textContent = definition.label;
            const control = definition.type === 'textarea' ? document.createElement('textarea') : document.createElement('input');
            if (definition.type && definition.type !== 'textarea') control.type = definition.type;
            if (definition.file) control.placeholder = 'Pega una URL o selecciona una foto abajo';
            if (definition.required) control.required = true;
            control.value = value?.[definition.key] ?? '';
            control.dataset.programJsonField = definition.key;
            control.addEventListener('input', syncValue);
            control.addEventListener('change', syncValue);
            label.appendChild(control);
            if (definition.file) {
                const localLabel = document.createElement('span');
                localLabel.textContent = 'Seleccionar desde este dispositivo (JPG, PNG, GIF o WEBP; máximo 2 MB)';
                const fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.accept = 'image/jpeg,image/png,image/gif,image/webp';
                fileInput.dataset.programJsonUpload = 'foto';
                fileInput.addEventListener('change', syncValue);
                label.append(localLabel, fileInput);
            }
            fields.appendChild(label);
        });
        row.appendChild(fields);

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'program-json-remove';
        removeButton.textContent = 'Eliminar';
        removeButton.addEventListener('click', () => {
            row.remove();
            rows.querySelectorAll('.program-json-item').forEach((item, index) => {
                item.querySelector('legend').textContent = `${fieldName === 'temario' ? 'Módulo' : 'Docente/expositor'} ${index + 1}`;
            });
            syncValue();
        });
        row.appendChild(removeButton);
        rows.appendChild(row);
        updateLegend();
        syncValue();
    };

    container.replaceChildren(rows, addButton, hidden);
    addButton.addEventListener('click', () => addRow({}));
    if (Array.isArray(values) && values.length) values.forEach(addRow);
    else addRow({});
}

function resetProgramJsonListEditors(root){
    root.querySelectorAll('[data-program-json-list]').forEach(editor => {
        renderProgramJsonListEditor(editor, editor.dataset.programJsonList, []);
    });
}

function isWideField(field){
    return Boolean(field.wide) || ['richtext', 'textarea', 'json-list', 'file'].includes(field.type);
}

function populateFormFields(item){
    formFields.innerHTML = '';
    editRichEditor = null;
    const fields = getEditableFieldsForSection(currentSection);
    let lastGroup = null;

    fields.forEach(f=>{
        if(f.group && f.group !== lastGroup){
            const heading = document.createElement('h3');
            heading.className = 'edit-group-title';
            heading.textContent = f.group;
            formFields.appendChild(heading);
            lastGroup = f.group;
        }

        const inputId = 'edit-' + f.key;
        const wrapper = document.createElement('div');
        wrapper.className = 'edit-field' + (isWideField(f) ? ' edit-field-wide' : '');
        const caption = document.createElement('label');
        caption.className = 'edit-label';
        caption.textContent = f.label;
        wrapper.appendChild(caption);

        if(f.type === 'json-list'){
            const editor = document.createElement('div');
            editor.className = 'program-json-editor';
            wrapper.appendChild(editor);
            formFields.appendChild(wrapper);
            renderProgramJsonListEditor(editor, f.key, item[f.key]);
            return;
        }

        if(f.type === 'custom-link'){
            const link = document.createElement('a');
            link.href = 'admin-congreso';
            link.className = 'btn-secondary congress-full-editor-link';
            link.textContent = 'Abrir edición completa del Congreso';
            wrapper.appendChild(link);
            formFields.appendChild(wrapper);
            return;
        }

        if(f.type === 'richtext'){
            const container = document.createElement('div');
            container.className = 'quill-editor';
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = f.key;
            wrapper.append(container, hidden);
            formFields.appendChild(wrapper);
            const editor = createRichEditor(container, hidden, {
                placeholder: f.placeholder || 'Escribe la descripción aquí...',
                html: item[f.key] ?? ''
            });
            if(editor){
                editRichEditor = editor;
            } else {
                // Sin Quill (por ejemplo, si el CDN no carga) se usa un cuadro de texto simple.
                container.remove();
                hidden.remove();
                const fallback = document.createElement('textarea');
                fallback.rows = 8;
                fallback.name = f.key;
                fallback.id = inputId;
                fallback.className = 'input-field';
                fallback.value = item[f.key] ?? '';
                caption.htmlFor = inputId;
                wrapper.appendChild(fallback);
            }
            return;
        }

        caption.htmlFor = inputId;
        let input;
        let fileHintText = '';

        if(f.type === 'select'){
            input = document.createElement('select');
            input.name = f.key;
            input.className = 'input-field';
            f.options.forEach(option => {
                const element = document.createElement('option');
                element.value = option.value;
                element.textContent = option.label;
                input.appendChild(element);
            });
            input.value = item[f.key] ?? '';
        } else if(f.type === 'textarea'){
            input = document.createElement('textarea');
            input.rows = f.rows || 4;
            input.name = f.key;
            input.value = Array.isArray(item[f.key]) ? JSON.stringify(item[f.key], null, 2) : (item[f.key] ?? '');
            input.className = 'input-field';
            if(f.maxlength) input.maxLength = f.maxlength;
        } else if(f.type === 'file'){
            const currentPath = item[f.key] ?? '';
            const current = document.createElement('input');
            current.type = 'hidden';
            current.name = f.key + '_current';
            current.value = currentPath;
            wrapper.appendChild(current);
            if(currentPath){
                const preview = document.createElement('img');
                preview.className = 'edit-image-preview';
                preview.src = currentPath;
                preview.alt = 'Imagen actual';
                wrapper.appendChild(preview);
                const hint = document.createElement('p');
                hint.className = 'edit-hint';
                hint.textContent = 'Imagen actual. Selecciona un archivo solo si quieres reemplazarla.';
                wrapper.appendChild(hint);
            }
            input = document.createElement('input');
            input.type = 'file';
            input.name = f.key;
            const acceptedTypes = ['miembros', 'convenios'].includes(currentSection) ? IMAGE_TYPES_NO_GIF : IMAGE_TYPES_DEFAULT;
            input.accept = acceptedTypes.join(',');
            input.className = 'input-field';
            fileHintText = `Formatos permitidos: ${imageFormatsText(acceptedTypes)}. Máximo 2 MB.`;
        } else if(f.type === 'checkbox'){
            input = document.createElement('input');
            input.type = 'checkbox';
            input.name = f.key;
            input.value = '1';
            input.checked = item[f.key] === true || Number(item[f.key]) === 1;
            input.className = 'edit-checkbox';
        } else {
            input = document.createElement('input');
            let value = item[f.key] ?? '';
            if(f.key && f.key.toLowerCase().includes('fecha')){ input.type = 'date'; value = String(value).slice(0, 10); }
            else if(f.key && f.key.toLowerCase().includes('hora')){ input.type = 'time'; value = String(value).slice(0, 5); }
            else input.type = ['email', 'number', 'tel', 'url', 'password', 'time'].includes(f.type) ? f.type : 'text';
            if(f.type === 'number' && f.min !== undefined) input.min = String(f.min);
            input.name = f.key;
            input.value = value;
            input.className = 'input-field';
            if(f.readonly) input.readOnly = true;
            if(f.list) input.setAttribute('list', f.list);
            if(f.limit) input.maxLength = f.limit;
            if(f.autocomplete) input.autocomplete = f.autocomplete;
        }
        input.id = inputId;
        if(f.type === 'checkbox'){
            const checkLabel = document.createElement('label');
            checkLabel.className = 'admin-check';
            checkLabel.htmlFor = inputId;
            const checkText = document.createElement('span');
            checkText.textContent = f.checkboxLabel || f.label;
            checkLabel.append(input, checkText);
            wrapper.appendChild(checkLabel);
        } else {
            wrapper.appendChild(input);
        }
        if(f.hint){
            const hintText = document.createElement('p');
            hintText.className = 'edit-hint';
            hintText.textContent = f.hint;
            wrapper.appendChild(hintText);
        }
        if(fileHintText){
            const formats = document.createElement('p');
            formats.className = 'edit-hint';
            formats.textContent = fileHintText;
            wrapper.appendChild(formats);
        }
        if(f.maxlength) attachCharCounter(input, f.maxlength);
        formFields.appendChild(wrapper);
    });
}

function getEditableFieldsForSection(section){
    switch(section){
        case 'programas': return [
            {group:'Información general', key:'titulo', label:'Título', wide:true}, {group:'Información general', key:'categoria', label:'Categoría'}, {group:'Información general', key:'autor', label:'Institución asociada', hint:'Organización con la que se dicta el programa (ej. UNESCO, MIT). Alimenta el filtro "Convenio" de /programas.'}, {group:'Información general', key:'link', label:'Link', wide:true}, {group:'Información general', key:'imagen', label:'Imagen', type:'file'},
            {group:'Fechas y modalidad', key:'fecha_inicio', label:'Fecha Inicio'}, {group:'Fechas y modalidad', key:'fecha_fin', label:'Fecha Fin'}, {group:'Fechas y modalidad', key:'lugar', label:'Lugar', limit:200}, {group:'Fechas y modalidad', key:'duracion', label:'Duración'}, {group:'Fechas y modalidad', key:'modalidad', label:'Modalidad', type:'select', options:[{value:'',label:'Seleccionar'},{value:'presencial',label:'Presencial'},{value:'virtual',label:'Virtual'},{value:'hibrida',label:'Híbrida'}]}, {group:'Fechas y modalidad', key:'horario', label:'Horario'}, {group:'Fechas y modalidad', key:'frecuencia', label:'Frecuencia'}, {group:'Fechas y modalidad', key:'vacantes', label:'Vacantes disponibles', type:'number', min:0},
            {group:'Descripción y contenido', key:'descripcion', label:'Descripción', type:'richtext', placeholder:'Escribe la descripción del programa aquí...'}, {group:'Descripción y contenido', key:'dirigido_a', label:'Dirigido a', type:'textarea'}, {group:'Descripción y contenido', key:'objetivos', label:'Objetivos', type:'textarea'}, {group:'Descripción y contenido', key:'temario', label:'Temario por módulos', type:'json-list'}, {group:'Descripción y contenido', key:'requisitos', label:'Requisitos', type:'textarea'}, {group:'Descripción y contenido', key:'certificacion', label:'Certificación', type:'textarea'}, {group:'Descripción y contenido', key:'docentes', label:'Docentes y expositores', type:'json-list'},
            {group:'Inversión e inscripción', key:'inversion', label:'Inversión', type:'textarea', rows:3}, {group:'Inversión e inscripción', key:'descuentos', label:'Descuentos', type:'textarea', rows:3}, {group:'Inversión e inscripción', key:'contacto_telefono', label:'Teléfono de inscripción', type:'tel'}, {group:'Inversión e inscripción', key:'contacto_whatsapp', label:'WhatsApp de inscripción', type:'tel'}, {group:'Inversión e inscripción', key:'contacto_correo', label:'Correo de inscripción', type:'email'}
        ];
        case 'agenda': return [
            {group:'Datos del evento', key:'titulo', label:'Título', wide:true}, {group:'Datos del evento', key:'fecha_evento', label:'Fecha del evento'}, {group:'Datos del evento', key:'fecha_fin', label:'Fecha de fin (opcional)'}, {group:'Datos del evento', key:'hora_evento', label:'Hora del evento'}, {group:'Datos del evento', key:'lugar', label:'Lugar'}, {group:'Datos del evento', key:'categoria', label:'Categoría', list:'categorias-agenda-list', limit:100}, {group:'Datos del evento', key:'link_inscripcion', label:'Link de inscripción'},
            {group:'Descripción', key:'descripcion', label:'Descripción del evento', type:'richtext', placeholder:'Escribe la descripción del evento aquí...'},
            {group:'Imagen', key:'imagen', label:'Imagen del evento', type:'file'}
        ];
        case 'miembros': return [
            {group:'Datos del personal', key:'nombre', label:'Nombre completo'}, {group:'Datos del personal', key:'cargo', label:'Cargo'}, {group:'Datos del personal', key:'correo', label:'Correo electrónico', type:'email', hint:'Opcional. Déjalo vacío si la persona no tiene correo institucional.'}, {group:'Datos del personal', key:'imagen', label:'Foto', type:'file'}
        ];
        case 'convenios': return [
            {group:'Datos del convenio', key:'institucion', label:'Institución'}, {group:'Datos del convenio', key:'tipo', label:'Tipo'}, {group:'Datos del convenio', key:'pais', label:'País'}, {group:'Datos del convenio', key:'ciudad', label:'Ciudad'}, {group:'Datos del convenio', key:'ubicacion_google', label:'Ubicación de Google Maps', wide:true}, {group:'Datos del convenio', key:'descripcion', label:'Descripción', type:'textarea'}, {group:'Datos del convenio', key:'imagen', label:'Imagen', type:'file'}
        ];
        case 'congreso': return [
            {group:'Congreso CDIA', key:'id', label:'ID', readonly:true},
            {group:'Congreso CDIA', key:'titulo', label:'Título', wide:true},
            {group:'Congreso CDIA', key:'subtitulo', label:'Subtítulo', wide:true},
            {group:'Congreso CDIA', key:'descripcion', label:'Presentación', type:'textarea', rows:4, maxlength:1000},
            {group:'Congreso CDIA', key:'summary', label:'Resumen breve', type:'textarea', rows:3, maxlength:1000},
            {group:'Congreso CDIA', key:'time', label:'Hora', type:'time'},
            {group:'Edición completa', key:'fullEditor', label:'Administración detallada', type:'custom-link', wide:true}
        ];
        case 'usuarios': return [
            {group:'Datos del usuario', key:'username', label:'Usuario', readonly:true}, {group:'Datos del usuario', key:'role', label:'Rol', type:'select', options:[{value:'editor',label:'Editor'},{value:'admin',label:'Administrador'}]},
            {group:'Contraseña', key:'password', label:'Nueva contraseña', type:'password', autocomplete:'new-password', hint:'Déjala vacía si no quieres cambiarla. Mínimo 6 caracteres.', wide:true}
        ];
        case 'noticias':
        default: return [
            {group:'Información', key:'titulo', label:'Título', wide:true}, {group:'Información', key:'autor', label:'Autor'}, {group:'Información', key:'categoria', label:'Categoría', list:'categorias-noticias-list', limit:100}, {group:'Información', key:'es_destacada', label:'Destacada', type:'checkbox', checkboxLabel:'Mostrar esta noticia como destacada', hint:'La noticia destacada aparece arriba en la página de noticias. Si marcas varias, se muestra la más reciente.'}, {group:'Información', key:'fecha_evento', label:'Fecha Evento'}, {group:'Información', key:'link', label:'Link', wide:true},
            {group:'Contenido', key:'descripcion_corta', label:'Descripción corta', type:'textarea', rows:3, maxlength:255}, {group:'Contenido', key:'contenido', label:'Contenido', type:'richtext', placeholder:'Escribe el contenido de la noticia aquí...'},
            {group:'Imagen', key:'imagen', label:'Imagen', type:'file'}
        ];
    }
}

// El riel lateral ahora es hijo de .admin-workspace, que no tiene reglas propias
// de edicion, asi que la marca va directamente en el body.
function showEditScreen(){
    if(!editScreen) return;
    editScreen.hidden = false;
document.body.classList.add('admin-is-editing');
    document.body.classList.add('admin-editing');
    window.scrollTo({ top: 0 });
}

function hideEditScreen(){
    if(!editScreen) return;
    editScreen.hidden = true;
document.body.classList.remove('admin-is-editing');
    document.body.classList.remove('admin-editing');
    editingItem = null;
    editRichEditor = null;
    editSnapshot = '';
    formFields.innerHTML = '';
}

function leaveEditScreen(){
    hideEditScreen();
    if(history.state && history.state.editar){
        history.back();
    } else if(new URLSearchParams(window.location.search).has('editar')){
        const url = new URL(window.location.href);
        url.searchParams.delete('editar');
        history.replaceState({}, '', url);
    }
}

// --- Cambios sin guardar en la pantalla de edición ---
function serializeEditForm(){
    if(editRichEditor) editRichEditor.sync();
    return JSON.stringify(Array.from(formFields.querySelectorAll('input,textarea,select'))
        .filter(control => control.name)
        .map(control => {
            if(control.type === 'file') return [control.name, Array.from(control.files || []).map(file => file.name + ':' + file.size)];
            if(control.type === 'checkbox') return [control.name, control.checked];
            return [control.name, control.value];
        }));
}

function captureEditSnapshot(){
    editSnapshot = serializeEditForm();
}

function isEditDirty(){
    return Boolean(editingItem) && Boolean(editScreen) && !editScreen.hidden && serializeEditForm() !== editSnapshot;
}

function confirmDiscardChanges(){
    if(!isEditDirty()) return Promise.resolve(true);
    if(window.Swal && typeof window.Swal.fire === 'function'){
        const isDark = document.documentElement.classList.contains('admin-dark');
        return window.Swal.fire({
            title: 'Tienes cambios sin guardar',
            text: 'Si sales ahora, se perderán los cambios que hiciste en este registro.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Salir sin guardar',
            cancelButtonText: 'Seguir editando',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            background: isDark ? '#17232d' : '#ffffff',
            color: isDark ? '#e8eef0' : '#1f2937'
        }).then(result => result.isConfirmed);
    }
    return Promise.resolve(window.confirm('Tienes cambios sin guardar. ¿Salir de todos modos?'));
}

async function requestLeaveEditScreen(){
    if(await confirmDiscardChanges()) leaveEditScreen();
}

window.addEventListener('beforeunload', event => {
    if(allowUnload || !isEditDirty()) return;
    event.preventDefault();
    event.returnValue = '';
});

window.addEventListener('popstate', async () => {
    const id = new URLSearchParams(window.location.search).get('editar');
    if(!id){
        if(isEditDirty()){
            const editUrl = new URL(window.location.href);
            editUrl.searchParams.set('section', currentSection);
            editUrl.searchParams.set('editar', editingItem.id);
            const editedId = editingItem.id;
            if(!await confirmDiscardChanges()){
                // Decidió seguir editando: se restaura la dirección de la pantalla de edición.
                history.pushState({ editar: editedId }, '', editUrl);
                return;
            }
        }
        hideEditScreen();
        return;
    }
    const item = currentData.find(row => String(row.id) === String(id));
    if(item) openEditScreen(item, false);
});

function setEditSaving(isSaving){
    [saveBtn, saveBtnBottom].forEach(btn => {
        if(!btn) return;
        btn.disabled = isSaving;
        btn.textContent = isSaving ? 'Guardando...' : 'Guardar cambios';
    });
}

async function saveUserEdit(){
    const role = formFields.querySelector('[name="role"]')?.value || '';
    const password = formFields.querySelector('[name="password"]')?.value || '';
    if(password && password.length < 6){
        await showAdminAlert('Contraseña muy corta', 'La contraseña debe tener al menos 6 caracteres.', 'warning');
        return;
    }
    if(!password && role === editingItem.role){
        await showAdminAlert('Sin cambios', 'No cambiaste el rol ni escribiste una contraseña nueva.', 'info');
        return;
    }

    const formData = new FormData();
    formData.append('id', editingItem.id);
    if(role && role !== editingItem.role) formData.append('role', role);
    if(password) formData.append('password', password);

    const editedSelf = Boolean(currentUser) && String(currentUser.id) === String(editingItem.id);
    setEditSaving(true);
    try {
        const res = await fetch(userActionUrl('update-user'), { method: 'POST', body: formData, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrfToken } });
        const json = await res.json().catch(() => null);
        if(res.ok){
            leaveEditScreen();
            if(editedSelf) await fetchCurrentUser();
            await fetchData();
            await showAdminAlert('Usuario actualizado', 'Los cambios se guardaron correctamente.');
        } else if(await isSessionExpired(res)){
            await handleExpiredSession();
        } else {
            await showAdminAlert('No se pudo guardar', apiErrorMessage(json, res), 'error');
        }
    } catch (error) {
        await showAdminAlert('Error al guardar', error.message || 'Ocurrió un error inesperado.', 'error');
    } finally {
        setEditSaving(false);
    }
}

async function saveEdit(){
    if(!editingItem) return;
    if(currentSection === 'usuarios'){
        await saveUserEdit();
        return;
    }
    if(!editForm || !validateAdminForm(editForm) || !validateImageFiles(editForm)) return;
    
    // Asegura que el editor enriquecido haya volcado su contenido antes de enviar
    if(editRichEditor) editRichEditor.sync();

    const formData = new FormData();
    const inputs = Array.from(formFields.querySelectorAll('input,textarea,select'));
    let hasNewImage = false;

    for(const inp of inputs){
        if(!inp.name) continue;
        if(inp.type === 'file'){
            if(inp.files && inp.files[0]){
                formData.append(inp.name, inp.files[0]);
                hasNewImage = true;
            }
            continue;
        }

        if(inp.type === 'checkbox'){
            formData.append(inp.name, inp.checked ? '1' : '0');
            continue;
        }

        if(inp.name.endsWith('_current')){
            if(!hasNewImage){
                formData.append('imagen', inp.value);
            }
            continue;
        }

        formData.append(inp.name, inp.value);
    }

    if(currentSection !== 'congreso' && !formData.has('imagen') && editingItem.imagen){
        formData.append('imagen', editingItem.imagen);
    }

    formData.append('_method', 'PUT');
    const url = buildApiUrl(currentSection, editingItem.id);
    setEditSaving(true);
    try {
        const res = await fetch(url, { method: 'POST', body: formData, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrfToken } });
        const json = await res.json().catch(() => null);
        if(res.ok){
            leaveEditScreen();
            await fetchData();
            if(json?.warning) await showAdminAlert('Cambios guardados con una advertencia', json.warning, 'warning');
            else await showAdminAlert('Cambios guardados', 'El registro se actualizó correctamente.');
        } else if(await isSessionExpired(res)){
            await handleExpiredSession();
        } else {
            await showAdminAlert('No se pudo guardar', apiErrorMessage(json, res), 'error');
        }
    } catch (error) {
        await showAdminAlert('Error al guardar', error.message || 'Ocurrió un error inesperado.', 'error');
    } finally {
        setEditSaving(false);
    }
}

// Events
if(sectionSelect){
    sectionSelect.addEventListener('change', ()=>{ currentSection = sectionSelect.value; if(createResourceInput) createResourceInput.value = currentSection; fetchData(); });
}
// top selector sync
if(topResourceSelect){
    topResourceSelect.addEventListener('change', ()=>{
        currentSection = topResourceSelect.value;
        if(sectionSelect) sectionSelect.value = currentSection;
        if(createResourceInput) createResourceInput.value = currentSection;
        fetchData();
    });
    // initialize top selector value
    topResourceSelect.value = currentSection;
}
if(resourceSelect){
    resourceSelect.addEventListener('change', ()=>{ currentSection = resourceSelect.value; if(topResourceSelect) topResourceSelect.value = currentSection; if(createResourceInput) createResourceInput.value = currentSection; fetchData(); });
}
const btnList = document.getElementById('btnListar');
if(btnList){
    btnList.addEventListener('click', ()=>{
        const sel = resourceSelect ? resourceSelect.value : (topResourceSelect ? topResourceSelect.value : currentSection);
        currentSection = sel;
        if(topResourceSelect) topResourceSelect.value = sel;
        if(resourceSelect) resourceSelect.value = sel;
        if(createResourceInput) createResourceInput.value = sel;
        fetchData();
    });
}
if(sectionSelect && topResourceSelect){ sectionSelect.addEventListener('change', ()=>{ topResourceSelect.value = sectionSelect.value; }); }
// tab buttons (if present)
document.querySelectorAll('.tab-button').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        document.querySelectorAll('.tab-button').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        const r = btn.dataset.resource;
        if(r){ currentSection = r; if(sectionSelect) sectionSelect.value = r; if(createResourceInput) createResourceInput.value = r; fetchData(); }
    });
});

// El menu es ahora una barra horizontal en la cabecera y sus enlaces se envuelven
// en varias lineas cuando la pantalla es estrecha, asi que ya no hace falta el
// cajon deslizante ni el backdrop que tenian el menu lateral.

if(refreshBtn){ refreshBtn.addEventListener('click', ()=>fetchData()); }
if(searchInput){ searchInput.addEventListener('input', ()=>{ currentPage = 1; renderTable(); }); }
[cancelBtn, cancelBtnBottom, editBackBtn].forEach(btn => { if(btn) btn.addEventListener('click', requestLeaveEditScreen); });
[saveBtn, saveBtnBottom].forEach(btn => { if(btn) btn.addEventListener('click', saveEdit); });

// Login UI events
if(btnOpenLogin) btnOpenLogin.addEventListener('click', showLogin);
if(closeLogin) closeLogin.addEventListener('click', hideLogin);
if(loginBtn){
    loginBtn.addEventListener('click', async ()=>{
        const u = document.getElementById('loginUser')?.value;
        const p = document.getElementById('loginPass')?.value;
        if(!u || !p){ await showAdminAlert('Datos incompletos', 'Ingresa usuario y contraseña.', 'warning'); return; }
        const fd = new FormData();
        fd.append('username', u);
        fd.append('password', p);
        fd.append('g-recaptcha-response', window.grecaptcha?.getResponse() || '');
        try {
            const res = await fetch(new URL('auth.php', window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/')), { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrfToken } });
            const json = await res.json().catch(() => null);
            if(res.ok){ hideLogin(); await fetchCurrentUser(); await showAdminAlert('Sesión iniciada', 'Ingresaste correctamente.'); }
            else await showAdminAlert('No se pudo iniciar sesión', json?.error || `Error ${res.status}`, 'error');
        } catch (error) {
            await showAdminAlert('Error de conexión', error.message || 'No se pudo iniciar sesión.', 'error');
        } finally {
            if (window.grecaptcha && typeof window.grecaptcha.reset === 'function') window.grecaptcha.reset();
        }
    });
}
async function logoutAdmin(){
    if(!await confirmDiscardChanges()) return;
    try {
        const res = await fetch(new URL('auth.php?action=logout', window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/')), { method: 'GET', credentials: 'same-origin', headers: { 'X-CSRF-Token': csrfToken } });
        if(res.ok){
            await showAdminAlert('Sesión cerrada', 'Has cerrado sesión correctamente.');
            allowUnload = true;
            window.location.href = 'login';
        }
        else await showAdminAlert('No se pudo cerrar sesión', `Error ${res.status}`, 'error');
    } catch (error) {
        await showAdminAlert('Error al cerrar sesión', error.message || 'Ocurrió un error inesperado.', 'error');
    }
}
if(logoutBtn) logoutBtn.addEventListener('click', logoutAdmin);
if(btnLogout) btnLogout.addEventListener('click', logoutAdmin);

// Apertura y cierre del cajon de secciones.
if(adminSidebarToggle){
  adminSidebarToggle.addEventListener('click', () => {
    setAdminSidebarOpen(!document.body.classList.contains('admin-sidebar-open'));
  });
}
if(adminSidebarBackdrop) adminSidebarBackdrop.addEventListener('click', closeAdminSidebar);
// Al navegar a otra seccion el cajon debe cerrarse, o el listado nuevo queda
// tapado por el menu.
sidebarNavLinks.forEach(link => link.addEventListener('click', closeAdminSidebar));
document.addEventListener('keydown', event => {
  if(event.key !== 'Escape') return;
  if(!document.body.classList.contains('admin-sidebar-open')) return;
  closeAdminSidebar();
  if(adminSidebarToggle) adminSidebarToggle.focus();
});

// Si la ventana crece y vuelve el riel fijo, el cajon se queda en un estado que
// ya no corresponde con lo que se ve.
if(window.matchMedia){
  const wideScreen = window.matchMedia('(min-width: 901px)');
  const syncSidebarWithViewport = event => { if(event.matches) closeAdminSidebar(); };
  if(typeof wideScreen.addEventListener === 'function') wideScreen.addEventListener('change', syncSidebarWithViewport);
  else if(typeof wideScreen.addListener === 'function') wideScreen.addListener(syncSidebarWithViewport);
}

// init current user and load page data after auth state is known
fetchCurrentUser().then(user => {
    if(!user) {
        if (window.location.pathname.endsWith('admin') || window.location.pathname.endsWith('/')) {
            window.location.href = 'login';
            return;
        }
        showLogin();
    }
    document.title = 'Admin - ' + currentSection;
    updateCreateFormFields(currentSection);
    fetchData().then(openEditFromUrl).catch(error => console.error('fetchData error', error));
});

// Create form submit support
const createForm = document.getElementById('formCreate');
const createResourceInput = createForm ? createForm.querySelector('input[name="resource"]') : null;
if(createForm){
    resetProgramJsonListEditors(createForm);
    initCreateRichEditors();
    attachCharCounter(document.getElementById('descripcion-noticias'), 255);
    // Use programmatic submit via button to avoid native HTML5 validation on hidden fields
    const createSubmitBtn = document.getElementById('createSubmitBtn');
    if(createSubmitBtn){
        createSubmitBtn.addEventListener('click', async () => {
            const resource = createResourceInput?.value || currentSection;
            if(resource === 'usuarios'){
                await createUser();
                return;
            }
            if(createRichEditors[resource]) createRichEditors[resource].sync();
            if(!validateAdminForm(createForm) || !validateImageFiles(createForm)) return;
            const formData = new FormData(createForm);
            try {
                if(resource === 'congreso'){
                    const logoFile = document.getElementById('logo-congreso-file')?.files?.[0];
                    if(logoFile){
                        const logoPath = await uploadCongressLogo(logoFile);
                        formData.set('logo', logoPath);
                    }
                }
                if(resource === 'congreso') formData.append('_method', 'PUT');
                const res = await fetch(buildApiUrl(resource), { method: 'POST', body: formData, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrfToken } });
                if(res.ok){
                    const createdJson = await res.json().catch(() => null);
                    createForm.reset();
                    Object.values(createRichEditors).forEach(editor => { if(editor) editor.clear(); });
                    resetProgramJsonListEditors(createForm);
                    updateCreateFormFields(resource);
                    await fetchData().catch(error => console.error('create refresh error', error));
                    if(resource === 'congreso'){
                        const logoFile = document.getElementById('logo-congreso-file');
                        if(logoFile) logoFile.value = '';
                    }
                    if(createdJson?.warning) await showAdminAlert('Registro creado con una advertencia', createdJson.warning, 'warning');
                    else await showAdminAlert('Registro creado', 'La información se guardó correctamente.');
                } else {
                    const json = await res.json().catch(() => null);
                    if(await isSessionExpired(res)) await handleExpiredSession();
                    else await showAdminAlert('No se pudo crear', apiErrorMessage(json, res), 'error');
                }
            } catch (err) {
                console.error('create submit error', err);
                await showAdminAlert('Error al crear', err.message || 'Ocurrió un error inesperado.', 'error');
            }
        });
    }
}

const congressLogoFile = document.getElementById('logo-congreso-file');
const congressLogoPath = document.getElementById('logo-congreso');
if(congressLogoFile && congressLogoPath){
    congressLogoFile.addEventListener('change', () => {
        const file = congressLogoFile.files?.[0];
        if(!file) return;
        setCongressLogoPreview(URL.createObjectURL(file));
    });
}

async function createUser(){
    const username = document.getElementById('usuario-usuarios')?.value.trim();
    const password = document.getElementById('password-usuarios')?.value;
    const role = document.getElementById('role-usuarios')?.value || 'editor';
    if(!username || !password){
        await showAdminAlert('Datos incompletos', 'Ingresa usuario y contraseña.', 'warning');
        return;
    }
    if(password.length < 6){
        await showAdminAlert('Contraseña muy corta', 'La contraseña debe tener al menos 6 caracteres.', 'warning');
        return;
    }
    const url = userActionUrl('create-user');
    const fd = new FormData();
    fd.append('username', username);
    fd.append('password', password);
    fd.append('role', role);
    const res = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrfToken } });
    if(res.ok){
        createForm.reset();
        updateCreateFormFields('usuarios');
        await fetchData();
        await showAdminAlert('Usuario creado', 'El usuario se creó correctamente.');
    } else {
        const json = await res.json().catch(() => null);
        if(await isSessionExpired(res)) await handleExpiredSession();
        else await showAdminAlert('No se pudo crear el usuario', apiErrorMessage(json, res), 'error');
    }
}

// when tabs change, update create form fields as well
document.querySelectorAll('.tab-button').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const r = btn.dataset.resource;
        if(r) updateCreateFormFields(r);
    });
});
