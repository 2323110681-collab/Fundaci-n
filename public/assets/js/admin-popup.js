(() => {
  const notice = document.getElementById('popupNotice');
  if (notice) {
    const isSuccess = notice.dataset.type === 'success';
    const title = isSuccess ? 'Configuración guardada' : 'No se pudo guardar';
    if (window.Swal && typeof window.Swal.fire === 'function') {
      window.Swal.fire({
        icon: isSuccess ? 'success' : 'error',
        title,
        text: notice.dataset.message || 'Ocurrió un error inesperado.'
      });
    } else {
      console.error('SweetAlert2 no está disponible para mostrar el resultado del aviso.');
      window.alert(`${title}: ${notice.dataset.message || 'Ocurrió un error inesperado.'}`);
    }
  }

  const toggle = document.getElementById('adminSidebarToggle');
  const backdrop = document.querySelector('.admin-sidebar-backdrop');

  function setSidebarOpen(isOpen) {
    document.body.classList.toggle('admin-sidebar-open', isOpen);
    if (toggle) toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    if (backdrop) backdrop.hidden = !isOpen;
  }

  if (toggle) toggle.addEventListener('click', () => {
    setSidebarOpen(!document.body.classList.contains('admin-sidebar-open'));
  });
  if (backdrop) backdrop.addEventListener('click', () => setSidebarOpen(false));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.body.classList.contains('admin-sidebar-open')) {
      setSidebarOpen(false);
      if (toggle) toggle.focus();
    }
  });

  const logoutButton = document.querySelector('[data-admin-logout]');
  if (logoutButton) logoutButton.addEventListener('click', async () => {
    logoutButton.disabled = true;
    try {
      const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
      const response = await fetch('auth.php?action=logout', {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'X-CSRF-Token': csrfToken }
      });
      if (!response.ok) throw new Error('No se pudo cerrar la sesión. Recarga la página e inténtalo de nuevo.');
      window.location.href = 'login';
    } catch (error) {
      window.alert(error.message || 'Ocurrió un error al cerrar la sesión.');
      logoutButton.disabled = false;
    }
  });
})();
