(() => {
  const form = document.getElementById('congressForm');
  if (!form) return;
  const state = document.getElementById('congressDirtyState');
  const saveButton = document.getElementById('saveCongress');
  let dirty = false;
  let ready = false;

  function setDirty(value) {
    dirty = value;
    if (!state) return;
    state.textContent = value ? 'Tienes cambios sin guardar' : 'Sin cambios pendientes';
    state.classList.toggle('is-dirty', value);
  }

  // Los datos se cargan de forma asíncrona; los cambios hechos antes de ese momento no cuentan.
  setTimeout(() => { ready = true; }, 1500);
  const markDirty = () => { if (ready) setDirty(true); };
  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);
  form.addEventListener('click', event => {
    if (event.target.closest('.remove-button, #addFee, #addSpeaker, #addSponsor, #addCongressCountry')) markDirty();
  });
  form.querySelectorAll('.ql-editor').forEach(editor => editor.addEventListener('input', markDirty));
  new MutationObserver(mutations => {
    mutations.forEach(mutation => mutation.addedNodes.forEach(node => {
      if (node.nodeType === 1 && node.querySelector?.('.ql-editor')) {
        node.querySelectorAll('.ql-editor').forEach(editor => editor.addEventListener('input', markDirty));
      }
    }));
  }).observe(form, { childList: true, subtree: true });

  // Tras guardar con éxito (el botón vuelve a "Guardar cambios"/"Crear congreso"), se limpia el aviso.
  if (saveButton) {
    let wasSaving = false;
    new MutationObserver(() => {
      const saving = saveButton.disabled;
      if (wasSaving && !saving && !document.querySelector('.admin-message.error:not([hidden])')) setDirty(false);
      wasSaving = saving;
    }).observe(saveButton, { attributes: true, childList: true, characterData: true });
  }

  window.addEventListener('beforeunload', event => {
    if (!dirty) return;
    event.preventDefault();
    event.returnValue = '';
  });

  // Barra de secciones: resalta la sección visible
  const links = Array.from(document.querySelectorAll('#congressJump a'));
  if (!links.length || !('IntersectionObserver' in window)) return;
  const map = new Map();
  links.forEach(link => {
    const target = document.getElementById((link.getAttribute('href') || '').slice(1));
    if (target) map.set(target, link);
  });
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      links.forEach(link => link.classList.remove('is-active'));
      map.get(entry.target)?.classList.add('is-active');
    });
  }, { rootMargin: '-20% 0px -70% 0px' });
  map.forEach((_, target) => observer.observe(target));
})();
