<?php $waGeneral = whatsapp_link('¡Hola, Punto Bonito! 👋 Vi su catálogo en línea y tengo una duda.'); ?>
</main>

<footer class="site-footer">
  <svg class="wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true" style="background:var(--lila-50)"><path d="M0 30 C 240 0 480 60 720 30 S 1200 0 1440 30 V60 H0Z"/></svg>
  <div class="wrap foot">
    <div>
      <a class="brand" href="/"><img src="/assets/img/logo-64.png" width="64" height="64" alt="Logo de Punto Bonito" loading="lazy"><span>Punto Bonito</span></a>
      <p>Bolsos, monederos, tazas, termos, aretes y mucho más.</p>
      <?php if (cfg('direccion')): ?><p>📍 <?= e((string) cfg('direccion')) ?></p><?php endif; ?>
    </div>
    <nav class="foot-cats" aria-label="Categorías">
      <?php foreach (CATEGORIAS as $c): ?><a href="/categoria/<?= e($c['slug']) ?>"><?= e($c['nombre']) ?></a><?php endforeach; ?>
    </nav>
    <div class="foot-links">
      <a href="<?= e($waGeneral) ?>" target="_blank" rel="noopener"><svg class="ico" aria-hidden="true"><use href="#i-wa"/></svg> <?= e(whatsapp_configurado() ? telefono_legible() : 'WhatsApp') ?></a>
      <?php if (cfg('instagram')): ?>
        <a href="https://www.instagram.com/<?= e((string) cfg('instagram')) ?>/" target="_blank" rel="noopener"><svg class="ico" aria-hidden="true"><use href="#i-ig"/></svg> @<?= e((string) cfg('instagram')) ?></a>
      <?php endif; ?>
      <?php if (cfg('facebook')): ?>
        <a href="<?= e((string) cfg('facebook')) ?>" target="_blank" rel="noopener">Facebook</a>
      <?php endif; ?>
    </div>
  </div>
  <p class="copy">© <?= date('Y') ?> Punto Bonito. Todos los derechos reservados.</p>
  <p class="copy credit">Sitio web creado por <a href="https://favzstudio.com" target="_blank" rel="noopener">Favzstudio.com</a></p>
</footer>

<a class="wa-float" href="<?= e($waGeneral) ?>" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp">
  <svg class="ico" aria-hidden="true"><use href="#i-wa"/></svg>
</a>
<script src="/assets/js/site.js?v=1" defer></script>
</body>
</html>
