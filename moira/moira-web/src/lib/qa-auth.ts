import { timingSafeEqual } from "node:crypto";

export const QA_COOKIE_NAME = "moira_qa_access";
export const QA_LOGIN_PATH = "/qa-login";

const MAX_AGE_SECONDS = 60 * 60 * 24 * 30; // 30 días

const hmacKeys = new Map<string, Promise<CryptoKey>>();

function getHmacKey(secret: string): Promise<CryptoKey> {
  let key = hmacKeys.get(secret);
  if (!key) {
    key = crypto.subtle.importKey(
      "raw",
      new TextEncoder().encode(secret),
      { name: "HMAC", hash: "SHA-256" },
      false,
      ["sign"]
    );
    hmacKeys.set(secret, key);
  }
  return key;
}

async function hmac(secret: string, message: string): Promise<string> {
  const key = await getHmacKey(secret);
  const signature = await crypto.subtle.sign("HMAC", key, new TextEncoder().encode(message));
  return Buffer.from(signature).toString("hex");
}

function base64UrlEncode(input: string): string {
  return Buffer.from(input, "utf8").toString("base64url");
}

function base64UrlDecode(input: string): string {
  return Buffer.from(input, "base64url").toString("utf8");
}

/**
 * Firma una cookie de acceso QA atada a `username`, con la fecha de emisión
 * embebida en el payload firmado (no solo en el atributo maxAge de la cookie,
 * que el cliente podría no respetar). `secret` debe ser un QA_AUTH_SECRET ya
 * validado por el caller.
 */
export async function signQaCookie(secret: string, username: string): Promise<string> {
  const payload = base64UrlEncode(JSON.stringify({ u: username, iat: Math.floor(Date.now() / 1000) }));
  const signature = await hmac(secret, payload);
  return `${payload}.${signature}`;
}

export async function verifyQaCookie(cookieValue: string | undefined, secret: string): Promise<boolean> {
  if (!cookieValue) return false;

  const dotIndex = cookieValue.lastIndexOf(".");
  if (dotIndex === -1) return false;

  const payload = cookieValue.slice(0, dotIndex);
  const signature = cookieValue.slice(dotIndex + 1);

  let parsed: { u?: string; iat?: number };
  try {
    parsed = JSON.parse(base64UrlDecode(payload));
  } catch {
    return false;
  }
  if (!parsed.u || !Number.isFinite(parsed.iat)) return false;
  if (Math.floor(Date.now() / 1000) - parsed.iat! > MAX_AGE_SECONDS) return false;

  const expected = await hmac(secret, payload);
  const a = Buffer.from(signature);
  const b = Buffer.from(expected);
  return a.length === b.length && timingSafeEqual(a, b);
}
