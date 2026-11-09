# 30-dias-con-ia

Bitácora de mi plan de 30 días usando IA (Claude) para generar ingresos, buscar empleo y crear proyectos propios.
**José Esteban Kantún González**: Ingeniero en Sistemas · Maestría en Inteligencia Artificial · Campeche, México

## Proyectos

| Proyecto | Descripción | Enlace |
| --- | --- | --- |
| Patrulla Orbital | Juego arcade de naves para navegador, hecho con IA: niveles, jefes finales cada 5 niveles, disparo triple y efectos de sonido. Funciona en computadora y celular. | [Jugar](https://favz123.github.io/30-dias-con-ia/patrulla-orbital/) · [Código](patrulla-orbital/index.html) |
| Resumidor de PDFs | Script en Python que usa la API de Claude para resumir cualquier PDF en español: resumen, puntos clave y acciones sugeridas. | [Código](resumidor-pdf/) |
| Recepción en apuros | Juego de navegador: eres la recepcionista de un consultorio y debes contestar los WhatsApp de los pacientes antes de que se acabe el tiempo. Cada vez llegan más rápido. Funciona en celular. | [Jugar](https://favz123.github.io/30-dias-con-ia/recepcion-en-apuros/) · [Código](recepcion-en-apuros/index.html) |
| Chatbot de consultorio | Asistente estilo WhatsApp con la API de Claude para un consultorio ficticio: horarios, precios y solicitudes de cita. Publicado gratis en Vercel. | [Probar](https://chatbot-consultorio-claude.vercel.app/) · [Código](chatbot-consultorio/) |
| En Busca de Closet (prototipo) | Primer catálogo web estático (en lila) para una tienda de ropa de segunda mano en Campeche: prendas por talla con botón a WhatsApp, dinámica de compra, envíos, preguntas frecuentes y ubicación. Optimizado para SEO. | [Ver sitio](https://favz123.github.io/30-dias-con-ia/en-busca-de-closet/) · [Código](en-busca-de-closet/index.html) |
| En Busca de Closet (tienda en línea) | Tienda real en PHP + MySQL en Hostinger: catálogo de cientos de prendas en 5 tallas (XCH a XG) con búsqueda, ficha por prenda y botón a WhatsApp. Incluye un panel para que la dueña suba prendas desde el celular (una por una o hasta 40 a la vez), las marque como disponibles, apartadas o vendidas y administre contraseñas. Cada cambio se publica solo en Hostinger con GitHub Actions. | [Ver tienda](https://enbuscadecloset.com) · [Código](en-busca-de-closet-web/) |

## Bitácora

### Día 1 (13/10/2026)
- CV reescrito con IA y Proyecto "Carrera y negocio" creado en Claude.
- 10 ideas de ingreso definidas; prioridad: chatbot WhatsApp para consultorios, webs WordPress para médicos y trabajo remoto entrenando IA.
- Creativo: juego de naves "Patrulla Orbital".
- Prompt clave: "Dame 10 formas de ganar dinero con mis habilidades y Claude, ordenadas por rapidez".

### Día 2 (14/10/2026)
- LinkedIn optimizado con titular, "Acerca de", aptitudes y "Open to work".
- Patrulla Orbital mejorado con IA: jefe final cada 5 niveles y efectos de sonido (Web Audio).
- Juego publicado con GitHub Pages.
- Prompt clave: "Mejora el juego: agrega un jefe final cada 5 niveles y efectos de sonido activables con un botón; explícame cada cambio antes de hacerlo".

### Día 3 (16/10/2026)
- Primera herramienta con la API de Claude: "Resumidor de PDFs" en Python; probado con la NOM-004 del expediente clínico.
- Mejora: modo libro, que divide PDFs largos en partes y une los resúmenes.
- Lista de 20 empleos objetivo (entrenamiento de IA, salud y gobierno en Campeche).
- Creativo: nuevo enemigo "tanque" en Patrulla Orbital, pedido por mí a Claude Code y aprobado en un pull request.
- Prompt clave: "En patrulla-orbital/index.html agrega un enemigo 'tanque': verde, más grande y lento que los normales, con 4 de vida, que parpadee al recibir un golpe y dé 50 puntos. Debe aparecer desde el nivel 3. No cambies nada más. Abre un pull request".

### Día 4 (18/10/2026)
- Nicho elegido: asistentes de WhatsApp con IA y webs para consultorios privados en Campeche y Carmen, con una clínica local como cliente piloto.
- Análisis del nicho con IA: dolores, 3 paquetes con precios, costos y competencia; precios validados con lo que se cobra en Campeche.
- 2 postulaciones enviadas de la lista de empleos.
- Creativo: juego "Recepción en apuros", donde los WhatsApp de los pacientes llegan cada vez más rápido.
- Prompt clave: "Crea un juego de navegador en el que soy la recepcionista de un consultorio y debo contestar los mensajes de WhatsApp de los pacientes antes de que se acabe el tiempo; al final muestra cuántos atendí y perdí".

### Día 6 (02–03/11/2026)
- Primer proyecto para un negocio real: tienda en línea de **En Busca de Closet**, una tienda de ropa de segunda mano en Campeche, publicada en [enbuscadecloset.com](https://enbuscadecloset.com).
- Del catálogo estático en GitHub Pages se pasó a PHP + MySQL en Hostinger, porque la tienda maneja unas 500 prendas y una prenda vendida debe desactivarse sin tocar código.
- Panel para la dueña desde el celular: subir prendas con foto, carga masiva desde Google Drive, estados disponible / apartada / vendida y manejo de contraseñas. Manual de usuario entregado.
- Dominio, SSL, base de datos y Google Search Console (con sitemap) configurados.
- Pruebas reales: se corrigieron un error 500 del buscador que sólo ocurría en MySQL y un detalle visual del paginador, y se agregó la talla Extra chica a petición de la dueña.
- Mejoras de SEO (textos alternativos, pies de foto, sitemap con imágenes y datos estructurados), favicon propio y crédito de [Favzstudio.com](https://favzstudio.com) en el pie del sitio.
- Prompt clave: "Eres un senior web developer and designer con 20 años de experiencia. Diséñame un sitio web llamativo en color lila. Tomando en cuenta el SEO, el sitio web es un catálogo estático dividido en 4 tallas [...] Cada una tiene un botón que lleva a WhatsApp".

### Día 7 (08–09/11/2026)
- Cambios pedidos por la dueña en [enbuscadecloset.com](https://enbuscadecloset.com): textos de compra y métodos de pago, dirección corregida y portada nueva con 5 fotos de la tienda y el lema "Ropa deportiva secondhand".
- Publicación automática: al fusionar un cambio en GitHub, una automatización revisa el código PHP y sube por FTPS a Hostinger sólo los archivos que cambiaron, sin tocar la configuración ni las fotos de las prendas. Ya no hace falta pasar ZIP ni mover archivos a mano.
- Depuración en producción, reproduciendo cada error antes de corregirlo:
    - el nombre del servidor FTP no existía y la comprobación rechazaba también la IP; ahora acepta la IP y limpia el valor si se pega con `ftp://`, diagonales o espacios;
    - Hostinger corta la conexión cuando se pide un archivo inexistente, lo que hacía fallar la primera publicación; se resolvió creando antes el registro inicial;
    - el certificado de seguridad no coincidía con la IP; ahora se omite ese paso de ayuda sin desactivar la verificación.
- Prompt clave: "¿Qué puedes hacer con acceso a Hostinger?" (la respuesta: mejor que GitHub publique solo, sin darle a la IA las llaves del hosting).
