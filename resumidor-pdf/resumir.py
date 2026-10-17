"""Resume un PDF con la API de Claude.

Uso:
    python resumir.py documento.pdf
    python resumir.py documento.pdf --enfoque "puntos legales"

Guarda el resumen junto al PDF como documento.resumen.md
"""
import argparse
import base64
import sys
from pathlib import Path

import anthropic

MODELO = "claude-opus-5-5"
LIMITE_MB = 32  # tamaño máximo de una petición a la API

INSTRUCCIONES = """Resume este documento en español para alguien con poco tiempo.
Usa exactamente este formato en Markdown:

## Resumen
Un párrafo de 3 a 5 oraciones con lo esencial.

## Puntos clave
De 5 a 8 viñetas con datos concretos (cifras, fechas, nombres).

## Qué hacer con esto
De 2 a 4 acciones o decisiones que el documento sugiere.
"""


def leer_pdf(ruta: Path) -> str:
    if not ruta.is_file():
        sys.exit(f"No encuentro el archivo: {ruta}")
    if ruta.suffix.lower() != ".pdf":
        sys.exit("El archivo debe ser un PDF.")
    tamano_mb = ruta.stat().st_size / 1_000_000
    if tamano_mb > LIMITE_MB:
        sys.exit(f"El PDF pesa {tamano_mb:.1f} MB; el máximo es {LIMITE_MB} MB.")
    return base64.standard_b64encode(ruta.read_bytes()).decode()


def resumir(ruta: Path, enfoque: str | None) -> str:
    pedido = INSTRUCCIONES
    if enfoque:
        pedido += f"\nPon especial atención a: {enfoque}."

    client = anthropic.Anthropic()  # lee la clave de ANTHROPIC_API_KEY
    respuesta = client.beta.messages.create(
        model=MODELO,
        max_tokens=16000,
        output_config={"effort": "medium"},
        # Si el modelo rechaza la petición, la API la reintenta con otro modelo
        betas=["server-side-fallback-2026-07-01"],
        fallbacks="default",
        messages=[{
            "role": "user",
            "content": [
                {"type": "document", "source": {"type": "base64", "media_type": "application/pdf", "data": leer_pdf(ruta)}},
                {"type": "text", "text": pedido},
            ],
        }],
    )

    if respuesta.stop_reason == "refusal":
        sys.exit("Claude no pudo resumir este documento.")
    texto = "".join(b.text for b in respuesta.content if b.type == "text")
    if respuesta.stop_reason == "max_tokens":
        texto += "\n\n_(El resumen se cortó por longitud.)_"
    uso = respuesta.usage
    print(f"Tokens usados: {uso.input_tokens} de entrada, {uso.output_tokens} de salida", file=sys.stderr)
    return texto


def main() -> None:
    parser = argparse.ArgumentParser(description="Resume un PDF con Claude.")
    parser.add_argument("pdf", type=Path, help="ruta del PDF")
    parser.add_argument("--enfoque", help="tema en el que debe fijarse el resumen")
    args = parser.parse_args()

    try:
        resumen = resumir(args.pdf, args.enfoque)
    except anthropic.AuthenticationError:
        sys.exit("Clave inválida o ausente. Define ANTHROPIC_API_KEY (ver README).")
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
