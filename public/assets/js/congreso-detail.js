(() => {
  // Botones "Copiar" de los medios de pago
  document.querySelectorAll('.congress-copy').forEach(button => {
    button.addEventListener('click', async () => {
      const value = button.dataset.copy || '';
      if (!value) return;
      const original = button.textContent;
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(value);
        } else {
          const helper = document.createElement('textarea');
          helper.value = value;
          helper.setAttribute('readonly', '');
          helper.style.position = 'fixed';
          helper.style.opacity = '0';
          document.body.appendChild(helper);
          helper.select();
          document.execCommand('copy');
          helper.remove();
        }
        button.textContent = '¡Copiado!';
        button.classList.add('is-copied');
      } catch (error) {
        button.textContent = 'No se pudo copiar';
      }
      setTimeout(() => {
        button.textContent = original;
        button.classList.remove('is-copied');
      }, 1800);
    });
  });

  // Resalta en la barra de secciones la que se está viendo
  const links = Array.from(document.querySelectorAll('.congress-subnav a'));
  if (!links.length || !('IntersectionObserver' in window)) return;
  const byId = new Map();
  links.forEach(link => {
    const id = (link.getAttribute('href') || '').split('#')[1];
    const target = id ? document.getElementById(id) : null;
    if (target) byId.set(target, link);
  });
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      links.forEach(link => link.classList.remove('is-active'));
      byId.get(entry.target)?.classList.add('is-active');
    });
  }, { rootMargin: '-30% 0px -60% 0px' });
  byId.forEach((_, target) => observer.observe(target));
})();
