"use client";

import { useState, Suspense } from "react";
import { useSearchParams } from "next/navigation";
import Image from "next/image";

function QaLoginForm() {
  const searchParams = useSearchParams();
  const rawNext = searchParams.get("next") || "/";
  // Solo permitir rutas relativas del propio sitio — nunca una URL externa ni
  // protocol-relative ("//evil.com"), para no habilitar un open redirect
  // después de un login con credenciales reales.
  const next = rawNext.startsWith("/") && !rawNext.startsWith("//") ? rawNext : "/";

  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const res = await fetch("/api/qa-login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, password }),
      });

      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        setError(data.error ?? "Usuario o contraseña incorrectos");
        setLoading(false);
        return;
      }

      // Navegación dura (no router.push): garantiza que el navegador ya aplicó
      // la cookie Set-Cookie del POST antes de volver a pasar por el gate del
      // proxy. Con router.push la navegación soft/prefetch puede dispararse
      // antes de que la cookie se comprometa y el proxy rebota de nuevo a login.
      window.location.assign(next);
    } catch {
      setError("Ocurrió un error. Intentá de nuevo.");
      setLoading(false);
    }
  }

  return (
    <div
      style={{
        minHeight: "100dvh",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        background: "var(--bg-subtle)",
        padding: "1.5rem",
      }}
    >
      <div
        style={{
          width: "100%",
          maxWidth: 380,
          background: "var(--surface)",
          border: "1px solid var(--border)",
          borderRadius: 8,
          padding: "2.5rem 2rem",
          boxShadow: "0 4px 24px rgba(0,0,0,0.06)",
        }}
      >
        <div style={{ display: "flex", justifyContent: "center", marginBottom: "1.5rem" }}>
          <Image src="/moira-logo.jpg" alt="Moira" width={220} height={75} priority />
        </div>

        <h1
          style={{
            color: "var(--title)",
            fontSize: "1rem",
            fontWeight: 600,
            textAlign: "center",
            marginBottom: "0.4rem",
            letterSpacing: "1px",
          }}
        >
          Acceso privado
        </h1>
        <p
          style={{
            color: "var(--text)",
            fontSize: "0.8rem",
            textAlign: "center",
            marginBottom: "1.75rem",
          }}
        >
          Este sitio está en QA. Ingresá tus credenciales para continuar.
        </p>

        <form onSubmit={handleSubmit} style={{ display: "flex", flexDirection: "column", gap: "1rem" }}>
          <div>
            <label
              htmlFor="username"
              style={{ display: "block", fontSize: "0.75rem", color: "var(--text)", marginBottom: "0.35rem" }}
            >
              Usuario
            </label>
            <input
              id="username"
              type="text"
              autoComplete="username"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
              style={{
                width: "100%",
                padding: "0.6rem 0.75rem",
                border: "1px solid var(--border)",
                borderRadius: 4,
                background: "var(--surface)",
                color: "var(--title)",
              }}
            />
          </div>

          <div>
            <label
              htmlFor="password"
              style={{ display: "block", fontSize: "0.75rem", color: "var(--text)", marginBottom: "0.35rem" }}
            >
              Contraseña
            </label>
            <input
              id="password"
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              style={{
                width: "100%",
                padding: "0.6rem 0.75rem",
                border: "1px solid var(--border)",
                borderRadius: 4,
                background: "var(--surface)",
                color: "var(--title)",
              }}
            />
          </div>

          {error && (
            <p style={{ color: "var(--danger)", fontSize: "0.8rem", margin: 0 }}>{error}</p>
          )}

          <button
            type="submit"
            disabled={loading}
            style={{
              marginTop: "0.5rem",
              padding: "0.7rem",
              border: "none",
              borderRadius: 4,
              background: "var(--global-palette-btn-bg)",
              color: "var(--global-palette-btn)",
              fontWeight: 600,
              letterSpacing: "1px",
              cursor: loading ? "default" : "pointer",
              opacity: loading ? 0.7 : 1,
            }}
          >
            {loading ? "Ingresando..." : "Ingresar"}
          </button>
        </form>
      </div>
    </div>
  );
}

export default function QaLoginPage() {
  return (
    <Suspense fallback={null}>
      <QaLoginForm />
    </Suspense>
  );
}
