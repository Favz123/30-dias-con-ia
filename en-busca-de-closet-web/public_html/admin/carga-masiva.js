// Carga masiva: elegir varias fotos, llenar datos (o leerlos del nombre del archivo)
// y subirlas una por una, comprimidas, con barra de progreso.
(function () {
  'use strict';

  var raiz = document.getElementById('carga');
  if (!raiz) return;
  var CSRF = raiz.getAttribute('data-csrf');
  var MAX_LOTE = 40;
  var MAX_LADO = 1600;
  var TALLAS = ['XCH', 'CH', 'M', 'G', 'XG'];
  var ALIAS = { XS: 'XCH', ECH: 'XCH', S: 'CH', L: 'G', XL: 'XG' };
  var MARCAS = (window.CLOSET_MARCAS || []).slice().sort(function (a, b) { return b.length - a.length; });

  var input = raiz.querySelector('[data-fotos]');
  var lote = raiz.querySelector('[data-lote]');
  var plantilla = raiz.querySelector('[data-plantilla]');
  var pasoTodas = raiz.querySelector('[data-paso="todas"]');
  var barra = raiz.querySelector('[data-barra]');
  var progreso = raiz.querySelector('[data-progreso]');
  var resumen = raiz.querySelector('[data-resumen]');
  var btnSubir = raiz.querySelector('[data-subir]');
  var items = [];
  var subiendo = false;

  /* ---------- Leer datos del nombre del archivo ---------- */
  // "CH - Short Nike negro - 250.jpg" → talla CH, marca Nike, nombre "Short Nike negro", precio 250
  function analizar(nombreArchivo) {
    var base = nombreArchivo.replace(/\.[^.]+$/, '').replace(/_+/g, ' ').trim();
    if (/^(img|dsc|pxl|photo|foto|image|imagen|screenshot|captura|whatsapp|wa)[\s-]*\d/i.test(base) || /^[\d\s-]+$/.test(base)) {
      return {};
    }
    base = base.replace(/^(XCH|CH|M|G|XG|XS)-/i, '$1 ').replace(/-\$?(\d{2,6})$/, ' $1');
    var tokens = base.split(/\s+/).filter(function (t) { return t && t !== '-' && t !== '|'; });
    var r = {};
    var resto = [];
    tokens.forEach(function (t) {
      var limpio = t.replace(/[,;]$/, '');
      var may = limpio.toUpperCase();
      if (!r.talla && TALLAS.indexOf(may) !== -1) { r.talla = may; return; }
      if (!r.talla && ALIAS[may]) { r.talla = ALIAS[may]; return; }
      var precio = limpio.match(/^\$?(\d{2,6})(?:\.00)?$/);
      if (precio) { r.precio = precio[1]; return; }
      resto.push(t);
    });
    var nombre = resto.join(' ').replace(/\s+-\s*$/, '').trim();
    if (nombre) r.nombre = nombre.charAt(0).toUpperCase() + nombre.slice(1);
    var minus = ' ' + nombre.toLowerCase() + ' ';
    for (var i = 0; i < MARCAS.length; i++) {
      if (minus.indexOf(' ' + MARCAS[i].toLowerCase() + ' ') !== -1) { r.marca = MARCAS[i]; break; }
    }
    return r;
  }

  /* ---------- Agregar fotos ---------- */
  input.addEventListener('change', function () {
    var archivos = Array.prototype.slice.call(input.files || []);
    input.value = '';
    var pendientes = items.filter(function (it) { return !it.listo; }).length;
    var lugar = MAX_LOTE - pendientes;
    if (archivos.length > lugar) {
      alert('Por tanda se pueden subir hasta ' + MAX_LOTE + ' fotos. Se agregaron las primeras ' + Math.max(lugar, 0) + '.');
      archivos = archivos.slice(0, Math.max(lugar, 0));
    }
    archivos.forEach(agregar);
    actualizar();
  });

  function agregar(file) {
    var el = plantilla.content.firstElementChild.cloneNode(true);
    var it = { file: file, el: el, talla: '', listo: false };
    var mini = el.querySelector('[data-mini]');
    encolarMiniatura(file, mini);

    var datos = analizar(file.name);
    ['nombre', 'marca', 'precio'].forEach(function (c) {
      if (datos[c]) el.querySelector('[data-campo="' + c + '"]').value = datos[c];
    });
    if (datos.talla) elegirTalla(it, datos.talla);

    el.querySelectorAll('[data-talla]').forEach(function (b) {
      b.addEventListener('click', function () { elegirTalla(it, b.getAttribute('data-talla')); actualizar(); });
    });
    el.querySelectorAll('[data-campo]').forEach(function (inp) {
      inp.addEventListener('input', function () { el.classList.remove('falta'); actualizar(); });
    });
    el.querySelector('[data-quitar]').addEventListener('click', function () {
      if (subiendo) return;
      el.remove();
      items.splice(items.indexOf(it), 1);
      actualizar();
    });
    items.push(it);
    lote.appendChild(el);
  }

  // Miniaturas pequeñas, una a la vez, para no saturar la memoria del celular.
  var colaMini = [];
  var generando = false;
  function encolarMiniatura(file, img) {
    colaMini.push([file, img]);
    if (!generando) siguienteMiniatura();
  }
  function siguienteMiniatura() {
    var t = colaMini.shift();
    if (!t) { generando = false; return; }
    generando = true;
    var url = URL.createObjectURL(t[0]);
    var img = new Image();
    img.onload = function () {
      var lado = 192;
      var escala = Math.max(lado / img.naturalWidth, lado / img.naturalHeight);
      var c = document.createElement('canvas');
      c.width = lado; c.height = lado;
      var w = img.naturalWidth * escala, h = img.naturalHeight * escala;
      c.getContext('2d').drawImage(img, (lado - w) / 2, (lado - h) / 2, w, h);
      URL.revokeObjectURL(url);
      t[1].src = c.toDataURL('image/jpeg', 0.7);
      setTimeout(siguienteMiniatura, 0);
    };
    img.onerror = function () { URL.revokeObjectURL(url); setTimeout(siguienteMiniatura, 0); };
    img.src = url;
  }

  function elegirTalla(it, talla) {
    it.talla = talla;
    it.el.classList.remove('falta');
    it.el.querySelectorAll('[data-talla]').forEach(function (b) {
      b.setAttribute('aria-pressed', String(b.getAttribute('data-talla') === talla));
    });
  }

  /* ---------- Aplicar a todas ---------- */
  raiz.querySelector('[data-aplicar]').addEventListener('click', function () {
    var talla = (raiz.querySelector('input[name="todas_talla"]:checked') || {}).value;
    var valores = {};
    raiz.querySelectorAll('[data-todas]').forEach(function (inp) {
      if (inp.value.trim()) valores[inp.getAttribute('data-todas')] = inp.value.trim();
    });
    items.forEach(function (it) {
      if (it.listo) return;
      if (talla && !it.talla) elegirTalla(it, talla);
      Object.keys(valores).forEach(function (c) {
        var campo = it.el.querySelector('[data-campo="' + c + '"]');
        if (campo && !campo.value.trim()) campo.value = valores[c];
      });
    });
    actualizar();
  });

  /* ---------- Estado general ---------- */
  function actualizar() {
    var pendientes = items.filter(function (it) { return !it.listo; });
    var hechos = items.length - pendientes.length;
    var hay = items.length > 0;
    pasoTodas.hidden = !pendientes.length;
    barra.hidden = !hay;
    progreso.style.width = hay ? Math.round(hechos / items.length * 100) + '%' : '0';
    var completas = pendientes.filter(completa).length;
    if (!pendientes.length && hay) {
      resumen.innerHTML = '✅ ¡Listo! Se subieron ' + hechos + ' prendas. <a href="/admin/">Ver mis prendas</a>';
      btnSubir.hidden = true;
    } else {
      resumen.textContent = (hechos ? hechos + ' subidas · ' : '') + pendientes.length + ' por subir' +
        (completas < pendientes.length ? ' · faltan datos en ' + (pendientes.length - completas) : '');
      btnSubir.hidden = false;
      btnSubir.disabled = subiendo || !pendientes.length;
      btnSubir.textContent = subiendo ? 'Subiendo…' : 'Subir ' + pendientes.length + (pendientes.length === 1 ? ' prenda' : ' prendas');
    }
  }

  function valor(it, c) { return it.el.querySelector('[data-campo="' + c + '"]').value.trim(); }
  function completa(it) { return it.talla && valor(it, 'nombre'); }

  /* ---------- Subir ---------- */
  btnSubir.addEventListener('click', async function () {
    var pendientes = items.filter(function (it) { return !it.listo; });
    var incompletas = pendientes.filter(function (it) { return !completa(it); });
    if (incompletas.length) {
      incompletas.forEach(function (it) { it.el.classList.add('falta'); estado(it, 'Falta la talla o el nombre.', 'mal'); });
      incompletas[0].el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }
    subiendo = true;
    actualizar();
    for (var i = 0; i < pendientes.length; i++) {
      await subirUna(pendientes[i]);
      actualizar();
    }
    subiendo = false;
    actualizar();
  });

  window.addEventListener('beforeunload', function (ev) {
    if (subiendo || items.some(function (it) { return !it.listo; })) { ev.preventDefault(); ev.returnValue = ''; }
  });

  async function subirUna(it) {
    estado(it, 'Subiendo…', '');
    it.el.classList.add('trabajando');
    var datos = new FormData();
    datos.append('csrf', CSRF);
    datos.append('talla', it.talla);
    ['nombre', 'marca', 'precio', 'detalle'].forEach(function (c) { datos.append(c, valor(it, c)); });
    try {
      var foto = await comprimir(it.file);
      datos.append('foto', foto, 'foto.jpg');
      var r = await fetch('/admin/subir.php', { method: 'POST', body: datos, credentials: 'same-origin' });
      var json = null;
      try { json = await r.json(); } catch (e) { /* respuesta no JSON */ }
      if (r.ok && json && json.ok) {
        it.listo = true;
        it.el.classList.add('listo');
        it.el.querySelectorAll('input, button').forEach(function (x) { x.disabled = true; });
        estado(it, '✓ Subida como ' + json.codigo, 'bien');
      } else {
        estado(it, (json && json.error) || 'No se pudo subir (error ' + r.status + '). Toca “Subir” para reintentar.', 'mal');
      }
    } catch (e) {
      estado(it, 'Sin conexión. Revisa tu internet y toca “Subir” para reintentar.', 'mal');
    }
    it.el.classList.remove('trabajando');
  }

  function estado(it, texto, tipo) {
    var p = it.el.querySelector('[data-estado]');
    p.textContent = texto;
    p.className = 'lote-estado' + (tipo ? ' ' + tipo : '');
  }

  // Reduce a 1600 px en JPG (también convierte HEIC de iPhone). Si falla, manda la original.
  function comprimir(file) {
    return new Promise(function (resolve) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () {
        var escala = Math.min(1, MAX_LADO / Math.max(img.naturalWidth, img.naturalHeight));
        var canvas = document.createElement('canvas');
        canvas.width = Math.round(img.naturalWidth * escala);
        canvas.height = Math.round(img.naturalHeight * escala);
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        URL.revokeObjectURL(url);
        canvas.toBlob(function (blob) { resolve(blob || file); }, 'image/jpeg', 0.85);
      };
      img.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
      img.src = url;
    });
  }
})();
