"""Resume un PDF con la API de Claude.

Uso:
    python resumir.py documento.pdf
    python resumir.py documento.pdf --enfoque "puntos legales"
    python resumir.py libro.pdf --paginas 50

Si el PDF es largo (un libro), lo divide en partes, resume cada una
y al final une todo en un solo resumen.

Guarda el resumen junto al PDF como documento.resumen.md
"""
import argparse
import base64
import io
import sys
from pathlib import Path

import anthropic
from pypdf import PdfReader, PdfWriter
from pypdf.errors import PdfReadError

MODELO = "claude-opus-5-5"
LIMITE_MB = 32  # tamaño máximo de una petición a la API
PAGINAS_POR_PARTE = 100  # arriba de esto se usa el modo libro

INSTRUCCIONES = """Resume este documento en español para alguien con poco tiempo.
Usa exactamente este formato en Markdown:

## Resumen
Un párrafo de 3 a 5 oraciones con lo esencial.

## Puntos clave
De 5 a 8 viñetas con datos concretos (cifras, fechas, nombres).

## Qué hacer con esto
De 2 a 4 acciones o decisiones que el documento sugiere.
"""

INSTRUCCIONES_PARTE = """Esta es la parte {n} de {total} de un documento largo (páginas {desde} a {hasta}).
Resúmela en español en un máximo de 300 palabras: ideas principales, datos concretos
(cifras, fechas, nombres) y títulos de los capítulos que aparezcan. Solo el resumen, sin introducción.
"""

INSTRUCCIONES_LIBRO = """Abajo están los resúmenes, en orden, de todas las partes de un documento largo.
Con ellos escribe el resumen del documento completo en español, con exactamente este formato en Markdown:

## Resumen
Un párrafo de 4 a 6 oraciones con lo esencial de todo el documento.

## Estructura
Una viñeta por capítulo o parte, con una línea sobre lo que trata.

## Puntos clave
De 6 a 10 viñetas con las ideas y datos más importantes.

## Qué hacer con esto
De 2 a 4 acciones o decisiones que el documento sugiere.
"""


def validar_pdf(ruta: Path) -> None:
    if not ruta.is_file():
        sys.exit(f"No encuentro el archivo: {ruta}")
    if ruta.suffix.lower() != ".pdf":
        sys.exit("El archivo debe ser un PDF.")


def a_base64(datos: bytes) -> str:
    tamano_mb = len(datos) / 1_000_000
    if tamano_mb > LIMITE_MB:
        sys.exit(f"Una parte del PDF pesa {tamano_mb:.1f} MB; el máximo es {LIMITE_MB} MB. Prueba con --paginas más chico.")
    return base64.standard_b64encode(datos).decode()


def dividir(lector: PdfReader, paginas: int) -> list[tuple[int, int, bytes]]:
    """Devuelve (página inicial, página final, bytes del PDF) por cada parte."""
    partes = []
    for inicio in range(0, len(lector.pages), paginas):
        fin = min(inicio + paginas, len(lector.pages))
        escritor = PdfWriter()
        for i in range(inicio, fin):
            escritor.add_page(lector.pages[i])
        buffer = io.BytesIO()
        escritor.write(buffer)
        partes.append((inicio + 1, fin, buffer.getvalue()))
    return partes


def preguntar(client: anthropic.Anthropic, contenido: list[dict], max_tokens: int = 16000) -> str:
    respuesta = client.beta.messages.create(
        model=MODELO,
        max_tokens=max_tokens,
        output_config={"effort": "medium"},
        # Si el modelo rechaza la petición, la API la reintenta con otro modelo
        betas=["server-side-fallback-2026-07-01"],
        fallbacks="default",
        messages=[{"role": "user", "content": contenido}],
    )
    if respuesta.stop_reason == "refusal":
        sys.exit("Claude no pudo resumir este documento.")
    texto = "".join(b.text for b in respuesta.content if b.type == "text")
    if respuesta.stop_reason == "max_tokens":
        texto += "\n\n_(El resumen se cortó por longitud.)_"
    uso = respuesta.usage
    print(f"  Tokens usados: {uso.input_tokens} de entrada, {uso.output_tokens} de salida", file=sys.stderr)
    return texto


