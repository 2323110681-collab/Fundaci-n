(function () {
  'use strict';

  // La CSP del proyecto no permite scripts en linea (script-src 'self'), asi que
  // el boton de copiar vive aqui y no como onclick en el HTML.
  const buttons = document.querySelectorAll('[data-share-copy]');

  buttons.forEach(button => {
    const labelNode = button.querySelector('[data-share-text]');
    const originalLabel = labelNode ? labelNode.textContent : '';

    function restoreLabel(delay) {
      window.setTimeout(() => {
        button.classList.remove('is-copied', 'is-error');
        button.disabled = false;
        if (labelNode) labelNode.textContent = originalLabel;
      }, delay);
    }

    button.addEventListener('click', async () => {
      const status = document.querySelector('[data-share-status]');
      const done = button.dataset.shareDone || originalLabel;
      const error = button.dataset.shareError || originalLabel;

      const url = window.location.href;

      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(url);
        } else {
          // Fallback para cuando no hay API de portapapeles (http local o
          // navegadores antiguos). Se usa un textarea temporal porque
          // execCommand ya no existe en algunos.
          const helper = document.createElement('textarea');
          helper.value = url;
          helper.setAttribute('readonly', '');
          helper.style.position = 'fixed';
          helper.style.opacity = '0';
          document.body.appendChild(helper);
          helper.select();
          const copied = document.execCommand('copy');
          document.body.removeChild(helper);
          if (!copied) throw new Error('copy failed');
        }

        button.classList.add('is-copied');
        button.disabled = true;
        if (labelNode) labelNode.textContent = done;
        if (status) status.textContent = '';
        restoreLabel(2200);
      } catch (error_) {
        button.classList.add('is-error');
        button.disabled = true;
        if (labelNode) labelNode.textContent = error;
        restoreLabel(2600);
      }
    });
  });
})();
