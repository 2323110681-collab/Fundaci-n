(() => {
  const createButton = document.getElementById('createCongress');
  const message = document.getElementById('createMessage');
  const authUrl = new URL('auth.php', window.location.href);
  const createUrl = new URL('index.php?resource=congreso&view=create', window.location.href);

  createButton.addEventListener('click', async () => {
    createButton.disabled = true;
    createButton.textContent = 'Creando…';
    message.hidden = true;
    try {
      const authResponse = await fetch(authUrl, { credentials: 'same-origin', cache: 'no-store' });
      const auth = await authResponse.json();
      if (!authResponse.ok || !auth.csrf_token) {
        throw new Error('Tu sesión expiró. Vuelve a iniciar sesión.');
      }
      const response = await fetch(createUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-CSRF-Token': auth.csrf_token },
      });
      const result = await response.json();
      if (!response.ok || !result.data?.id) {
        throw new Error(result.error || `No se pudo crear el congreso (error ${response.status}).`);
      }
      window.location.href = `admin-congreso?id=${encodeURIComponent(result.data.id)}`;
    } catch (error) {
      message.textContent = error.message || 'No se pudo crear el congreso.';
      message.className = 'admin-message error';
      message.hidden = false;
      createButton.disabled = false;
      createButton.textContent = 'Crear congreso';
    }
  });
})();
