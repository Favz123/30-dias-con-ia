<?php $waGeneral = whatsapp_link('¡Hola! Vi su catálogo en línea y tengo una duda.'); ?>
</main>

<footer class="site-footer">
  <svg class="wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true" style="background:var(--lila-50)"><path d="M0 30 C 240 0 480 60 720 30 S 1200 0 1440 30 V60 H0Z"/></svg>
  <div class="wrap foot">
    <div>
      <a class="brand" href="/"><img src="/assets/img/logo-64.png" width="64" height="64" alt="Logo de En Busca de Closet, tienda de ropa en Campeche" loading="lazy"><span>En Busca de Closet</span></a>
      <p>Ropa de segunda mano con estilo · Campeche, México</p>
    </div>
    <div class="foot-links">
      <a href="<?= e($waGeneral) ?>" target="_blank" rel="noopener"><svg class="ico" aria-hidden="true"><use href="#i-wa"/></svg> WhatsApp</a>
      <a href="https://www.instagram.com/enbuscadecloset/" target="_blank" rel="noopener"><svg class="ico" aria-hidden="true"><use href="#i-ig"/></svg> @enbuscadecloset</a>
    </div>
  </div>
  <p class="copy">© <?= date('Y') ?> En Busca de Closet. Todos los derechos reservados.</p>
  <p class="copy credit">Sitio web creado por <a href="https://favzstudio.com" target="_blank" rel="noopener">Favzstudio.com</a></p>
</footer>

<a class="wa-float" href="<?= e($waGeneral) ?>" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp">
  <svg class="ico" aria-hidden="true"><use href="#i-wa"/></svg>
</a>
<script src="/assets/js/site.js?v=2" defer></script>
</body>
</html>
