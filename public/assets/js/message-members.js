(function () {
  const grid = document.querySelector('.message-members .members-grid');
  if (!grid) return;

  function makePhoto(member) {
    if (!member.imagen) {
      const placeholder = document.createElement('div');
      placeholder.className = 'member-photo member-photo-placeholder';
      placeholder.setAttribute('role', 'img');
      placeholder.setAttribute('aria-label', 'Foto pendiente');
      return placeholder;
    }

    const image = document.createElement('img');
    image.className = 'member-photo';
    image.src = member.imagen;
    image.alt = member.nombre || 'Miembro de la fundación';
    image.addEventListener('error', function () {
      const placeholder = document.createElement('div');
      placeholder.className = 'member-photo member-photo-placeholder';
      placeholder.setAttribute('role', 'img');
      placeholder.setAttribute('aria-label', 'Foto no disponible');
      image.replaceWith(placeholder);
    }, { once: true });
    return image;
  }

  function renderMembers(members) {
    grid.replaceChildren();
    if (members.length === 0) {
      const message = document.createElement('p');
      message.className = 'members-loading';
      message.textContent = 'No hay miembros registrados para mostrar.';
      grid.appendChild(message);
      return;
    }

    members.forEach(function (member) {
      const card = document.createElement('article');
      card.className = 'member-card';

      const name = document.createElement('h3');
      name.textContent = member.nombre || '';
      const role = document.createElement('p');
      role.textContent = member.cargo || '';

      card.append(makePhoto(member), name, role);
      grid.appendChild(card);
    });
  }

  const basePath = window.location.pathname.replace(/\/[^/]*$/, '/');
  const endpoint = new URL('index.php?resource=miembros', window.location.origin + basePath);

  fetch(endpoint)
    .then(function (response) {
      if (!response.ok) throw new Error('HTTP ' + response.status);
      return response.json();
    })
    .then(function (result) {
      if (!result || !Array.isArray(result.data)) {
        throw new Error('La respuesta de miembros no tiene el formato esperado.');
      }
      renderMembers(result.data);
    })
    .catch(function (error) {
      console.error('No se pudieron cargar los miembros de la fundación.', error);
      const message = document.createElement('p');
      message.className = 'members-loading';
      message.textContent = 'No se pudieron cargar los miembros. Intenta actualizar la página.';
      grid.replaceChildren(message);
    })
    .finally(function () {
      grid.setAttribute('aria-busy', 'false');
    });
})();
