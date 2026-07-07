<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenida al newsletter</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        body { margin: 0; padding: 0; background-color: #f1f5f9; width: 100%; }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;">

@php
    $siteName = \App\Models\SiteSetting::instance()->name ?: config('app.name', 'Moira');
    $siteUrl  = \App\Models\SiteSetting::instance()->url  ?: config('app.url', '');
    $unsubscribeUrl = rtrim($siteUrl, '/') . '/newsletter/unsubscribe?token=' . $subscriber->token;
@endphp

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td style="padding:32px 16px;">
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center"
             style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">

        <!-- HEADER -->
        <tr>
          <td style="background:#0f172a;padding:32px 36px;">
            <p style="margin:0 0 4px;font-family:Arial,sans-serif;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">
              {{ $siteName }}
            </p>
            <p style="margin:0;font-family:Arial,sans-serif;font-size:13px;color:#94a3b8;letter-spacing:0.04em;text-transform:uppercase;">
              Newsletter
            </p>
          </td>
        </tr>

        <!-- STATUS BAR -->
        <tr>
          <td style="background:#f43f5e;padding:10px 36px;">
            <p style="margin:0;font-family:Arial,sans-serif;font-size:12px;font-weight:600;color:#ffffff;letter-spacing:0.05em;text-transform:uppercase;">
              🌊 &nbsp;¡Ya sos parte de la comunidad Moira!
            </p>
          </td>
        </tr>

        <!-- BODY -->
        <tr>
          <td style="padding:36px 36px 28px;">

            <!-- Icono decorativo -->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:24px;">
              <tr>
                <td align="center">
                  <div style="width:72px;height:72px;background:#fff1f2;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:32px;line-height:72px;text-align:center;">
                    💌
                  </div>
                </td>
              </tr>
            </table>

            <p style="margin:0 0 12px;font-family:Arial,sans-serif;font-size:22px;font-weight:700;color:#0f172a;text-align:center;line-height:1.3;">
              ¡Gracias por suscribirte!
            </p>

            <p style="margin:0 0 24px;font-family:Arial,sans-serif;font-size:15px;color:#64748b;text-align:center;line-height:1.65;">
              A partir de ahora vas a ser la primera en enterarte de nuestras<br>
              nuevas colecciones, ofertas exclusivas y novedades de temporada.
            </p>

            <!-- Beneficios -->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
                   style="margin-bottom:28px;background:#f8fafc;border-radius:10px;overflow:hidden;">
              <tr>
                <td style="padding:20px 24px;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="padding:6px 0;font-family:Arial,sans-serif;font-size:14px;color:#374151;">
                        <span style="color:#f43f5e;font-weight:700;margin-right:8px;">✓</span> Acceso anticipado a nuevas colecciones
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:6px 0;font-family:Arial,sans-serif;font-size:14px;color:#374151;">
                        <span style="color:#f43f5e;font-weight:700;margin-right:8px;">✓</span> Ofertas y descuentos exclusivos para suscriptoras
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:6px 0;font-family:Arial,sans-serif;font-size:14px;color:#374151;">
                        <span style="color:#f43f5e;font-weight:700;margin-right:8px;">✓</span> Tips de moda y tendencias de temporada
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- CTA -->
            @if($siteUrl)
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
                   style="margin-bottom:28px;">
              <tr>
                <td align="center">
                  <a href="{{ $siteUrl }}"
                     style="display:inline-block;background:#f43f5e;color:#ffffff;font-family:Arial,sans-serif;font-size:14px;font-weight:700;text-decoration:none;padding:14px 36px;border-radius:8px;letter-spacing:0.02em;">
                    Ver colección →
                  </a>
                </td>
              </tr>
            </table>
            @endif

          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:18px 36px;">
            <p style="margin:0;font-family:Arial,sans-serif;font-size:11px;color:#94a3b8;text-align:center;line-height:1.8;">
              © {{ date('Y') }} {{ $siteName }}. Este email fue enviado a {{ $subscriber->email }}.<br>
              Si no querés seguir recibiendo nuestro newsletter,
              <a href="{{ $unsubscribeUrl }}" style="color:#94a3b8;">cancelá tu suscripción aquí</a>.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
