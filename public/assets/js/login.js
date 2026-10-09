async function redirectIfNotAuthenticated() {
  const pathname = window.location.pathname.toLowerCase();
  if (!pathname.endsWith('admin')) {
    return;
  }

  try {
    const res = await fetch('auth.php', { credentials: 'same-origin' });
    const json = await res.json().catch(() => null);
    if (!json || !json.user) {
      window.location.href = 'login';
    }
  } catch (err) {
    window.location.href = 'login';
  }
}

redirectIfNotAuthenticated();

const loginSubmit = document.getElementById('loginSubmit');
const loginMessage = document.getElementById('loginMessage');
const usernameInput = document.getElementById('username');
const passwordInput = document.getElementById('password');

// El mensaje en linea se conserva como respaldo: si SweetAlert2 no llega a
// cargar (sin red, CDN bloqueado) el error sigue siendo visible, y además
// sirve como región aria-live para lectores de pantalla.
function showLoginError(title, text) {
  if (loginMessage) {
    loginMessage.textContent = text;
  }

  if (!window.Swal || typeof window.Swal.fire !== 'function') {
    console.error('SweetAlert2 no está disponible:', title, text);
    return Promise.resolve();
  }

  return window.Swal.fire({
    title,
    text,
    icon: 'error',
    confirmButtonText: 'Reintentar',
    confirmButtonColor: '#d9a20b',
    background: '#ffffff',
    color: '#1f2937',
    focusConfirm: false
  });
}

// auth.php responde en ingles y sin campo 'message' en los casos habituales,
// asi que se traduce por codigo de error y estado HTTP para no exponer
// respuestas crudas ni confundir un 403 de permisos con uno de sesion caducada.
function describeLoginError(status, json) {
  const code = json && typeof json.error === 'string' ? json.error : '';

  if (status === 401) {
    return 'El usuario o la contraseña no son correctos.';
  }
  if (status === 403) {
    if (code === 'invalid CSRF token') {
      return 'La sesión quedó desactualizada. Recarga la página e inténtalo nuevamente.';
    }
    return 'Tu cuenta no tiene acceso al panel de administración.';
  }
  if (status === 400) {
    if (code === 'invalid recaptcha') {
      return 'Confirma que no eres un robot para iniciar sesión.';
    }
    return 'Completa el usuario y la contraseña para continuar.';
  }
  const message = json && typeof json.message === 'string' ? json.message : '';
  return message || 'No se pudo iniciar sesión. Inténtalo nuevamente.';
}

function setSubmitting(isSubmitting) {
  if (!loginSubmit) return;
  loginSubmit.disabled = isSubmitting;
  loginSubmit.textContent = isSubmitting ? 'Verificando...' : 'Entrar';
}

async function login() {
  const username = usernameInput.value.trim();
  const password = passwordInput.value.trim();

  if (loginMessage) {
    loginMessage.textContent = '';
  }

  if (!username || !password) {
    await showLoginError('Faltan datos', 'Ingresa tu usuario y contraseña.');
    (!username ? usernameInput : passwordInput).focus();
    return;
  }

  setSubmitting(true);

  const formData = new FormData();
  formData.append('username', username);
  formData.append('password', password);
  const captchaResponse = window.grecaptcha?.getResponse();
  formData.append('g-recaptcha-response', captchaResponse || '');

  try {
    const sessionResponse = await fetch('auth.php', { credentials: 'same-origin', cache: 'no-store' });
    const sessionData = await sessionResponse.json().catch(() => null);
    const csrfToken = sessionData?.csrf_token;
    if (!csrfToken) {
      await showLoginError('Sesión no válida', 'No se pudo validar la sesión. Recarga la página e inténtalo nuevamente.');
      return;
    }

    const res = await fetch('auth.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: { 'X-CSRF-Token': csrfToken }
    });
    const json = await res.json().catch(() => null);

    if (res.ok && json && json.user) {
      window.location.href = 'admin';
      return;
    }

    await showLoginError('No se pudo iniciar sesión', describeLoginError(res.status, json));

    // Tras un intento fallido se enfoca el campo de contraseña: es lo que el
    // usuario necesita corregir, y Permite reintentar sin tocar el mouse.
    passwordInput.select();
  } catch (err) {
    await showLoginError('Error de conexión', 'No se pudo conectar con el servidor. Revisa tu conexión e inténtalo nuevamente.');
    console.error(err);
  } finally {
    if (window.grecaptcha && typeof window.grecaptcha.reset === 'function') {
      window.grecaptcha.reset();
    }
    setSubmitting(false);
  }
}

if (loginSubmit) {
  loginSubmit.addEventListener('click', login);
}

if (usernameInput) {
  usernameInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      login();
    }
  });
}

if (passwordInput) {
  passwordInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      login();
    }
  });
}

