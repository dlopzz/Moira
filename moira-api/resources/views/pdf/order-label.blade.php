@extends('pdf.layout')

@section('title', 'Etiqueta de envío - Orden #' . $order->id)

@section('content')
    @php
        $siteName = \App\Models\SiteSetting::instance()->name ?: config('app.name', 'Moira');
        $addr = $order->shipping_address ?? [];
    @endphp

    <div class="header">
        <h1>{{ $siteName }}</h1>
        <div class="muted">Etiqueta de envío — Orden #{{ $order->id }}</div>
    </div>

    <h2>Destinatario</h2>
    <p>
        {{ $addr['label'] ?? $order->customer?->name ?? 'Cliente' }}<br>
        @if(!empty($addr['street'])){{ $addr['street'] }}@endif
        @if(!empty($addr['address_line_2']))<br>{{ $addr['address_line_2'] }}@endif
        @if(!empty($addr['city']) || !empty($addr['state']))
            <br>{{ implode(', ', array_filter([$addr['city'] ?? '', $addr['state'] ?? ''])) }}
            @if(!empty($addr['zip_code'])) {{ $addr['zip_code'] }}@endif
        @endif
        @if(!empty($addr['telephone']))<br>Tel: {{ $addr['telephone'] }}@endif
    </p>

    <h2>Envío</h2>
    <table>
        <tr><td class="muted">Método</td><td>{{ $order->shipping_method_label ?? '—' }}</td></tr>
        <tr><td class="muted">N° de seguimiento</td><td>{{ $order->tracking_number ?? '—' }}</td></tr>
        <tr><td class="muted">Fecha de envío</td><td>{{ $order->shipped_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
    </table>

    <h2>Contenido</h2>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Variante</th>
                <th class="text-right">Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->variant_label ?? '—' }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Generado el {{ now()->format('d/m/Y H:i') }}</div>
@endsection
