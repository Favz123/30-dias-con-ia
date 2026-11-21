# Punto Bonito: catálogo en línea con pedidos por WhatsApp

Catálogo de **Punto Bonito** (bolsos, monederos, cosmetiqueras, lapiceras, tazas, termos, vasos, llaveros, plumas, aretes, espejos y mandiles), hecho sobre la misma base que la tienda de En Busca de Closet: PHP + MySQL en Hostinger, con un panel privado (`/admin`) para subir los productos desde el celular.

- **Cada producto** tiene su foto y tres botones de WhatsApp con el mensaje ya escrito (nombre, código, precio y enlace del producto):
  - **Comprar** · “Quiero *comprar* este producto…”
  - **Apartar** · “Me gustaría *apartar* este producto…”
  - **Más info** · “Quisiera *más información* de este producto…”
- **Catálogo público:** portada con mosaico de las 12 categorías, una sección por categoría con lo más nuevo, página por categoría (`/categoria/bolsos`), buscador (también entiende “bolso”, “taza”, un color o un código), filtro por color, orden por precio, paginación y ficha por producto (`/producto/BOL-0001`) con botón de compartir.
- **Estados:** Disponible / Apartado / Vendido, con un toque desde el panel. Un apartado desactiva Comprar y Apartar; un vendido se ve en gris 3 días y después desaparece solo.
- **Etiqueta “Nuevo”** durante 7 días (`dias_nuevo` en la configuración).
- **Panel:** foto con la cámara, código automático por categoría (`BOL-0001`, `TAZ-0002`…), carga masiva de hasta 40 fotos y aviso si falta el número de WhatsApp.
- **Colores:** los del logo, combinados al estilo del *Diccionario de combinaciones de color* de Sanzo Wada: magenta, azul, naranja, amarillo, verde y morado sobre papel crema, con vino como color de anclaje. Están al inicio de `public_html/assets/css/site.css`.

## Lo que se edita más seguido

| Quiero cambiar… | Dónde |
| --- | --- |
| **El número de WhatsApp** (⚠️ pendiente) | `app/config.php` → `whatsapp` (52 + 10 dígitos) |
| Instagram, Facebook o dirección | `app/config.php` → `instagram`, `facebook`, `direccion` |
| Agregar, quitar o renombrar categorías, su ícono o color | `app/bootstrap.php` → `const CATEGORIAS` (instrucciones ahí mismo) |
| Los textos de los mensajes de WhatsApp | `app/bootstrap.php` → `mensaje_producto()` |
| Preguntas frecuentes | `app/bootstrap.php` → `preguntas_frecuentes()` |
| Productos, fotos, precios y estados | Panel `/admin`, sin tocar código |

> Las preguntas frecuentes son generales (no dan montos ni plazos). Conviene ajustarlas a las reglas reales de la tienda.

### Subir varias fotos a la vez

En **Subir varios productos**, si el archivo se llama `Bolso tejido rosa - 350.jpg`, se llenan solos la categoría (por la palabra “bolso”), el color, el nombre y el precio. También sirve la clave al inicio: `BOL - Bolso tejido rosa - 350.jpg`.

Claves: BOL Bolsos · MON Monederos · COS Cosmetiqueras · LAP Lapiceras · TAZ Tazas · TER Termos · VAS Vasos · LLA Llaveros · PLU Plumas · ARE Aretes · ESP Espejos · MAN Mandiles.

## Probar en tu computadora

Necesitas PHP 8.0 o superior con `pdo_sqlite` y `gd` (con WebP).

```bash
cd punto-bonito-web
cp app/config.example.php app/config.php
# En app/config.php: driver 'sqlite', sitio_url 'http://localhost:8000'
php tools/cargar-ejemplos.php          # opcional: 12 productos de PRUEBA con fotos de ropa
php -S localhost:8000 -t public_html tools/router.php
```

Abre http://localhost:8000 (tienda) y http://localhost:8000/admin (panel). Los productos de prueba usan fotos de ropa de En Busca de Closet sólo como relleno; bórralos (o borra `app/datos/`) antes de cargar los reales.

## Instalar en Hostinger

Es igual que En Busca de Closet (ver `en-busca-de-closet-web/LEEME.md`): sitio vacío con su dominio, SSL, PHP 8.2 con `gd` y `pdo_mysql`, una base MySQL nueva, `app/` **al lado** de `public_html/`, `config.php` con los datos y una `clave_instalacion` difícil, y después abrir `/admin` para crear la cuenta de la dueña. Por último, enviar `https://<dominio>/sitemap.xml` a Google Search Console.

La publicación automática por GitHub Actions todavía no está configurada para este sitio, porque falta saber el dominio y el hosting.

---
Diseño y desarrollo: [Favzstudio.com](https://favzstudio.com)
