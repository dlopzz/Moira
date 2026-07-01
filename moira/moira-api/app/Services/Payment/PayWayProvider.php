<?php

namespace App\Services\Payment;

use App\Models\PaymentMethod;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PayWayProvider
{
    public function __construct(private readonly PaymentMethod $method) {}

    /**
     * @param  array{token: string, bin: string, payment_method_id: int, installments: int, card_holder_name: string, card_holder_doc_type: string, card_holder_doc_number: string} $tokenData
     */
    public function charge(
        string $customerEmail,
        int    $amountCents,
        array  $tokenData,
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

        $siteId = 'MR-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(6));

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

        \Illuminate\Support\Facades\Log::debug('[PayWay] charge request', [
            'endpoint'                 => "{$baseUrl}/api/v2/payments",
            'payment_method_id'        => (int) $tokenData['payment_method_id'],
            'bin'                      => $tokenData['bin'],
            'installments'             => (int) $tokenData['installments'],
            'amount_cents'             => $amountCents,
            'device_unique_identifier' => $tokenData['device_unique_identifier'] ?? 'NOT SET',
            'token_prefix'             => substr($tokenData['token'], 0, 8),
        ]);
        \Illuminate\Support\Facades\Log::debug('[PayWay] charge response', [
            'http_status' => $response->status(),
            'body'        => $response->body(),
        ]);

        $data    = $response->json() ?? [];
        $status  = $data['status'] ?? 'error';
        $authCode = $data['status_details']['card_authorization_code'] ?? null;

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
