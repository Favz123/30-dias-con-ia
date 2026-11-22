// Menú móvil, animaciones al hacer scroll y filtros que se envían solos.
(function () {
  var toggle = document.querySelector('.menu-toggle');
  var menu = document.getElementById('menu');
  if (toggle && menu) {
    toggle.addEventListener('click', function () {
      var abierto = menu.classList.toggle('open');
      toggle.setAttribute('aria-expanded', String(abierto));
    });
    menu.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        menu.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  var items = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, { threshold: 0.08 });
    items.forEach(function (el) { io.observe(el); });
  } else {
    items.forEach(function (el) { el.classList.add('in'); });
  }

  document.querySelectorAll('[data-autosubmit]').forEach(function (sel) {
    sel.addEventListener('change', function () { sel.form.submit(); });
  });
})();

// Botón "Compartir": usa el menú de compartir del celular o copia el enlace.
(function () {
  document.querySelectorAll('[data-compartir]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var url = btn.getAttribute('data-url');
      var titulo = btn.getAttribute('data-titulo');
      if (navigator.share) {
        navigator.share({ title: titulo + ' · Punto Bonito', url: url }).catch(function () {});
        return;
      }
      if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function () {
          var antes = btn.textContent;
          btn.textContent = '✅ ¡Enlace copiado!';
          setTimeout(function () { btn.textContent = antes; }, 2200);
        });
      }
    });
  });
})();
