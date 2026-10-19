# Chatbot de consultorio con Claude

Demo de un asistente estilo WhatsApp para un consultorio **ficticio** de Campeche: responde horarios, precios, ubicación y servicios, y toma solicitudes de cita (nombre, día y motivo). No da diagnósticos y manda las urgencias al 911.

Sirve para enseñarle a un médico cómo funcionaría su asistente antes de conectarlo a su WhatsApp real.

## Cómo funciona

- `index.html`: la página del chat (funciona en celular).
- `api/chat.js`: función que recibe la conversación y le pregunta a Claude (`claude-haiku-5-5`). Aquí está toda la información del consultorio, en el texto `SISTEMA`. Para otro cliente, solo cambias ese texto.
- La clave de la API vive en Vercel como variable de entorno; nunca llega al navegador.
- Protecciones de costo: máximo 500 caracteres por mensaje, 20 mensajes por conversación, 400 tokens por respuesta y 10 mensajes por minuto por persona.

## Publicarlo gratis en Vercel

1. Entra a [vercel.com](https://vercel.com) y crea tu cuenta con **Continue with GitHub** (plan Hobby, gratis).
2. **Add New → Project** e importa el repo `30-dias-con-ia`.
3. En **Root Directory** elige `chatbot-consultorio`. Deja el resto como está.
4. En **Environment Variables** agrega `ANTHROPIC_API_KEY` con tu clave (pégala sin comillas). Te conviene crear una clave nueva solo para esta demo en console.anthropic.com.
5. Presiona **Deploy**. En un minuto tendrás una dirección como `chatbot-consultorio-xxxx.vercel.app`.

Cada vez que se fusione un cambio en `main`, Vercel vuelve a publicar solo.

## Costo y seguridad

Con Haiku 5.5 (0.10 USD por millón de tokens de entrada y 0.50 USD de salida) una conversación de 10 mensajes cuesta menos de un centavo de dólar. Aun así, como la página es pública, pon un **límite de gasto mensual** en console.anthropic.com → *Limits* (por ejemplo 5 USD).

## Cambiarlo para un cliente real

Edita el texto `SISTEMA` en `api/chat.js` con los datos del consultorio y el nombre en `index.html`. Para conectarlo a WhatsApp de verdad se usa la API de WhatsApp de Meta (eso lo veremos más adelante).
