<?php /* Secciones fijas de la portada: cómo comprar y preguntas frecuentes. */ ?>
  <svg class="wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true" style="background:var(--lila-50)"><path d="M0 30 C 240 0 480 60 720 30 S 1200 0 1440 30 V60 H0Z"/></svg>
  <section id="como-comprar" class="dark" aria-labelledby="din-title">
    <div class="wrap">
      <header class="section-head">
        <h2 id="din-title">Cómo comprar</h2>
        <span class="script">fácil y por WhatsApp</span>
      </header>
      <ol class="steps">
        <li class="step reveal">
          <h3>Elige tu favorito</h3>
          <p>Explora por <strong>categoría</strong> o usa el buscador. Toca la foto para ver el producto en grande.</p>
        </li>
        <li class="step reveal">
          <h3>Toca un botón</h3>
          <p>Se abre WhatsApp con el mensaje <strong>ya escrito</strong>, con el nombre y el código del producto. Sólo dale enviar.</p>
          <div class="botones-guia" aria-hidden="true"><span class="g-wa">Comprar</span><span class="g-ap">💖 Apartar</span><span class="g-in">💬 Más info</span></div>
        </li>
        <li class="step reveal">
          <h3>Acordamos los detalles</h3>
          <p>Te respondemos para confirmar <strong>disponibilidad</strong>, forma de pago y entrega. ¡Y listo, es tuyo!</p>
        </li>
      </ol>
    </div>
  </section>
  <svg class="wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true" style="background:#ffe3ef;transform:scaleY(-1)"><path d="M0 30 C 240 0 480 60 720 30 S 1200 0 1440 30 V60 H0Z"/></svg>

  <section id="preguntas" class="faq-section" aria-labelledby="faq-title">
    <div class="wrap">
      <header class="section-head">
        <h2 id="faq-title">Preguntas</h2>
        <span class="script">frecuentes</span>
      </header>
      <div class="faq">
        <?php foreach (preguntas_frecuentes() as $i => [$pregunta, $respuesta]): ?>
        <details<?= $i === 0 ? ' open' : '' ?>>
          <summary><?= e($pregunta) ?></summary>
          <div class="answer"><?= $respuesta /* HTML fijo escrito en preguntas_frecuentes() */ ?></div>
        </details>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
