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
| El número de WhatsApp (hoy 981 105 0953) | `app/config.php` → `whatsapp` (52 + 10 dígitos) |
| Instagram, Facebook, ciudad o dirección | `app/config.php` → `instagram`, `facebook`, `ciudad`, `estado`, `direccion` |
| La descripción de cada categoría (sale en la página y en Google) | `app/bootstrap.php` → `descripcion` dentro de `CATEGORIAS` |
| Agregar, quitar o renombrar categorías, su ícono o color | `app/bootstrap.php` → `const CATEGORIAS` (instrucciones ahí mismo) |
| Los textos de los mensajes de WhatsApp | `app/bootstrap.php` → `mensaje_producto()` |
| Preguntas frecuentes | `app/bootstrap.php` → `preguntas_frecuentes()` |
| Productos, fotos, precios y estados | Panel `/admin`, sin tocar código |

> Las preguntas frecuentes son generales (no dan montos ni plazos). Conviene ajustarlas a las reglas reales de la tienda.

### Varias piezas del mismo producto

Si llegan varias piezas iguales, se sube **una sola foto** y en “Cantidad (piezas iguales)” se pone cuántas son. En la tienda sale “Disponibles: N”.

- En la lista del panel, **− Vendí 1** resta una pieza; al vender la última, el producto pasa solo a “Agotado”. **+ Llegó 1** suma una (y si estaba agotado, lo vuelve a poner disponible).
- Si una clienta aparta una de varias piezas, usa **− Vendí 1** (o baja la cantidad) en lugar de “Apartado”, para que las demás sigan a la venta.
- Si ya habías subido el mismo producto varias veces, arriba de la lista aparece **🔁 Posibles repetidos** (mismo nombre, categoría y precio). “Unir” deja una sola ficha, la más antigua, con las piezas sumadas, y borra las demás.
- La columna `cantidad` se crea sola en la base de datos la primera vez que se abre el sitio con esta versión.

### Subir varias fotos a la vez

En **Subir varios productos**, si el archivo se llama `Bolso tejido rosa - 350.jpg`, se llenan solos la categoría (por la palabra “bolso”), el color, el nombre y el precio. También sirve la clave al inicio: `BOL - Bolso tejido rosa - 350.jpg`. Para varias piezas iguales agrega `x3` o `3pz`: `Taza corazón - 120 x3.jpg`.

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

## Instalar en Hostinger (puntobonito.store)

1. **Sitio y SSL.** En hPanel: **Sitios web → Agregar sitio web** con `puntobonito.store` (sitio vacío, sin WordPress) y activa el SSL gratuito.
2. **PHP.** PHP 8.2 o superior con `gd` y `pdo_mysql`.
3. **Base de datos.** Crea una base MySQL y un usuario nuevos (no uses la de En Busca de Closet).
4. **Publicación automática.** Ver la sección siguiente; sube `app/` y `public_html/` solos.
5. **`config.php`.** En el Administrador de archivos, dentro de `domains/puntobonito.store/app/`, copia `config.example.php` como `config.php` y llena `db` (driver `mysql`) y una `clave_instalacion` difícil. El WhatsApp y el dominio ya vienen puestos.
6. **Cuenta de la dueña.** Abre `https://puntobonito.store/admin` y crea el usuario con la clave de instalación.
7. **Google.** En [Search Console](https://search.google.com/search-console) agrega la propiedad `puntobonito.store` (por DNS, o con la etiqueta: pon su código en `google_verificacion`) y envía `https://puntobonito.store/sitemap.xml`. Conviene también crear el **Perfil de Negocio de Google** con el mismo nombre, teléfono y la dirección del sitio.

## Publicación automática (GitHub → Hostinger)

El flujo `.github/workflows/publicar-punto-bonito.yml` funciona igual que el de En Busca de Closet: al fusionar en `main` un cambio de `punto-bonito-web/app/` o `public_html/`, revisa el PHP y sube por FTPS sólo lo que cambió. Nunca toca `config.php`, `app/datos/` ni las fotos de `uploads/`.

- **Mismo hosting que En Busca de Closet:** no hay que crear nada. Usa los secretos `FTP_SERVIDOR`, `FTP_USUARIO` y `FTP_CLAVE` que ya existen y publica en `domains/puntobonito.store/`. Esto sirve si esos datos son de la cuenta FTP principal de Hostinger.
- **Otra cuenta FTP o carpeta:** crea los secretos `PB_FTP_SERVIDOR`, `PB_FTP_USUARIO` y `PB_FTP_CLAVE`, y la variable `PB_FTP_RUTA` (la carpeta donde están `app/` y `public_html/`; `./` si la cuenta FTP ya abre ahí).
- **Antes de la primera publicación real:** en **Actions → Publicar Punto Bonito → Run workflow** deja marcado “Sólo simular” y revisa en el registro a qué carpeta subiría.

---
Diseño y desarrollo: [Favzstudio.com](https://favzstudio.com)
