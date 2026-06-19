<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f9fafb; margin: 0; padding: 0; color: #111827; }
        .wrap { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        .header { background: #1d4ed8; padding: 28px 32px; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -.3px; }
        .body { padding: 28px 32px; }
        .body p { margin: 0 0 16px; color: #374151; font-size: 15px; line-height: 1.6; }
        .order-num { color: #6b7280; font-size: 13px; margin-bottom: 24px !important; }
        .product-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 20px; margin-bottom: 12px; display: flex; align-items: center; gap: 16px; }
        .product-name { font-weight: 600; font-size: 15px; color: #111827; margin: 0 0 8px; }
        .btn { display: inline-block; background: #1d4ed8; color: #fff !important; text-decoration: none; padding: 9px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; }
        .footer { padding: 20px 32px; border-top: 1px solid #f3f4f6; color: #9ca3af; font-size: 12px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1>{{ config('app.name', 'Moira') }}</h1>
    </div>
    <div class="body">
        <p>Hola <strong>{{ $order->customer->first_name }}</strong>,</p>
        <p>Gracias por tu compra. Tu opinión nos ayuda a mejorar y a otros clientes a elegir mejor. ¿Podés tomarte un minuto para calificar los productos que recibiste?</p>
        <p class="order-num">Pedido #{{ str_pad((string) $order->id, 8, '0', STR_PAD_LEFT) }}</p>

        @foreach($reviews as $review)
        <div class="product-card">
            <div style="flex:1;">
                <p class="product-name">{{ $review->product->name }}</p>
                <a href="{{ rtrim(\App\Models\SiteSetting::getValue('url', config('app.url')), '/') }}/reviews/{{ $review->token }}" class="btn">
                    Dejar reseña ★
                </a>
            </div>
        </div>
        @endforeach

        <p style="margin-top:24px; color:#6b7280; font-size:13px;">
            Cada link es de un solo uso. Si ya enviaste tu reseña, este mensaje puede ignorarse.
        </p>
    </div>
    <div class="footer">
        © {{ date('Y') }} {{ config('app.name', 'Moira') }}. Este email fue enviado porque realizaste una compra.
    </div>
</div>
</body>
</html>
