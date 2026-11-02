# En Busca de Closet: tienda en línea con panel para la dueña

Catálogo de ropa de segunda mano para **enbuscadecloset.com**, con un panel privado (`/admin`) para que la dueña suba prendas y las marque como **Disponible / Apartada / Vendida** desde el celular.

- **Catálogo público:** portada con las 5 tallas, páginas por talla (`/talla/chica`), búsqueda, filtro por marca, paginación y una ficha por prenda (`/prenda/CH-0012`), cada una con su botón de WhatsApp.
- **Estados:**
  - **Disponible:** botón "Me interesa" activo.
  - **Apartada:** etiqueta "Apartada" y botón desactivado.
  - **Vendida:** se ve en gris con la etiqueta "Vendida" durante 3 días y después desaparece sola. Los días se configuran en `dias_vendido`.
- **Carga masiva** (`/admin/carga-masiva.php`): hasta 40 fotos por tanda, también desde Google Drive. Si el archivo se llama `CH - Short Nike negro - 250.jpg`, se llenan solos la talla, la marca, el nombre y el precio. Hay un "aplicar a todas" para lo que se repite, y las fotos se comprimen y suben una por una con barra de progreso.
- **Panel:** foto con la cámara (se reduce en el celular antes de subirla), código automático (`CH-0001`), botones de estado de un toque, búsqueda por código, aviso de apartados vencidos (más de 7 días) y opción "Guardar y subir otra", que recuerda la talla y la marca.
- **SEO:** HTML generado en el servidor, `sitemap.xml` automático, datos estructurados (`ClothingStore`, `FAQPage`, `Product`, `BreadcrumbList`), URLs limpias y fotos WebP.

## Estructura

```
en-busca-de-closet-web/
├── app/                  ← código privado (NO va dentro de public_html)
│   ├── bootstrap.php     ← base de datos, fotos, utilidades
│   ├── config.example.php← copiar como config.php
│   └── vistas/           ← plantillas
├── public_html/          ← lo que se publica
│   ├── index.php         ← portada, tallas y búsqueda
│   ├── prenda.php        ← ficha de cada prenda
│   ├── sitemap.php       ← /sitemap.xml
│   ├── admin/            ← panel de la dueña
│   ├── assets/           ← CSS, JS, logo
│   └── uploads/          ← fotos de las prendas (se llena sola)
└── tools/                ← sólo para pruebas locales
```

## Probar en tu computadora

Necesitas PHP 8.0 o superior con las extensiones `pdo_sqlite` y `gd` (con soporte WebP).

```bash
cd en-busca-de-closet-web
cp app/config.example.php app/config.php
# En app/config.php: driver 'sqlite', sitio_url 'http://localhost:8000' y una clave_instalacion
php tools/cargar-ejemplos.php          # opcional: carga 7 prendas de ejemplo
php -S localhost:8000 -t public_html tools/router.php
```

Abre http://localhost:8000 (tienda) y http://localhost:8000/admin (panel).

## Instalar en Hostinger

1. **Agrega el sitio.** En hPanel: **Sitios web → Agregar sitio web**. Elige el dominio `enbuscadecloset.com` y la opción de sitio vacío (sin WordPress).
2. **Activa SSL.** En **Seguridad → SSL**, instala el certificado gratuito.
3. **Revisa PHP.** En **Avanzado → Configuración de PHP**, elige PHP 8.2 o superior y verifica que `gd` y `pdo_mysql` estén activos (normalmente ya lo están).
4. **Crea la base de datos.** En **Bases de datos → Bases de datos MySQL**, crea una base y un usuario, y anota el nombre de la base, el usuario y la contraseña.
5. **Sube los archivos.** Abre el **Administrador de archivos** y ve a `domains/enbuscadecloset.com/`:
   - Sube la carpeta `app/` **al lado** de `public_html/`, no adentro.
   - Sube el **contenido** de `public_html/` dentro de `public_html/`, incluidos `.htaccess` y `uploads/.htaccess`.
   - No subas `tools/`.
6. **Configura el sitio.** En `app/`, copia `config.example.php` como `config.php` y llena:
   - `whatsapp`: el número de la tienda, con 52 y 10 dígitos.
   - `db`: `driver` igual a `mysql` y los datos del paso 4.
   - `clave_instalacion`: una clave difícil.
7. **Crea la cuenta.** Abre `https://enbuscadecloset.com/admin`. La primera vez te pide la clave de instalación para crear el usuario y la contraseña de la dueña. Esto sólo funciona una vez.
8. **Avisa a Google.** En Google Search Console, agrega el dominio y envía `https://enbuscadecloset.com/sitemap.xml`.
9. **Instala el panel en el celular de la dueña.** Abre `/admin` en su celular y elige **Agregar a pantalla de inicio**. Queda como app.

> Si Hostinger no te deja poner `app/` fuera de `public_html`, súbela dentro (`public_html/app/`). El sitio la encuentra solo y `app/.htaccess` bloquea el acceso desde internet. En ese caso, en `config.php` cambia `carpeta_fotos` por `__DIR__ . '/../uploads'`.

## Respaldos

Las prendas viven en la base de datos MySQL y las fotos en `public_html/uploads/`. Hostinger hace respaldos automáticos según tu plan. Además, conviene descargar cada mes un respaldo desde **Archivos → Respaldos**.
