import { NextRequest, NextResponse } from "next/server";
import { QA_COOKIE_NAME, signQaCookie } from "@/lib/qa-auth";
import { api, ApiError } from "@/lib/api";

export async function POST(request: NextRequest) {
  const secret = process.env.QA_AUTH_SECRET;
  if (!secret) {
    return NextResponse.json({ error: "QA gate no configurado" }, { status: 500 });
  }

  let username: string, password: string;
  try {
    ({ username, password } = await request.json());
  } catch {
    return NextResponse.json({ error: "Solicitud inválida" }, { status: 400 });
  }

  try {
    await api.qaVerify({ username, password });
  } catch (err) {
    if (err instanceof ApiError) {
      if (err.status === 429) {
        return NextResponse.json(
          { error: "Demasiados intentos. Esperá un minuto y volvé a intentar." },
          { status: 429 }
        );
      }
      return NextResponse.json({ error: "Usuario o contraseña incorrectos" }, { status: 401 });
    }
    return NextResponse.json({ error: "No se pudo validar las credenciales" }, { status: 502 });
  }

  const signed = await signQaCookie(secret, username);
  const response = NextResponse.json({ ok: true });
  response.cookies.set(QA_COOKIE_NAME, signed, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge: 60 * 60 * 24 * 30, // 30 días
  });
  return response;
}
