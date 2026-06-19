<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f9fafb; margin: 0; padding: 0; color: #111827; }
        .wrap { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        .header { background: #1d4ed8; padding: 28px 32px; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; }
        .header p  { color: #bfdbfe; margin: 6px 0 0; font-size: 14px; }
        .body { padding: 28px 32px; }
        .body p { margin: 0 0 14px; color: #374151; font-size: 15px; line-height: 1.6; }
        .order-box { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; }
        .order-box .label { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; font-weight: 600; margin-bottom: 4px; }
        .order-box .value { font-size: 15px; font-weight: 700; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
        td { padding: 10px 0; font-size: 14px; color: #374151; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        td.right { text-align: right; }
        .totals { border-top: 2px solid #e5e7eb; padding-top: 12px; }
        .totals .row { display: flex; justify-content: space-between; font-size: 14px; color: #6b7280; margin-bottom: 6px; }
        .totals .total { font-size: 17px; font-weight: 700; color: #111827; }
        .address-box { background: #f8fafc; border-radius: 8px; padding: 14px 18px; margin-top: 20px; font-size: 14px; color: #374151; line-height: 1.7; }
        .footer { padding: 20px 32px; border-top: 1px solid #f3f4f6; color: #9ca3af; font-size: 12px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1>{{ config('app.name', 'Moira') }}</h1>
        <p>¡Tu pedido fue recibido y está siendo procesado!</p>
    </div>
    <div class="body">
        <p>Hola <strong>{{ $order->customer->first_name }}</strong>, gracias por tu compra.</p>

        <div class="order-box">
            <div class="label">Número de pedido</div>
            <div class="value">#{{ str_pad((string) $order->id, 8, '0', STR_PAD_LEFT) }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:right">Cant.</th>
                    <th style="text-align:right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row">
                <span>Subtotal</span>
                <span>${{ number_format($order->subtotal, 2) }}</span>
            </div>
            @if((float)$order->discount > 0)
            <div class="row" style="color:#16a34a">
                <span>Descuento</span>
                <span>−${{ number_format($order->discount, 2) }}</span>
            </div>
            @endif
            <div class="row total" style="margin-top:8px; padding-top:8px; border-top:1px solid #e5e7eb;">
                <span>Total</span>
                <span>${{ number_format($order->total, 2) }}</span>
            </div>
        </div>

        @if($order->shipping_address)
        <div class="address-box">
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:#9ca3af; font-weight:600; margin-bottom:6px;">Dirección de envío</div>
            {{ $order->shipping_address['street'] ?? '' }}
            @if(!empty($order->shipping_address['address_line_2']))
                , {{ $order->shipping_address['address_line_2'] }}
            @endif
            <br>
            {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['state'] ?? '' }} {{ $order->shipping_address['zip_code'] ?? '' }}
            <br>
            {{ $order->shipping_address['country'] ?? '' }}
        </div>
        @endif
    </div>
    <div class="footer">
        © {{ date('Y') }} {{ config('app.name', 'Moira') }}. Este email fue generado automáticamente, por favor no respondas.
    </div>
</div>
</body>
</html>
