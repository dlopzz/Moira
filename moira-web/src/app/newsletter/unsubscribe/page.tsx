import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Desuscripción | Moira Bikinis',
  robots: 'noindex',
};

async function unsubscribe(token: string): Promise<{ ok: boolean; message: string }> {
  const base = process.env.NEXT_PUBLIC_API_URL!;
  try {
    const res = await fetch(`${base}/newsletter/unsubscribe?token=${encodeURIComponent(token)}`, {
      cache: 'no-store',
    });
    const data = await res.json();
    if (!res.ok) return { ok: false, message: data.message ?? 'Link inválido o ya utilizado.' };
    return { ok: true, message: data.message ?? 'Te desuscribiste correctamente.' };
  } catch {
    return { ok: false, message: 'Error de conexión. Intentá nuevamente.' };
  }
}

export default async function UnsubscribePage({
  searchParams,
}: {
  searchParams: Promise<{ token?: string }>;
}) {
  const params = await searchParams;
  const token = params.token ?? '';

  const result = token
    ? await unsubscribe(token)
    : { ok: false, message: 'Token no encontrado.' };

  return (
    <div style={{
      minHeight: '100vh',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: '#f1f5f9',
      padding: '24px',
    }}>
      <div style={{
        background: '#fff',
        borderRadius: '16px',
        padding: '48px 40px',
        maxWidth: '440px',
        width: '100%',
        boxShadow: '0 4px 24px rgba(0,0,0,0.08)',
        textAlign: 'center',
      }}>
        <div style={{ fontSize: '48px', marginBottom: '16px' }}>
          {result.ok ? '👋' : '⚠️'}
        </div>
        <h1 style={{
          fontFamily: 'system-ui, sans-serif',
          fontSize: '22px',
          fontWeight: 700,
          color: '#0f172a',
          marginBottom: '12px',
        }}>
          {result.ok ? 'Desuscripción exitosa' : 'Algo salió mal'}
        </h1>
        <p style={{
          fontFamily: 'system-ui, sans-serif',
          fontSize: '15px',
          color: '#64748b',
          lineHeight: 1.6,
          marginBottom: '32px',
        }}>
          {result.message}
        </p>
        <a
          href="/"
          style={{
            display: 'inline-block',
            background: '#f43f5e',
            color: '#fff',
            fontFamily: 'system-ui, sans-serif',
            fontSize: '14px',
            fontWeight: 700,
            textDecoration: 'none',
            padding: '12px 28px',
            borderRadius: '8px',
          }}
        >
          Volver al inicio
        </a>
      </div>
    </div>
  );
}
