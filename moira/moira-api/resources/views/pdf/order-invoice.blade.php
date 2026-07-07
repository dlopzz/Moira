@extends('pdf.layout')

@section('title', 'Comprobante - Orden #' . $order->id)

@section('content')
    @php
        $siteName = \App\Models\SiteSetting::instance()->name ?: config('app.name', 'Moira');
        $addr = $order->shipping_address ?? [];
    @endphp

    <div class="header">
        <h1>{{ $siteName }}</h1>
        <div class="muted">Comprobante interno — Orden #{{ $order->id }} — {{ $order->created_at->format('d/m/Y H:i') }}</div>
    </div>

    <h2>Cliente</h2>
    <table>
        <tr><td class="muted">Nombre</td><td>{{ $order->customer?->name ?? $addr['label'] ?? 'Invitado' }}</td></tr>
        <tr><td class="muted">Email</td><td>{{ $order->customer?->email ?? '—' }}</td></tr>
        <tr><td class="muted">Dirección</td>
            <td>
                {{ implode(', ', array_filter([
                    $addr['street'] ?? null,
                    $addr['city'] ?? null,
                    $addr['state'] ?? null,
                    $addr['zip_code'] ?? null,
                ])) ?: '—' }}
            </td>
        </tr>
        <tr><td class="muted">Método de pago</td><td>{{ $order->paymentMethod?->name ?? '—' }}</td></tr>
    </table>

    <h2>Productos</h2>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Variante</th>
                <th class="text-right">Precio unit.</th>
                <th class="text-right">Cant.</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->variant_label ?? '—' }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->subtotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="width: 260px; margin-left: auto;">
        <tr><td>Subtotal</td><td class="text-right">${{ number_format($order->subtotal, 2, ',', '.') }}</td></tr>
        <tr><td>Envío</td><td class="text-right">${{ number_format($order->shipping_cost, 2, ',', '.') }}</td></tr>
        @if($order->discount > 0)
            <tr><td>Descuento @if($order->coupon_code)({{ $order->coupon_code }})@endif</td><td class="text-right">-${{ number_format($order->discount, 2, ',', '.') }}</td></tr>
        @endif
        <tr class="total-row"><td>Total</td><td class="text-right">${{ number_format($order->total, 2, ',', '.') }}</td></tr>
    </table>

    <div class="footer">
        Este comprobante es un resumen interno y no posee validez fiscal.<br>
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>
@endsection