def documento(datos: bytes) -> dict:
    return {"type": "document", "source": {"type": "base64", "media_type": "application/pdf", "data": a_base64(datos)}}


def resumir(ruta: Path, enfoque: str | None, paginas: int) -> str:
    validar_pdf(ruta)
    extra = f"\nPon especial atención a: {enfoque}." if enfoque else ""
    client = anthropic.Anthropic()  # lee la clave de ANTHROPIC_API_KEY

    lector = PdfReader(ruta)
    total_paginas = len(lector.pages)
    if total_paginas <= paginas:
        print(f"Resumiendo {total_paginas} páginas...", file=sys.stderr)
        return preguntar(client, [documento(ruta.read_bytes()), {"type": "text", "text": INSTRUCCIONES + extra}])

    # Modo libro: resume cada parte y luego une los resúmenes
    partes = dividir(lector, paginas)
    print(f"Documento de {total_paginas} páginas: lo divido en {len(partes)} partes.", file=sys.stderr)
    resumenes = []
    for n, (desde, hasta, datos) in enumerate(partes, start=1):
        print(f"Parte {n}/{len(partes)} (páginas {desde}-{hasta})...", file=sys.stderr)
        pedido = INSTRUCCIONES_PARTE.format(n=n, total=len(partes), desde=desde, hasta=hasta) + extra
        resumenes.append(f"### Parte {n} (páginas {desde}-{hasta})\n\n" + preguntar(client, [documento(datos), {"type": "text", "text": pedido}], max_tokens=4000))

    print("Uniendo los resúmenes...", file=sys.stderr)
    todas = "\n\n".join(resumenes)
    final = preguntar(client, [{"type": "text", "text": INSTRUCCIONES_LIBRO + extra + "\n\n" + todas}])
    return f"{final}\n\n---\n\n## Resumen por partes\n\n{todas}"


def main() -> None:
    parser = argparse.ArgumentParser(description="Resume un PDF con Claude.")
    parser.add_argument("pdf", type=Path, help="ruta del PDF")
    parser.add_argument("--enfoque", help="tema en el que debe fijarse el resumen")
    parser.add_argument("--paginas", type=int, default=PAGINAS_POR_PARTE,
                        help=f"páginas por parte en documentos largos (por defecto {PAGINAS_POR_PARTE})")
    args = parser.parse_args()
    if args.paginas < 1:
        sys.exit("--paginas debe ser 1 o más.")

    try:
        resumen = resumir(args.pdf, args.enfoque, args.paginas)
    except PdfReadError:
        sys.exit("No pude leer el PDF; puede estar dañado o protegido con contraseña.")
    except anthropic.AuthenticationError as e:
        sys.exit(f"Clave inválida o ausente ({e.message}). Define ANTHROPIC_API_KEY sin comillas (ver README).")
    except anthropic.RateLimitError:
        sys.exit("Demasiadas peticiones o sin saldo. Espera un minuto o revisa tu crédito.")
    except anthropic.BadRequestError as e:
        sys.exit(f"La API rechazó la petición: {e.message}")
    except anthropic.APIConnectionError:
        sys.exit("Sin conexión a internet.")
    except anthropic.APIStatusError as e:
        sys.exit(f"Error de la API ({e.status_code}): {e.message}")

    salida = args.pdf.with_suffix(".resumen.md")
    salida.write_text(f"# Resumen de {args.pdf.name}\n\n{resumen}\n", encoding="utf-8")
    print(resumen)
    print(f"\nGuardado en: {salida}", file=sys.stderr)


if __name__ == "__main__":
    main()
