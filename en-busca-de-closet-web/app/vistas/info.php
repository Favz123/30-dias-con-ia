<?php /* Secciones fijas: dinámica, envíos, preguntas y tienda física. */ ?>
  <svg class="wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true" style="background:var(--lila-50)"><path d="M0 30 C 240 0 480 60 720 30 S 1200 0 1440 30 V60 H0Z"/></svg>
  <section id="como-comprar" class="dark" aria-labelledby="din-title">
    <div class="wrap">
      <header class="section-head">
        <h2 id="din-title">Dinámica</h2>
        <span class="script">¿cómo comprar en línea?</span>
      </header>
      <ol class="steps">
        <li class="step reveal">
          <h3>Escríbenos</h3>
          <p>Las compras se realizan por <strong>DM (Instagram o Facebook) o WhatsApp</strong>: mándanos las prendas de tu interés para verificar disponibilidad y aclarar las dudas que puedas tener.</p>
        </li>
        <li class="step reveal">
          <h3>Confirma en 30 min</h3>
          <p>En las <strong>actualizaciones</strong>, la prenda se otorga al primer comentario o DM. Después tienes <strong>30 minutos</strong> para confirmar la compra o pasa a la siguiente persona.</p>
        </li>
        <li class="step reveal">
          <h3>Paga en 24 h</h3>
          <p>Una vez confirmada la compra, dispones de <strong>24 horas</strong> para realizar el pago o la prenda vuelve a estar disponible.</p>
        </li>
      </ol>
    </div>
  </section>
  <svg class="wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true" style="background:var(--lila-50);transform:scaleY(-1)"><path d="M0 30 C 240 0 480 60 720 30 S 1200 0 1440 30 V60 H0Z"/></svg>

  <section id="envios" aria-labelledby="env-title">
    <div class="wrap">
      <header class="section-head">
        <h2 id="env-title">Envíos</h2>
        <span class="script">Correos o Estafeta</span>
        <p>Enviamos a todo México. Si compras con envío, el pago es por transferencia, depósito o link de pago con tarjeta.</p>
      </header>
      <div class="ship-grid">
        <article class="ship reveal">
          <span class="price">$80</span>
          <span class="kicker">Opción 1</span>
          <h3>Correos de México</h3>
          <ul>
            <li>Te proporcionamos guía de rastreo del paquete.</li>
            <li>Tarda aprox. 2 semanas en llegar a tu domicilio.</li>
            <li>Incluye el número de prendas que quieras, <strong>sin costo extra</strong>.</li>
          </ul>
        </article>
        <article class="ship reveal">
          <span class="price">desde $160</span>
          <span class="kicker">Opción 2</span>
          <h3>Estafeta</h3>
          <ul>
            <li>El precio depende de la zona y peso del paquete.</li>
            <li>Económico: 5 a 7 días hábiles · Express: 1 a 2 días.</li>
            <li>Mándanos tu código postal y colonia, te cotizamos <strong>sin compromiso</strong>.</li>
          </ul>
        </article>
      </div>
      <div class="subhead">
        <h3>Campeche</h3>
        <span class="script">entrega personal o mandaditos</span>
      </div>
      <div class="ship-grid">
        <article class="ship reveal">
          <span class="kicker">En tienda</span>
          <h3>Entrega personal</h3>
          <ul>
            <li>Se realiza en la tienda física.</li>
            <li>Si no pasas el mismo día del apartado, se requiere el <strong>50%</strong> del valor de la prenda para asegurar la compra.</li>
          </ul>
        </article>
        <article class="ship reveal">
          <span class="kicker">A tu puerta</span>
          <h3>Servicio a domicilio</h3>
          <ul>
            <li>Se realiza por medio de mandaditos.</li>
            <li>Tiene un costo extra dependiendo de la zona.</li>
          </ul>
        </article>
      </div>
      <p class="pay-note">En ambos casos, se puede realizar el pago en efectivo, transferencia/depósito o con tarjeta de crédito/débito.</p>
    </div>
  </section>

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

  <section id="tienda" aria-labelledby="tienda-title">
    <div class="wrap">
      <header class="section-head">
        <h2 id="tienda-title">Tienda física</h2>
        <span class="script">¡ven a visitarnos!</span>
      </header>
      <div class="visit-grid">
        <figure class="store-photo reveal" style="margin:0">
          <img src="/assets/img/tienda-fisica-campeche.webp" width="492" height="646" loading="lazy" decoding="async" alt="Fachada lila de la tienda En Busca de Closet en la Colonia San José, Campeche">
          <figcaption>Busca la fachada lila 💜</figcaption>
        </figure>
        <div class="visit-info">
          <div class="address-card reveal">
            <h3>Dirección</h3>
            <address>Av. Colosio #54, entre Calle 22 y Calle Victoria,<br>Col. San José, San Francisco de Campeche, Camp.</address>
            <ul>
              <li>Contra esquina de “Majo Castillo” repostería.</li>
              <li>Enfrente de Mimika World (tienda de disfraces).</li>
              <li>Justo al lado de la papelería.</li>
            </ul>
            <p style="margin:18px 0 0"><a class="btn btn-light" style="box-shadow:none" href="https://www.google.com/maps/search/?api=1&amp;query=Av.+Colosio+54+Col.+San+Jos%C3%A9+San+Francisco+de+Campeche" target="_blank" rel="noopener">Cómo llegar</a></p>
          </div>
          <div class="map reveal">
            <iframe title="Mapa de la tienda En Busca de Closet en Campeche" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q=Av.%20Colosio%2054%2C%20Col.%20San%20Jos%C3%A9%2C%20San%20Francisco%20de%20Campeche&amp;z=16&amp;output=embed"></iframe>
          </div>
        </div>
      </div>
    </div>
  </section>
