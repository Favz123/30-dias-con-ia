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
# Windows (PowerShell)
$env:ANTHROPIC_API_KEY="sk-ant-..."
```
```bash
# Mac o Linux
export ANTHROPIC_API_KEY="sk-ant-..."
```

Luego:

```bash
python resumir.py mi-documento.pdf
python resumir.py contrato.pdf --enfoque "fechas y penalizaciones"
```

## Costo

Usa el modelo `claude-opus-5-5` (4 USD por millón de tokens de entrada y 20 USD por millón de salida). Un PDF de unas 10 páginas cuesta aproximadamente entre 0.10 y 0.20 USD; el script imprime los tokens usados para que lo compruebes. Para ahorrar, puedes cambiar `MODELO` a `claude-haiku-5-5`, que es mucho más barato aunque menos preciso.

## Límites

- PDF de hasta 32 MB y 600 páginas.
- Si Claude rechaza un documento por seguridad, la API lo reintenta con otro modelo de forma automática (`fallbacks="default"`).
