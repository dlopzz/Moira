<?php

namespace App\Services\Payment;

use App\Models\PaymentMethod;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayWayProvider
{
    public function __construct(private readonly PaymentMethod $method) {}

    /**
     * @param  array{token: string, bin: string, payment_method_id: int, installments: int, card_holder_name: string, card_holder_doc_type: string, card_holder_doc_number: string} $tokenData
     */
    /**
     * @param string|null $idempotencyKey  Identificador estable del intento de
     *        cobro (normalmente el id del quote). PayWay rechaza un
     *        site_transaction_id repetido, así que pasarlo convierte el reintento
     *        en un no-op del lado del proveedor: si la primera llamada llegó a
     *        procesarse y nosotros no vimos la respuesta (timeout, corte de red),
     *        el reintento NO genera un segundo cobro. Sin esta clave, cada
     *        llamada genera un site_transaction_id nuevo y el cliente puede
     *        terminar pagando dos veces.
     */
    public function charge(
        string  $customerEmail,
        int     $amountCents,
        array   $tokenData,
        ?string $idempotencyKey = null,
    ): PayWayResult {
        $privateKey = $this->method->activePrivateKey();
        $baseUrl    = $this->apiBaseUrl();

        if (! $privateKey || ! $baseUrl) {
            return new PayWayResult(
                approved: false,
                pending: false,
                transactionId: null,
                siteTransactionId: null,
                authCode: null,
                status: 'error',
                amountCents: $amountCents,
                raw: ['error' => 'Credenciales o endpoints de PayWay no configurados.'],
            );
        }

        $siteId = $idempotencyKey
            ? 'MR-' . substr(strtoupper(hash('sha256', $idempotencyKey)), 0, 24)
            : 'MR-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(6));

        try {
            $response = Http::withHeaders([
                    'apikey' => $privateKey,
                ])
                ->post("{$baseUrl}/api/v2/payments", [
                    'site_transaction_id' => $siteId,
                    'token'               => $tokenData['token'],
                    'customer'            => [
                        'id'    => $customerEmail,
                        'email' => $customerEmail,
                    ],
                    'payment_method_id'   => (int) $tokenData['payment_method_id'],
                    'bin'                 => $tokenData['bin'],
                    'amount'              => $amountCents,
                    'currency'            => 'ARS',
                    'installments'        => (int) $tokenData['installments'],
                    'description'         => '',
                    'payment_type'        => 'single',
                    'establishment_name'  => 'Moira',
                    'sub_payments'        => [],
                    'fraud_detection'     => ['send_to_cs' => false],
                ]);
        } catch (ConnectionException) {
            return new PayWayResult(
                approved: false,
                pending: false,
                transactionId: null,
                siteTransactionId: null,
                authCode: null,
                status: 'error',
                amountCents: $amountCents,
                raw: ['error' => 'No se pudo conectar con el procesador de pagos.'],
            );
        }

        $data    = $response->json() ?? [];
        $status  = $data['status'] ?? 'error';
        $authCode = $data['status_details']['card_authorization_code'] ?? null;

        // Campos explícitos, nunca el body crudo: la respuesta de PayWay incluye
        // datos del titular y de la tarjeta. Va en info y no en debug para que el
        // diagnóstico de pagos no dependa de dejar LOG_LEVEL en debug en producción.
        Log::info('[PayWay] charge', [
            'site_transaction_id' => $siteId,
            'http_status'         => $response->status(),
            'status'              => $status,
            'transaction_id'      => $data['id'] ?? null,
            'amount_cents'        => $amountCents,
            'installments'        => (int) $tokenData['installments'],
            'bin'                 => $tokenData['bin'],
            'error_type'          => $data['status_details']['error_type'] ?? null,
        ]);

        return new PayWayResult(
            approved: in_array($status, ['approved', 'pre_approved']),
            pending: $status === 'pending',
            transactionId: $data['id'] ?? null,
            siteTransactionId: $data['site_transaction_id'] ?? null,
            authCode: $authCode,
            status: $status,
            amountCents: $amountCents,
            raw: $data,
        );
    }

    public function apiBaseUrl(): string
    {
        $key = $this->method->is_sandbox ? 'endpoint_sandbox' : 'endpoint_production';

        return rtrim((string) $this->method->configValue($key, ''), '/');
    }

    public function jsSdkUrl(): ?string
    {
        return $this->method->configValue('js_sdk_url');
    }
}
