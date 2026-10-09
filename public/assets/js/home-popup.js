(() => {
  const popupUrl = new URL('popup.php', document.baseURI);

  function closePopup(dialog, previousOverflow, previousFocus) {
    dialog.remove();
    document.body.style.overflow = previousOverflow;
    if (previousFocus instanceof HTMLElement && previousFocus.isConnected) previousFocus.focus();
  }

  function showPopup(configuration) {
    const previousFocus = document.activeElement;
    const previousOverflow = document.body.style.overflow;
    const overlay = document.createElement('div');
    overlay.className = 'site-popup';
    overlay.setAttribute('role', 'presentation');

    const dialog = document.createElement('div');
    dialog.className = 'site-popup-dialog';
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-labelledby', 'sitePopupTitle');

    let onKeydown;
    const dismiss = () => {
      if (onKeydown) document.removeEventListener('keydown', onKeydown);
      closePopup(overlay, previousOverflow, previousFocus);
    };

    const closeButton = document.createElement('button');
    closeButton.className = 'site-popup-close';
    closeButton.type = 'button';
    closeButton.setAttribute('aria-label', 'Cerrar aviso');
    closeButton.textContent = '×';
    closeButton.addEventListener('click', dismiss);

    const title = document.createElement('h2');
    title.className = 'site-popup-title';
    title.id = 'sitePopupTitle';
    title.textContent = 'NOTICIAS';

    const image = document.createElement('img');
    image.className = 'site-popup-image';
    image.src = configuration.image;
    image.alt = 'Aviso de Fundación DU';
    image.addEventListener('error', () => {
      console.error('No se pudo cargar la imagen del aviso emergente.');
      dismiss();
    }, { once: true });

    if (configuration.link) {
      const link = document.createElement('a');
      link.className = 'site-popup-link';
      link.href = configuration.link;
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      link.setAttribute('aria-label', 'Abrir información del aviso en una nueva pestaña');
      link.append(image);
      dialog.append(title, link);
    } else {
      dialog.append(title);
      dialog.append(image);
    }
    dialog.append(closeButton);
    overlay.append(dialog);
    overlay.addEventListener('click', event => {
      if (event.target === overlay) dismiss();
    });

    onKeydown = event => {
      if (event.key === 'Escape') {
        dismiss();
        return;
      }
      if (event.key === 'Tab') {
        const focusable = Array.from(dialog.querySelectorAll('a[href],button:not([disabled])'));
        if (focusable.length === 0) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    };
    document.addEventListener('keydown', onKeydown);
    document.body.append(overlay);
    document.body.style.overflow = 'hidden';
    closeButton.focus();

    if (configuration.displaySeconds > 0) {
      window.setTimeout(() => {
        if (overlay.isConnected) dismiss();
      }, configuration.displaySeconds * 1000);
    }
  }

  async function loadPopup() {
    try {
      const response = await fetch(popupUrl, { credentials: 'same-origin', cache: 'no-store' });
      if (!response.ok) throw new Error(`No se pudo consultar el aviso (${response.status}).`);
      const result = await response.json();
      const configuration = result.data;
      if (!configuration || !configuration.image) return;

      const sessionKey = `fdu-popup:${configuration.image}:${configuration.endsOn}`;
      try {
        if (sessionStorage.getItem(sessionKey) === 'shown') return;
        sessionStorage.setItem(sessionKey, 'shown');
      } catch (error) {
        console.warn('No se pudo guardar el estado de visualización del aviso en esta sesión.', error);
      }

      window.setTimeout(() => showPopup(configuration), 350);
    } catch (error) {
      console.error('No se pudo mostrar el aviso emergente.', error);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadPopup, { once: true });
  } else {
    loadPopup();
  }
})();
