(() => {
  const maxImageSize = 5 * 1024 * 1024;
  const apiUrl = new URL('index.php?resource=congreso', window.location.href);
  const isNewCongress = new URLSearchParams(window.location.search).get('new') === '1';
  const congressId = new URLSearchParams(window.location.search).get('id') || '1';
  apiUrl.searchParams.set('id', congressId);
  const createUrl = new URL(apiUrl);
  createUrl.searchParams.set('view', 'create-save');
  const uploadUrl = new URL(apiUrl);
  uploadUrl.searchParams.set('view', 'upload-image');
  const authUrl = new URL('auth.php', window.location.href);
  const form = document.getElementById('congressForm');
  const message = document.getElementById('adminMessage');
  const feesList = document.getElementById('feesList');
  const speakersList = document.getElementById('speakersList');
  const sponsorsList = document.getElementById('sponsorsList');
  const congressCountryInput = document.getElementById('congressCountryInput');
  const congressCountriesList = document.getElementById('congressCountriesList');
  const certificationsEditor = new Quill(document.getElementById('certificationsEditor'), {
    theme: 'snow',
    placeholder: 'Escribe las certificaciones del congreso...',
    modules: { toolbar: [[{ header: [1, 2, 3, false] }], ['bold', 'italic', 'underline', 'link'], [{ list: 'ordered' }, { list: 'bullet' }], ['clean']] },
  });
  const saveButton = document.getElementById('saveCongress');
  const logoFileInput = document.getElementById('congressLogoFile');
  const logoPathInput = document.getElementById('congressLogoPath');
  const logoPreview = document.getElementById('congressLogoPreview');
  const logoFileName = document.getElementById('congressLogoFileName');
  const qrFileInput = document.getElementById('registrationQrFile');
  const qrPathInput = document.getElementById('registrationQrPath');
  const qrPreview = document.getElementById('registrationQrPreview');
  const removeQrButton = document.getElementById('removeRegistrationQr');
  const qrFileName = document.getElementById('registrationQrFileName');
  const yapeEnabledInput = document.getElementById('paymentYapeEnabled');
  const sidebarUserCard = document.getElementById('sidebarUserCard');
  const sidebarUserAvatar = document.getElementById('sidebarUserAvatar');
  const sidebarUsername = document.getElementById('sidebarUsername');
  const sidebarUserRole = document.getElementById('sidebarUserRole');
  const logoutButton = document.getElementById('btnLogout');
  const usersLink = document.getElementById('tab-usuarios');
  const pendingUploads = new Set();
  let csrfToken = '';
  const speakerCountries = [
    ['AR', 'Argentina'], ['BO', 'Bolivia'], ['BR', 'Brasil'], ['CL', 'Chile'],
    ['CN', 'China'], ['CO', 'Colombia'], ['CR', 'Costa Rica'], ['CU', 'Cuba'],
    ['EC', 'Ecuador'], ['SV', 'El Salvador'], ['ES', 'España'], ['US', 'Estados Unidos'],
    ['GT', 'Guatemala'], ['HN', 'Honduras'], ['MX', 'México'], ['NI', 'Nicaragua'],
    ['PA', 'Panamá'], ['PY', 'Paraguay'], ['PE', 'Perú'], ['PT', 'Portugal'],
    ['DO', 'República Dominicana'], ['UY', 'Uruguay'], ['VE', 'Venezuela'],
  ];

  function showMessage(text, type) {
    message.textContent = text;
    message.className = `admin-message ${type}`;
    message.hidden = false;
    message.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function makeField(labelText, value, className = '') {
    const label = document.createElement('label');
    if (className) label.className = className;
    label.append(document.createTextNode(labelText));
    const input = document.createElement('input');
    input.className = 'input-field';
    input.value = value || '';
    input.maxLength = 12000;
    label.append(input);
    return { label, input };
  }

  function addFee(fee = { audience: '', amount: '' }, prepend = false) {
    const row = document.createElement('div');
    row.className = 'fee-row';
    const audience = makeField('Público', fee.audience);
    const amount = makeField('Monto', fee.amount);
    audience.input.maxLength = 180;
    amount.input.maxLength = 80;
    const remove = document.createElement('button');
    remove.className = 'btn-secondary remove-button';
    remove.type = 'button';
    remove.textContent = 'Quitar';
    remove.addEventListener('click', () => row.remove());
    row.append(audience.label, amount.label, remove);
    feesList[prepend ? 'prepend' : 'append'](row);
    if (prepend) audience.input.focus({ preventScroll: true });
  }

  function addRichTextEditor(container, value, placeholder) {
    const editor = new Quill(container, {
      theme: 'snow',
      placeholder,
      modules: {
        toolbar: [[{ header: [1, 2, 3, false] }], ['bold', 'italic', 'underline', 'link'], [{ list: 'ordered' }, { list: 'bullet' }], ['clean']],
      },
    });
    if (value) {
      if (/<\/?[a-z][\s\S]*?>/i.test(value)) {
        editor.setContents(editor.clipboard.convert({ html: value }), 'silent');
      } else {
        editor.setText(value, 'silent');
      }
    }
    return editor;
  }

  function addSponsor(sponsor = {}, prepend = false) {
    const item = typeof sponsor === 'string' ? { name: sponsor, logo: '', description: '' } : sponsor;
    const card = document.createElement('article');
    card.className = 'sponsor-card';
    const header = document.createElement('div');
    header.className = 'sponsor-card-header';
    const title = document.createElement('h3');
    title.textContent = item.name || 'Nuevo auspiciador';
    const remove = document.createElement('button');
    remove.className = 'btn-secondary remove-button';
    remove.type = 'button';
    remove.textContent = 'Quitar auspiciador';
    remove.addEventListener('click', () => card.remove());
    header.append(title, remove);

    const grid = document.createElement('div');
    grid.className = 'sponsor-grid';
    const nameField = makeField('Nombre del auspiciador', item.name || '');
    nameField.input.maxLength = 180;
    nameField.input.addEventListener('input', () => { title.textContent = nameField.input.value || 'Nuevo auspiciador'; });
    grid.append(nameField.label);

    const logoField = document.createElement('div');
    logoField.className = 'wide';
    const logoLabel = document.createElement('span');
    logoLabel.className = 'edit-label';
    logoLabel.textContent = 'Logo del auspiciador';
    const preview = document.createElement('img');
    preview.className = 'sponsor-logo-preview';
    preview.alt = item.name ? `Logo de ${item.name}` : 'Vista previa del logo';
    preview.hidden = !item.logo;
    if (item.logo) preview.src = speakerPhotoUrl(item.logo);
    const hiddenLogo = document.createElement('input');
    hiddenLogo.type = 'hidden';
    hiddenLogo.value = item.logo || '';
    hiddenLogo.dataset.sponsorField = 'logo';
    const chooseLogo = document.createElement('label');
    chooseLogo.className = 'btn-secondary sponsor-logo-picker';
    chooseLogo.append(document.createTextNode('Elegir logo del equipo'));
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/jpeg,image/png,image/gif,image/webp';
    fileInput.setAttribute('aria-label', 'Elegir logo del auspiciador');
    chooseLogo.append(fileInput);
    const fileName = document.createElement('span');
    fileName.className = 'speaker-photo-hint';
    fileName.textContent = item.logo ? 'Logo actual' : 'JPG, PNG, GIF o WEBP · máximo 5 MB.';
    fileInput.addEventListener('change', () => {
      const file = fileInput.files?.[0];
      if (!file) return;
      if (!/^image\/(jpeg|png|gif|webp)$/.test(file.type) || file.size > maxImageSize) {
        fileInput.value = '';
        showMessage('El logo debe ser JPG, PNG, GIF o WEBP y no superar 5 MB.', 'error');
        return;
      }
      preview.src = URL.createObjectURL(file);
      preview.hidden = false;
      fileName.textContent = file.name;
      card.dataset.pendingLogo = 'selected';
    });
    logoField.append(logoLabel, preview, chooseLogo, fileName, hiddenLogo);
    grid.append(logoField);

    const descriptionLabel = document.createElement('div');
    descriptionLabel.className = 'wide sponsor-rich-editor';
    const descriptionCaption = document.createElement('span');
    descriptionCaption.className = 'edit-label';
    descriptionCaption.textContent = 'Descripción';
    const editorContainer = document.createElement('div');
    descriptionLabel.append(descriptionCaption, editorContainer);
    grid.append(descriptionLabel);
    card.descriptionQuill = addRichTextEditor(editorContainer, item.description || '', 'Describe al auspiciador...');
    card.append(header, grid);
    sponsorsList[prepend ? 'prepend' : 'append'](card);
    if (prepend) nameField.input.focus({ preventScroll: true });
  }

  function addCongressCountry(country) {
    const name = String(country || '').trim();
    if (!name) return;
    const alreadyAdded = Array.from(congressCountriesList.querySelectorAll('[data-country]'))
      .some(item => item.dataset.country.toLocaleLowerCase() === name.toLocaleLowerCase());
    if (alreadyAdded) return;

    const item = document.createElement('li');
    item.className = 'congress-country-admin-item';
    item.dataset.country = name;
    const label = document.createElement('span');
    label.textContent = name;
    const remove = document.createElement('button');
    remove.className = 'btn-secondary remove-button';
    remove.type = 'button';
    remove.textContent = 'Quitar';
    remove.setAttribute('aria-label', `Quitar ${name}`);
    remove.addEventListener('click', () => item.remove());
    item.append(label, remove);
    congressCountriesList.append(item);
  }

  function validateImage(file) {
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type) || file.size > maxImageSize) {
      throw new Error('La imagen debe ser JPG, PNG, GIF o WEBP y pesar máximo 5 MB.');
    }
  }

  function speakerPhotoUrl(path) {
    const value = String(path || '').trim();
    return /^[A-Za-z0-9_.-]+\.(?:jpe?g|png|gif|webp)$/i.test(value)
      ? `assets/images/congreso/${value}`
      : value;
  }

  function updateLogoPreview(path) {
    const value = String(path || '').trim();
    if (!value) {
      logoPreview.removeAttribute('src');
      logoPreview.hidden = true;
      return;
    }
    logoPreview.src = speakerPhotoUrl(value);
    logoPreview.hidden = false;
  }

  function updateQrPreview(path) {
    const value = String(path || '').trim();
    if (!value) {
      qrPreview.removeAttribute('src');
      qrPreview.hidden = true;
      removeQrButton.hidden = true;
      return;
    }
    qrPreview.src = speakerPhotoUrl(value);
    qrPreview.hidden = false;
    removeQrButton.hidden = false;
  }

  function clearRegistrationQr() {
    qrPathInput.value = '';
    qrFileInput.value = '';
    updateQrPreview('');
    qrFileName.textContent = 'QR eliminado. Guarda los cambios para confirmar.';
  }

  async function uploadImage(file) {
    validateImage(file);
    const data = new FormData();
    data.append('image', file);
    const response = await fetch(uploadUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-CSRF-Token': csrfToken },
      body: data,
    });
    const result = await response.json().catch(() => null);
    if (!response.ok || !result?.url) {
      if (response.status === 401 || response.status === 403) {
        throw new Error('Tu sesión expiró o no tiene permiso para subir imágenes. Vuelve a iniciar sesión.');
      }
      throw new Error(result?.error || `No se pudo subir la imagen (error ${response.status}).`);
    }
    return result.url;
  }

  function trackUpload(promise) {
    pendingUploads.add(promise);
    promise.finally(() => pendingUploads.delete(promise));
    return promise;
  }

  function addSpeaker(speaker = {}, prepend = false) {
    const card = document.createElement('article');
    card.className = 'speaker-card';
    card.dataset.pendingPhoto = '';

    const header = document.createElement('div');
    header.className = 'speaker-card-header';
    const title = document.createElement('h3');
    title.textContent = speaker.name || 'Nuevo ponente';
    const remove = document.createElement('button');
    remove.className = 'btn-secondary remove-button';
    remove.type = 'button';
    remove.textContent = 'Quitar ponente';
    remove.addEventListener('click', () => card.remove());
    header.append(title, remove);

    const grid = document.createElement('div');
    grid.className = 'speaker-grid';
    const controls = {};
    [
      ['Nombre completo', 'name'],
      ['Universidad o institución', 'institution'],
    ].forEach(([label, key]) => {
      const field = makeField(label, speaker[key] || '');
      field.input.dataset.speakerField = key;
      controls[key] = field.input;
      grid.append(field.label);
    });

    const countryLabel = document.createElement('label');
    countryLabel.className = 'speaker-country-field';
    countryLabel.append(document.createTextNode('País del ponente'));
    const countrySelect = document.createElement('select');
    countrySelect.className = 'input-field';
    countrySelect.dataset.speakerField = 'country';
    countrySelect.required = true;
    const countryPlaceholder = document.createElement('option');
    countryPlaceholder.value = '';
    countryPlaceholder.textContent = 'Seleccionar país';
    countrySelect.append(countryPlaceholder);
    speakerCountries.forEach(([code, name]) => {
      const option = document.createElement('option');
      option.value = name;
      option.textContent = `${String.fromCodePoint(127397 + code.charCodeAt(0))}${String.fromCodePoint(127397 + code.charCodeAt(1))} ${name}`;
      countrySelect.append(option);
    });
    const otherCountryOption = document.createElement('option');
    otherCountryOption.value = '__other__';
    otherCountryOption.textContent = 'Otro país (especificar)';
    countrySelect.append(otherCountryOption);
    const otherCountry = document.createElement('input');
    otherCountry.className = 'input-field speaker-other-country';
    otherCountry.maxLength = 120;
    otherCountry.placeholder = 'Escribe el nombre del país';
    otherCountry.setAttribute('aria-label', 'Nombre de otro país');
    otherCountry.value = speakerCountries.some(([, name]) => name === speaker.country) ? '' : (speaker.country || '');
    otherCountry.hidden = !otherCountry.value;
    if (otherCountry.value) countrySelect.value = '__other__';
    else countrySelect.value = speaker.country || '';
    const flagPreview = document.createElement('span');
    flagPreview.className = 'speaker-country-flag';
    const setCountryFlag = () => {
      const selected = speakerCountries.find(([, name]) => name === countrySelect.value);
      flagPreview.textContent = selected
        ? String.fromCodePoint(127397 + selected[0].charCodeAt(0)) + String.fromCodePoint(127397 + selected[0].charCodeAt(1))
        : '';
      otherCountry.hidden = countrySelect.value !== '__other__';
      otherCountry.required = countrySelect.value === '__other__';
    };
    countrySelect.addEventListener('change', setCountryFlag);
    setCountryFlag();
    countryLabel.append(countrySelect, otherCountry, flagPreview);
    controls.country = countrySelect;
    controls.otherCountry = otherCountry;
    grid.append(countryLabel);

    const photoLabel = document.createElement('div');
    photoLabel.className = 'wide photo-field';
    photoLabel.append(document.createTextNode('Fotografía del ponente'));
    const photoPreview = document.createElement('img');
    photoPreview.className = 'speaker-photo-preview';
    photoPreview.alt = 'Vista previa de la fotografía';
    photoPreview.hidden = !speaker.photo;
    if (speaker.photo) photoPreview.src = speakerPhotoUrl(speaker.photo);
    const photoPath = document.createElement('input');
    photoPath.className = 'input-field speaker-photo-path';
    photoPath.value = speaker.photo || '';
    photoPath.maxLength = 500;
    photoPath.dataset.speakerField = 'photo';
    const photoFile = document.createElement('input');
    photoFile.type = 'file';
    photoFile.accept = 'image/jpeg,image/png,image/gif,image/webp';
    photoFile.className = 'input-field';
    photoFile.setAttribute('aria-label', 'Subir fotografía JPG, PNG, GIF o WEBP, máximo 5 MB');
    const photoHint = document.createElement('span');
    photoHint.className = 'speaker-photo-hint';
    photoHint.textContent = 'JPG, PNG, GIF o WEBP · máximo 5 MB.';
    photoFile.addEventListener('change', () => {
      const file = photoFile.files?.[0];
      if (!file) return;
      try {
        validateImage(file);
        card.dataset.pendingPhoto = 'selected';
        photoPreview.src = URL.createObjectURL(file);
        photoPreview.hidden = false;
      } catch (error) {
        photoFile.value = '';
        showMessage(error.message, 'error');
      }
    });
    photoPath.addEventListener('input', () => {
      card.dataset.pendingPhoto = '';
      if (photoPath.value.trim()) {
        photoPreview.src = speakerPhotoUrl(photoPath.value);
        photoPreview.hidden = false;
      } else {
        photoPreview.removeAttribute('src');
        photoPreview.hidden = true;
      }
    });
    const photoActions = document.createElement('div');
    photoActions.className = 'speaker-photo-actions';
    const choosePhoto = document.createElement('label');
    choosePhoto.className = 'btn-secondary speaker-photo-picker';
    choosePhoto.append(document.createTextNode('Elegir foto del equipo'));
    photoFile.tabIndex = 0;
    photoFile.setAttribute('aria-label', 'Elegir foto del equipo');
    choosePhoto.append(photoFile);
    const photoName = document.createElement('span');
    photoName.className = 'speaker-photo-hint';
    photoName.textContent = speaker.photo ? 'Foto actual' : 'No se ha elegido una foto.';
    photoActions.append(choosePhoto, photoName);
    photoFile.addEventListener('change', () => {
      photoName.textContent = photoFile.files?.[0]?.name || 'No se ha elegido una foto.';
    });
    const photoPathLabel = document.createElement('span');
    photoPathLabel.className = 'speaker-photo-hint';
    photoPathLabel.textContent = 'O pega una URL o ruta existente:';
    photoLabel.append(photoPreview, photoActions, photoPathLabel, photoPath, photoHint);
    photoPath.dataset.speakerField = 'photo';
    grid.append(photoLabel);

    const biographyLabel = document.createElement('div');
    biographyLabel.className = 'wide speaker-biography-editor';
    const biographyCaption = document.createElement('span');
    biographyCaption.className = 'edit-label speaker-bio-label';
    biographyCaption.textContent = 'Biografía';
    const biographyEditor = document.createElement('div');
    biographyEditor.className = 'speaker-biography-quill';
    biographyLabel.append(biographyCaption, biographyEditor);
    grid.append(biographyLabel);
    const biographyQuill = new Quill(biographyEditor, {
      theme: 'snow',
      modules: {
        toolbar: [
          [{ header: [1, 2, 3, false] }],
          ['bold', 'italic', 'underline', 'link', 'image'],
          [{ list: 'ordered' }, { list: 'bullet' }],
          ['clean'],
        ],
      },
    });
    card.biographyQuill = biographyQuill;
    if (speaker.biography) {
      if (/<\/?[a-z][\s\S]*?>/i.test(speaker.biography)) {
        biographyQuill.setContents(biographyQuill.clipboard.convert({ html: speaker.biography }), 'silent');
      } else {
        biographyQuill.setText(speaker.biography, 'silent');
      }
    }
    const toolbar = biographyEditor.previousElementSibling;
    if (toolbar?.querySelector('.ql-image')) {
      const imagePicker = document.createElement('input');
      imagePicker.type = 'file';
      imagePicker.accept = 'image/jpeg,image/png,image/gif,image/webp';
      imagePicker.hidden = true;
      imagePicker.addEventListener('change', () => {
        const file = imagePicker.files?.[0];
        if (!file) return;
        const job = uploadImage(file).then(url => {
          const cursor = biographyQuill.getSelection(true);
          biographyQuill.insertEmbed(cursor ? cursor.index : biographyQuill.getLength(), 'image', url, 'user');
        }).catch(error => showMessage(error.message, 'error')).finally(() => {
          imagePicker.value = '';
        });
        trackUpload(job);
      });
      biographyLabel.append(imagePicker);
      biographyQuill.getModule('toolbar').addHandler('image', () => {
        imagePicker.click();
      });
    }

    const links = document.createElement('label');
    links.className = 'wide';
    links.append(document.createTextNode('Enlaces de perfil (uno por línea)'));
    const linksInput = document.createElement('textarea');
    linksInput.className = 'input-field';
    linksInput.rows = 3;
    linksInput.value = Array.isArray(speaker.links) ? speaker.links.join('\n') : '';
    linksInput.dataset.speakerField = 'links';
    links.append(linksInput);
    grid.append(links);

    const nameInput = controls.name;
    nameInput.addEventListener('input', () => { title.textContent = nameInput.value || 'Nuevo ponente'; });
    card.append(header, grid);
    speakersList[prepend ? 'prepend' : 'append'](card);
    if (prepend) nameInput.focus({ preventScroll: true });
  }

  function setField(name, value) {
    const field = form.elements.namedItem(name);
    if (field) field.value = value ?? '';
  }

  function populate(content) {
    ['title', 'subtitle', 'summary', 'intro', 'logo', 'date', 'time', 'modality', 'venue', 'registration_url', 'registration_qr', 'contact_email'].forEach(key => setField(key, content[key]));
    updateLogoPreview(content.logo);
    logoFileName.textContent = content.logo ? 'Logo actual; elige otro archivo para reemplazarlo.' : 'JPG, PNG, GIF o WEBP · máximo 5 MB.';
    updateQrPreview(content.registration_qr);
    qrFileName.textContent = content.registration_qr ? 'QR actual; elige otro archivo para reemplazarlo.' : 'JPG, PNG, GIF o WEBP · máximo 5 MB.';
    Object.entries(content.payment || {}).forEach(([key, value]) => {
      if (key === 'yape_enabled') return;
      setField(`payment.${key}`, value);
    });
    if (yapeEnabledInput) {
      yapeEnabledInput.checked = content.payment?.yape_enabled !== false;
    }
    certificationsEditor.setText('', 'silent');
    const certifications = content.certifications || [];
    if (certifications.length === 1 && /<\/?[a-z][\s\S]*?>/i.test(certifications[0])) {
      certificationsEditor.setContents(certificationsEditor.clipboard.convert({ html: certifications[0] }), 'silent');
    } else if (certifications.length) {
      certificationsEditor.setContents(certificationsEditor.clipboard.convert({
        html: certifications.map(item => `<p>${String(item).replace(/[&<>"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]))}</p>`).join(''),
      }), 'silent');
    }
    feesList.replaceChildren();
    (content.fees || []).forEach(addFee);
    sponsorsList.replaceChildren();
    (content.sponsors || []).forEach(addSponsor);
    congressCountriesList.replaceChildren();
    (content.countries || []).forEach(addCongressCountry);
    speakersList.replaceChildren();
    (content.speakers || []).forEach(addSpeaker);
  }

  async function collect() {
    const value = Object.fromEntries(new FormData(form).entries());
    const selectedLogo = logoFileInput.files?.[0];
    if (selectedLogo) {
      value.logo = await uploadImage(selectedLogo);
      logoPathInput.value = value.logo;
      updateLogoPreview(value.logo);
    }
    const selectedQr = qrFileInput.files?.[0];
    if (selectedQr) {
      value.registration_qr = await uploadImage(selectedQr);
      qrPathInput.value = value.registration_qr;
      updateQrPreview(value.registration_qr);
    }
    const sponsorCards = Array.from(sponsorsList.querySelectorAll('.sponsor-card'));
    for (const card of sponsorCards) {
      if (card.dataset.pendingLogo === 'selected') {
        const file = card.querySelector('input[type="file"]')?.files?.[0];
        if (!file) throw new Error('Selecciona nuevamente el logo de un auspiciador.');
        card.querySelector('[data-sponsor-field="logo"]').value = await uploadImage(file);
        card.dataset.pendingLogo = '';
      }
    }
    const cards = Array.from(speakersList.querySelectorAll('.speaker-card'));
    for (const card of cards) {
      if (card.dataset.pendingPhoto === 'selected') {
        const file = card.querySelector('input[type="file"]')?.files?.[0];
        if (!file) throw new Error('Selecciona nuevamente la fotografía de un ponente.');
        const photoPath = card.querySelector('[data-speaker-field="photo"]');
        photoPath.value = await uploadImage(file);
        card.dataset.pendingPhoto = '';
      }
    }

    const payment = {};
    ['bank', 'holder', 'account', 'cci', 'wallet_name', 'yape', 'yape_holder', 'note'].forEach(key => {
      payment[key] = form.elements.namedItem(`payment.${key}`).value.trim();
    });
    payment.yape_enabled = yapeEnabledInput ? yapeEnabledInput.checked : true;
    const fees = Array.from(feesList.querySelectorAll('.fee-row')).map(row => {
      const inputs = row.querySelectorAll('input');
      return { audience: inputs[0].value.trim(), amount: inputs[1].value.trim() };
    }).filter(fee => fee.audience || fee.amount);
    const speakers = cards.map(card => {
      const speaker = {};
      card.querySelectorAll('[data-speaker-field]').forEach(input => {
        const key = input.dataset.speakerField;
        if (key === 'country' && input.value === '__other__') {
          speaker.country = card.querySelector('.speaker-other-country').value.trim();
          return;
        }
        speaker[key] = key === 'links'
          ? input.value.split(/\r?\n/).map(link => link.trim()).filter(Boolean)
          : input.value.trim();
      });
      speaker.biography = card.biographyQuill.root.innerHTML;
      return speaker;
    });
    const sponsors = sponsorCards.map(card => ({
      name: card.querySelector('input:not([type="hidden"])').value.trim(),
      logo: card.querySelector('[data-sponsor-field="logo"]').value.trim(),
      description: card.descriptionQuill.root.innerHTML,
    })).filter(sponsor => sponsor.name || sponsor.logo || sponsor.description.replace(/<[^>]*>/g, '').trim());
    return {
      title: value.title.trim(),
      subtitle: value.subtitle.trim(),
      summary: value.summary.trim(),
      intro: value.intro.trim(),
      logo: value.logo.trim(),
      date: value.date.trim(),
      time: value.time.trim(),
      modality: value.modality.trim(),
      venue: value.venue.trim(),
      registration_url: value.registration_url.trim(),
      registration_qr: (value.registration_qr || qrPathInput.value || '').trim(),
      contact_email: value.contact_email.trim(),
      sponsors,
      countries: Array.from(congressCountriesList.querySelectorAll('[data-country]'))
        .map(item => item.dataset.country),
      fees,
      certifications: certificationsEditor.getText().trim() ? [certificationsEditor.root.innerHTML] : [],
      payment,
      speakers,
    };
  }

  async function initialize() {
    try {
      const requests = [fetch(authUrl, { credentials: 'same-origin', cache: 'no-store' })];
      if (!isNewCongress) requests.push(fetch(apiUrl, { credentials: 'same-origin', cache: 'no-store' }));
      const [userResponse, contentResponse] = await Promise.all(requests);
      const userResult = await userResponse.json();
      if (!userResponse.ok || !userResult.user || !userResult.csrf_token) {
        window.location.href = 'login';
        return;
      }
      csrfToken = userResult.csrf_token;
      updateSidebarUser(userResult.user);
      if (isNewCongress) {
        populate({
          title: '',
          subtitle: '',
          summary: 'Es un espacio internacional de encuentro académico y profesional que reunirá a expertos, investigadores y estudiantes para compartir conocimientos, experiencias y avances en ciencia de datos e inteligencia artificial, impulsando la innovación y la colaboración global.',
          intro: '',
          logo: '',
          date: '',
          time: '',
          modality: '',
          venue: '',
          registration_url: '',
          registration_qr: '',
          contact_email: '',
          payment: {},
          certifications: [],
          sponsors: [],
          fees: [],
          speakers: [],
        });
        document.querySelector('.page-header h1').textContent = 'Presentación del Congreso';
        document.title = 'Presentación del Congreso - Panel de administración';
        saveButton.textContent = 'Crear congreso';
        return;
      }
      const contentResult = await contentResponse.json();
      if (!contentResponse.ok || !contentResult.data?.[0]) {
        throw new Error(contentResult.error || 'No se pudo cargar la información del congreso.');
      }
      populate(contentResult.data[0]);
    } catch (error) {
      showMessage(error.message || 'No se pudo cargar la información.', 'error');
    }
  }

  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    saveButton.disabled = true;
    saveButton.textContent = 'Guardando…';
    try {
      await Promise.all(Array.from(pendingUploads));
      const payload = await collect();
      const response = await fetch(isNewCongress ? createUrl : apiUrl, {
        method: isNewCongress ? 'POST' : 'PUT',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify(payload),
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || `Error ${response.status}`);
      if (isNewCongress && result.data?.id) {
        window.location.href = `admin-congreso?id=${encodeURIComponent(result.data.id)}`;
        return;
      }
      logoFileInput.value = '';
      logoFileName.textContent = 'Logo guardado. Elige otro archivo para reemplazarlo.';
      if (window.Swal && typeof window.Swal.fire === 'function') {
        const isDark = document.documentElement.classList.contains('admin-dark');
        await window.Swal.fire({
          title: '¡Cambios exitosos!',
          text: 'El congreso se actualizó correctamente.',
          icon: 'success',
          confirmButtonText: 'Aceptar',
          confirmButtonColor: '#d9a20b',
          background: isDark ? '#17232d' : '#ffffff',
          color: isDark ? '#e8eef0' : '#1f2937',
        });
      } else {
        console.error('SweetAlert2 no está disponible para confirmar la actualización del congreso.');
        showMessage('Los cambios se guardaron, pero no se pudo mostrar la confirmación.', 'success');
      }
    } catch (error) {
      showMessage(error.message || 'No se pudieron guardar los cambios.', 'error');
    } finally {
      saveButton.disabled = false;
      saveButton.textContent = 'Guardar cambios';
    }
  });

  document.getElementById('addFee').addEventListener('click', () => addFee(undefined, true));
  document.getElementById('addSpeaker').addEventListener('click', () => addSpeaker({}, true));
  document.getElementById('addSponsor').addEventListener('click', () => addSponsor({}, true));
  document.getElementById('addCongressCountry').addEventListener('click', () => {
    addCongressCountry(congressCountryInput.value);
    congressCountryInput.value = '';
    congressCountryInput.focus();
  });
  congressCountryInput.addEventListener('keydown', event => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    document.getElementById('addCongressCountry').click();
  });
  function updateSidebarUser(user) {
    sidebarUserCard.classList.remove('hidden');
    const username = String(user.username || '');
    const words = username.trim().split(/\s+/).filter(Boolean);
    const initials = words.length > 1 ? words.slice(0, 2).map(word => word[0]).join('') : (words[0] || '').slice(0, 2);
    sidebarUsername.textContent = username;
    sidebarUserRole.textContent = user.role === 'admin' ? 'Administrador' : (user.role || 'Usuario');
    sidebarUserAvatar.textContent = initials.toUpperCase();
    usersLink.classList.toggle('hidden', user.role !== 'admin');
  }

  logoutButton.addEventListener('click', async () => {
    logoutButton.disabled = true;
    try {
      const response = await fetch(new URL('auth.php?action=logout', window.location.href), {
        credentials: 'same-origin',
        headers: { 'X-CSRF-Token': csrfToken },
      });
      if (!response.ok) throw new Error(`No se pudo cerrar sesión (error ${response.status}).`);
      window.location.href = 'login';
    } catch (error) {
      showMessage(error.message || 'No se pudo cerrar sesión.', 'error');
      logoutButton.disabled = false;
    }
  });
  logoFileInput.addEventListener('change', () => {
    const file = logoFileInput.files?.[0];
    if (!file) return;
    try {
      validateImage(file);
      logoFileName.textContent = file.name;
      logoPreview.src = URL.createObjectURL(file);
      logoPreview.hidden = false;
    } catch (error) {
      logoFileInput.value = '';
      logoFileName.textContent = 'JPG, PNG, GIF o WEBP · máximo 5 MB.';
      showMessage(error.message, 'error');
    }
  });

  qrFileInput.addEventListener('change', () => {
    const file = qrFileInput.files?.[0];
    if (!file) return;
    try {
      validateImage(file);
      qrFileName.textContent = file.name;
      qrPreview.src = URL.createObjectURL(file);
      qrPreview.hidden = false;
      removeQrButton.hidden = false;
    } catch (error) {
      qrFileInput.value = '';
      qrFileName.textContent = 'JPG, PNG, GIF o WEBP · máximo 5 MB.';
      showMessage(error.message, 'error');
    }
  });
  removeQrButton.addEventListener('click', clearRegistrationQr);
  const sidebarToggle = document.getElementById('adminSidebarToggle');
  const backdrop = document.querySelector('[data-sidebar-close]');
  function setSidebarOpen(isOpen) {
    document.body.classList.toggle('admin-sidebar-open', isOpen);
    sidebarToggle?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    if (backdrop) backdrop.hidden = !isOpen;
  }
  sidebarToggle?.addEventListener('click', () => {
    setSidebarOpen(!document.body.classList.contains('admin-sidebar-open'));
  });
  backdrop?.addEventListener('click', () => setSidebarOpen(false));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') setSidebarOpen(false);
  });
  document.querySelectorAll('.congress-admin-workspace .admin-sidebar .tab-button').forEach(link => {
    link.addEventListener('click', () => setSidebarOpen(false));
  });

  initialize();
})();
