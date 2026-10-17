# Resumidor de PDFs con Claude

Script de Python que envía un PDF a la API de Claude y devuelve un resumen en español con **Resumen**, **Puntos clave** y **Qué hacer con esto**. El resultado también se guarda como `documento.resumen.md` junto al PDF.

Sirve para contratos, reportes, artículos médicos, normas o cualquier documento largo. Con clientes, es la base de un servicio de "resúmenes automáticos de documentos".

## Requisitos

1. **Python 3.10 o superior** ([python.org](https://www.python.org/downloads/)). En Windows marca "Add Python to PATH" al instalar.
2. **Una clave de la API de Claude**: crea una cuenta en [console.anthropic.com](https://console.anthropic.com), compra créditos en *Billing* y genera una clave en *API Keys*. La API se cobra aparte de tu plan de claude.ai.
3. Instala la librería:
   ```bash
   pip install -r requirements.txt
   ```

## Uso

Define tu clave en la terminal (no la escribas dentro del código ni la subas a GitHub):

```powershell
# Windows (PowerShell): se guarda para siempre; abre una ventana nueva después
setx ANTHROPIC_API_KEY sk-ant-...
```
Escríbela **sin comillas**: con teclado en español es fácil que se cuele `¨` en lugar de `"` y la API la rechace.
```bash
# Mac o Linux
export ANTHROPIC_API_KEY="sk-ant-..."
```

Luego:

```bash
python resumir.py mi-documento.pdf
python resumir.py contrato.pdf --enfoque "fechas y penalizaciones"
python resumir.py libro.pdf --paginas 50
```

En Windows puedes usar `py` en lugar de `python`.

### Modo libro

Si el PDF tiene más de 100 páginas, el script lo divide solo en partes, resume cada una y al final une todo en un resumen con **Resumen**, **Estructura** (capítulos), **Puntos clave** y **Qué hacer con esto**. Debajo guarda también el resumen de cada parte. Con `--paginas` cambias el tamaño de cada parte.

## Costo

Usa el modelo `claude-opus-5-5` (4 USD por millón de tokens de entrada y 20 USD por millón de salida). Un PDF de unas 10 páginas cuesta aproximadamente entre 0.10 y 0.20 USD, así que un libro de 300 páginas rondaría los 3 a 6 USD; el script imprime los tokens usados para que lo compruebes. Para ahorrar, puedes cambiar `MODELO` a `claude-haiku-5-5`, que es mucho más barato aunque menos preciso.

## Límites

- Cada parte enviada debe pesar menos de 32 MB. Si un libro con muchas imágenes marca error de tamaño, usa `--paginas` más chico.
- PDFs con contraseña no se pueden leer.
- Si Claude rechaza un documento por seguridad, la API lo reintenta con otro modelo de forma automática (`fallbacks="default"`).
