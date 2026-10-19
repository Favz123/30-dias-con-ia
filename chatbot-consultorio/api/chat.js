// Función serverless de Vercel: recibe la conversación y responde con Claude.
// La clave vive en la variable de entorno ANTHROPIC_API_KEY de Vercel, nunca en el navegador.
import Anthropic from "@anthropic-ai/sdk";

const MODELO = "claude-haiku-5-5"; // rápido y barato para una demo pública
const MAX_MENSAJES = 20;           // límite de turnos por conversación
const MAX_CARACTERES = 500;        // límite por mensaje del paciente
const LIMITE_POR_MINUTO = 10;      // peticiones por IP (aproximado en serverless)

const SISTEMA = `Eres el asistente virtual de WhatsApp del "Consultorio Médico San Román", un consultorio FICTICIO de demostración en Campeche, México.

Información del consultorio (no inventes nada fuera de esto):
- Médica: Dra. Ana López Canul, medicina general.
- Dirección: Calle 10 #123, Barrio de San Román, Campeche (dirección de ejemplo).
- Horario: lunes a viernes de 9:00 a 14:00 y de 16:00 a 20:00; sábados de 9:00 a 13:00. Domingos cerrado.
- Precios: consulta general $500 MXN; certificado médico (escolar, laboral o deportivo) $300 MXN; curaciones desde $250 MXN; toma de presión y glucosa $100 MXN.
- Servicios: consulta general, control de presión y diabetes, certificados médicos, curaciones, retiro de puntos.
- No se hacen: análisis de laboratorio, ultrasonidos ni rayos X (puedes decir que la doctora da la orden para un laboratorio externo).
- Pagos: efectivo, tarjeta y transferencia. No se aceptan seguros de gastos médicos.
- Estacionamiento en la calle. Hay rampa de acceso.

Para agendar una cita pide, de uno en uno: nombre completo, día y hora preferidos (dentro del horario) y el motivo en pocas palabras. Cuando tengas los tres, responde con un resumen que empiece con "📋 Solicitud de cita:" y aclara que recepción la confirmará por WhatsApp. No confirmes la cita tú.

Reglas:
- Responde en español de México, amable y breve (máximo 3 oraciones o una lista corta).
- Nunca des diagnósticos, interpretes síntomas ni recomiendes medicamentos o dosis. Invita a agendar consulta.
- Si mencionan una urgencia (dolor de pecho, dificultad para respirar, sangrado abundante, desmayo, etc.), indica llamar al 911 o acudir a urgencias de inmediato.
- No pidas datos de salud detallados, CURP ni datos bancarios.
- Si preguntan algo que no está en la información, di que lo consultarás con recepción.
- Si te piden algo ajeno al consultorio, explica amablemente que solo atiendes dudas del consultorio.`;

const client = new Anthropic();
const visitas = new Map();

function limitado(ip) {
  const ahora = Date.now();
  const recientes = (visitas.get(ip) || []).filter((t) => ahora - t < 60_000);
  recientes.push(ahora);
  visitas.set(ip, recientes);
  return recientes.length > LIMITE_POR_MINUTO;
}

function validar(mensajes) {
  if (!Array.isArray(mensajes) || mensajes.length === 0 || mensajes.length > MAX_MENSAJES) return false;
  return mensajes.every((m, i) =>
    m && typeof m.content === "string" && m.content.trim() &&
    m.content.length <= MAX_CARACTERES &&
    m.role === (i % 2 === 0 ? "user" : "assistant")) &&
    mensajes.at(-1).role === "user";
}

export default async function handler(req, res) {
  if (req.method !== "POST") return res.status(405).json({ error: "Usa POST." });

  const ip = (req.headers["x-forwarded-for"] || "").split(",")[0] || "local";
  if (limitado(ip)) return res.status(429).json({ error: "Vas muy rápido. Espera un minuto." });

  const mensajes = req.body?.messages;
  if (!validar(mensajes)) {
    return res.status(400).json({ error: `Mensaje no válido (máximo ${MAX_CARACTERES} caracteres y ${MAX_MENSAJES} mensajes).` });
  }

  try {
    const respuesta = await client.messages.create({
      model: MODELO,
      max_tokens: 400,
      thinking: { type: "disabled" }, // respuestas cortas: sin razonamiento extendido, más rápido y barato
      system: [{ type: "text", text: SISTEMA, cache_control: { type: "ephemeral" } }],
      messages: mensajes.map(({ role, content }) => ({ role, content })),
    });
    const texto = respuesta.content.filter((b) => b.type === "text").map((b) => b.text).join("").trim();
    return res.status(200).json({ reply: texto || "Perdón, no entendí. ¿Me lo repites?" });
  } catch (err) {
    console.error(err);
    const status = err instanceof Anthropic.APIError ? err.status : 500;
    const error = status === 401 ? "El asistente no está configurado (falta la clave)." :
                  status === 429 ? "El asistente está ocupado. Intenta en un momento." :
                  "El asistente no está disponible ahora.";
    return res.status(502).json({ error });
  }
}
