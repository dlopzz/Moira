<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; background: #f9fafb; margin: 0; padding: 40px 0; }
        .card { background: #fff; max-width: 520px; margin: 0 auto; border-radius: 12px; padding: 40px; border: 1px solid #e5e7eb; }
        h1 { font-size: 20px; color: #111827; margin-bottom: 8px; }
        p { color: #6b7280; font-size: 15px; line-height: 1.6; }
        .btn { display: inline-block; margin: 24px 0; background: #2563eb; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 15px; }
        .footer { color: #9ca3af; font-size: 13px; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Hola, {{ $customer->first_name }}</h1>
        <p>Gracias por registrarte. Hacé click en el botón para verificar tu dirección de email y activar tu cuenta.</p>

        <a href="{{ $verificationUrl }}" class="btn">Verificar mi cuenta</a>

        <p>El link expira en 24 horas.</p>

        <div class="footer">
            Si no creaste esta cuenta podés ignorar este email.<br>
            Si el botón no funciona, copiá este link: {{ $verificationUrl }}
        </div>
    </div>
</body>
</html>
