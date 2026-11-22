// Panel: vista previa y compresión de la foto en el celular antes de subirla,
// confirmaciones y filtros que se envían solos.
(function () {
  'use strict';

  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.confirm(form.getAttribute('data-confirm'))) ev.preventDefault();
    });
  });

  // Evita doble envío (botones deshabilitados mientras se sube).
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (ev.defaultPrevented) return;
      setTimeout(function () {
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function (b) { b.disabled = true; });
      }, 0);
    });
  });

  var input = document.querySelector('[data-foto]');
  if (!input) return;
  var preview = document.querySelector('[data-preview]');
  var cta = document.querySelector('[data-photo-cta]');
  var estado = document.querySelector('[data-foto-estado]');
  var MAX = 1600;

  input.addEventListener('change', function () {
    var file = input.files && input.files[0];
    if (!file) return;
    preview.src = URL.createObjectURL(file);
    preview.hidden = false;
    cta.textContent = '📷 Cambiar foto';
    comprimir(file);
  });

  // Reduce la foto a 1600 px en JPG: sube más rápido con datos móviles
  // y convierte fotos HEIC de iPhone en un formato que el servidor entiende.
  function comprimir(file) {
    if (!window.DataTransfer || !HTMLCanvasElement.prototype.toBlob) return;
    var img = new Image();
    img.onload = function () {
      var escala = Math.min(1, MAX / Math.max(img.naturalWidth, img.naturalHeight));
      if (escala === 1 && /jpe?g|webp/i.test(file.type) && file.size < 1.5e6) return;
      var canvas = document.createElement('canvas');
      canvas.width = Math.round(img.naturalWidth * escala);
      canvas.height = Math.round(img.naturalHeight * escala);
      var ctx = canvas.getContext('2d');
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      canvas.toBlob(function (blob) {
        if (!blob) return;
        try {
          var dt = new DataTransfer();
          dt.items.add(new File([blob], 'foto.jpg', { type: 'image/jpeg' }));
          input.files = dt.files;
          if (estado) estado.textContent = 'Foto lista (' + Math.round(blob.size / 1024) + ' KB).';
        } catch (e) { /* si el navegador no lo permite, se sube la original */ }
      }, 'image/jpeg', 0.85);
    };
    img.src = preview.src;
  }
})();
