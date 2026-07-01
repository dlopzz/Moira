<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Confirmación de pedido</title>
    <!--[if mso]>
    <noscript>
        <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    </noscript>
    <![endif]-->
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; background-color: #f1f5f9; width: 100% !important; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; font-size: inherit !important; font-family: inherit !important; font-weight: inherit !important; line-height: inherit !important; }
        @media only screen and (max-width: 600px) {
            .email-wrapper { width: 100% !important; }
            .email-content { padding: 24px 16px !important; }
            .header-pad { padding: 28px 20px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;">

@php
    $siteName = \App\Models\SiteSetting::instance()->name ?: config('app.name', 'Moira');
    $siteUrl  = \App\Models\SiteSetting::instance()->url  ?: config('app.url', '');
    $orderNumber = str_pad((string) $order->id, 8, '0', STR_PAD_LEFT);

    // Nombre del destinatario
    if ($order->customer) {
        $recipientName = $order->customer->first_name;
    } elseif (!empty($order->shipping_address['label'])) {
        // Para guest: label = "Nombre Apellido"
        $parts = explode(' ', $order->shipping_address['label']);
        $recipientName = $parts[0];
    } else {
        $recipientName = 'Cliente';
    }

    // Dirección de envío
    $addr = $order->shipping_address ?? [];
@endphp

<!-- Wrapper -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td style="padding:32px 16px;">

      <!-- Card -->
      <table role="presentation" class="email-wrapper" border="0" cellpadding="0" cellspacing="0" width="600" align="center"
             style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">

        <!-- ── HEADER ─────────────────────────────────────────────── -->
        <tr>
          <td class="header-pad" style="background:#0f172a;padding:32px 36px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
              <tr>
                <td>
                  <p style="margin:0 0 4px;font-family:Arial,sans-serif;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">
                    {{ $siteName }}
                  </p>
                  <p style="margin:0;font-family:Arial,sans-serif;font-size:13px;color:#94a3b8;letter-spacing:0.04em;text-transform:uppercase;">
                    Confirmación de pedido
                  </p>
                </td>
                <td align="right" valign="top">
                  <p style="margin:0;font-family:Arial,sans-serif;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Pedido</p>
                  <p style="margin:4px 0 0;font-family:'Courier New',monospace;font-size:18px;font-weight:700;color:#e2e8f0;">
                    #{{ $orderNumber }}
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ── STATUS BAR ────────────────────────────────────────── -->
        <tr>
          <td style="background:#f43f5e;padding:10px 36px;">
            <p style="margin:0;font-family:Arial,sans-serif;font-size:12px;font-weight:600;color:#ffffff;letter-spacing:0.05em;text-transform:uppercase;">
              ✓ &nbsp;Tu compra fue confirmada y está siendo procesada
            </p>
          </td>
        </tr>

        <!-- ── BODY ──────────────────────────────────────────────── -->
        <td class="email-content" style="padding:32px 36px;">

          <!-- Saludo -->
          <p style="margin:0 0 20px;font-family:Arial,sans-serif;font-size:15px;color:#374151;line-height:1.65;">
            Hola <strong>{{ $recipientName }}</strong>, gracias por tu compra en {{ $siteName }}.
            A continuación encontrás el resumen de tu pedido.
          </p>

          <!-- ── ITEMS ──────────────────────────────────────────── -->
          <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
                 style="margin-bottom:4px;">
            <tr>
              <td colspan="3" style="padding-bottom:8px;border-bottom:2px solid #0f172a;">
                <p style="margin:0;font-family:Arial,sans-serif;font-size:11px;font-weight:700;color:#0f172a;text-transform:uppercase;letter-spacing:0.08em;">
                  Productos
                </p>
              </td>
            </tr>
            @foreach($order->items as $item)
            <tr>
              <td style="padding:12px 0 12px;font-family:Arial,sans-serif;font-size:14px;color:#1e293b;border-bottom:1px solid #f1f5f9;vertical-align:top;width:60%;">
                <span style="font-weight:600;">{{ $item->product_name }}</span>
                @if($item->variant_label)
                  <br>
                  <span style="font-size:12px;color:#94a3b8;">{{ $item->variant_label }}</span>
                @endif
              </td>
              <td style="padding:12px 8px;font-family:Arial,sans-serif;font-size:13px;color:#64748b;border-bottom:1px solid #f1f5f9;text-align:center;vertical-align:top;width:15%;">
                × {{ $item->quantity }}
              </td>
              <td style="padding:12px 0 12px;font-family:Arial,sans-serif;font-size:14px;color:#1e293b;border-bottom:1px solid #f1f5f9;text-align:right;vertical-align:top;width:25%;white-space:nowrap;">
                ${{ number_format((float)$item->subtotal, 0, ',', '.') }}
              </td>
            </tr>
            @endforeach
          </table>

          <!-- ── TOTALES ─────────────────────────────────────────── -->
          <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
                 style="margin:16px 0 28px;">

            <tr>
              <td style="font-family:Arial,sans-serif;font-size:13px;color:#64748b;padding:5px 0;">Subtotal</td>
              <td style="font-family:Arial,sans-serif;font-size:13px;color:#64748b;padding:5px 0;text-align:right;white-space:nowrap;">
                ${{ number_format((float)$order->subtotal, 0, ',', '.') }}
              </td>
            </tr>

            @if((float)$order->shipping_cost > 0)
            <tr>
              <td style="font-family:Arial,sans-serif;font-size:13px;color:#64748b;padding:5px 0;">
                Envío{{ $order->shipping_method_label ? ' — ' . $order->shipping_method_label : '' }}
              </td>
              <td style="font-family:Arial,sans-serif;font-size:13px;color:#64748b;padding:5px 0;text-align:right;white-space:nowrap;">
                ${{ number_format((float)$order->shipping_cost, 0, ',', '.') }}
              </td>
            </tr>
            @else
            <tr>
              <td style="font-family:Arial,sans-serif;font-size:13px;color:#64748b;padding:5px 0;">
                Envío{{ $order->shipping_method_label ? ' — ' . $order->shipping_method_label : '' }}
              </td>
              <td style="font-family:Arial,sans-serif;font-size:13px;color:#16a34a;padding:5px 0;text-align:right;font-weight:600;">
                Gratis
              </td>
            </tr>
            @endif

            @if((float)$order->discount > 0)
            <tr>
              <td style="font-family:Arial,sans-serif;font-size:13px;color:#16a34a;padding:5px 0;">
                Descuento{{ $order->coupon_code ? ' (' . strtoupper($order->coupon_code) . ')' : '' }}
              </td>
              <td style="font-family:Arial,sans-serif;font-size:13px;color:#16a34a;padding:5px 0;text-align:right;font-weight:600;white-space:nowrap;">
                −${{ number_format((float)$order->discount, 0, ',', '.') }}
              </td>
            </tr>
            @endif

            <tr>
              <td colspan="2" style="padding:4px 0 0;border-top:2px solid #0f172a;"></td>
            </tr>
            <tr>
              <td style="font-family:Arial,sans-serif;font-size:17px;font-weight:700;color:#0f172a;padding:10px 0 0;">
                Total
              </td>
              <td style="font-family:Arial,sans-serif;font-size:17px;font-weight:700;color:#f43f5e;padding:10px 0 0;text-align:right;white-space:nowrap;">
                ${{ number_format((float)$order->total, 0, ',', '.') }}
              </td>
            </tr>

          </table>

          <!-- ── DIRECCIÓN ──────────────────────────────────────── -->
          @if(!empty($addr))
          <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
                 style="margin-bottom:28px;">
            <tr>
              <td style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px 18px;">
                <p style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">
                  Dirección de envío
                </p>
                <p style="margin:0;font-family:Arial,sans-serif;font-size:14px;color:#374151;line-height:1.7;">
                  @if(!empty($addr['street'])){{ $addr['street'] }}@endif
                  @if(!empty($addr['address_line_2']))<br>{{ $addr['address_line_2'] }}@endif
                  @if(!empty($addr['city']) || !empty($addr['state']))
                    <br>{{ implode(', ', array_filter([$addr['city'] ?? '', $addr['state'] ?? ''])) }}
                    @if(!empty($addr['zip_code'])) {{ $addr['zip_code'] }}@endif
                  @endif
                  @if(!empty($addr['telephone']))<br>Tel: {{ $addr['telephone'] }}@endif
                </p>
              </td>
            </tr>
          </table>
          @endif

          <!-- ── CTA BUTTON (solo usuarios autenticados) ─────────── -->
          @if($order->customer && $siteUrl)
          <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
                 style="margin-bottom:28px;">
            <tr>
              <td align="center">
                <a href="{{ rtrim($siteUrl, '/') }}/profile/orders"
                   style="display:inline-block;background:#0f172a;color:#ffffff;font-family:Arial,sans-serif;font-size:14px;font-weight:600;text-decoration:none;padding:13px 32px;border-radius:8px;letter-spacing:0.02em;">
                  Ver mis pedidos →
                </a>
              </td>
            </tr>
          </table>
          @endif

          <!-- ── MENSAJE DE CIERRE ──────────────────────────────── -->
          <p style="margin:0;font-family:Arial,sans-serif;font-size:14px;color:#64748b;line-height:1.6;">
            Si tenés alguna consulta sobre tu pedido, respondé este email o escribinos a
            <a href="mailto:{{ \App\Models\SiteSetting::instance()->email }}"
               style="color:#f43f5e;text-decoration:none;">{{ \App\Models\SiteSetting::instance()->email }}</a>.
          </p>

        </td>

        <!-- ── FOOTER ─────────────────────────────────────────────── -->
        <tr>
          <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:18px 36px;">
            <p style="margin:0;font-family:Arial,sans-serif;font-size:11px;color:#94a3b8;text-align:center;line-height:1.6;">
              © {{ date('Y') }} {{ $siteName }}. Este email fue generado automáticamente — por favor no respondas directamente.<br>
              @if($siteUrl)
                <a href="{{ $siteUrl }}" style="color:#cbd5e1;text-decoration:none;">{{ $siteUrl }}</a>
              @endif
            </p>
          </td>
        </tr>

      </table>
      <!-- /Card -->

    </td>
  </tr>
</table>
<!-- /Wrapper -->

</body>
</html>
