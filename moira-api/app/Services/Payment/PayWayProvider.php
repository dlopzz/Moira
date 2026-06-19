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
                transactionId: null,
                authCode: null,
                status: 'error',
                amountCents: $amountCents,
                raw: ['error' => 'Credenciales o endpoints de PayWay no configurados.'],
            );
        }

        /* Auth: Basic base64(private_key:) — private key as username, empty password */
        $basicToken = base64_encode($privateKey . ':');
        $siteId     = 'MOIRA-' . Str::uuid()->toString();

        try {
            $response = Http::withHeaders(['Authorization' => "Basic {$basicToken}"])
                ->post("{$baseUrl}/api/v2/payments", [
                    'site_transaction_id'      => $siteId,
                    'token'                    => $tokenData['token'],
                    'user_id'                  => $customerEmail,
                    'payment_method_id'        => $tokenData['payment_method_id'],
                    'bin'                      => $tokenData['bin'],
                    'amount'                   => $amountCents,
                    'currency'                 => 'ARS',
                    'installments'             => $tokenData['installments'],
                    'payment_type'             => 'single',
                    'email'                    => $customerEmail,
                    'device_unique_identifier' => Str::uuid()->toString(),
                    'sub_payments'             => [],
                ]);
        } catch (ConnectionException) {
            return new PayWayResult(
                approved: false,
                transactionId: null,
                authCode: null,
                status: 'error',
                amountCents: $amountCents,
                raw: ['error' => 'No se pudo conectar con el procesador de pagos.'],
            );
        }

        $data   = $response->json() ?? [];
        $status = $data['status'] ?? 'error';

        return new PayWayResult(
            approved: in_array($status, ['approved', 'pre_approved']),
            transactionId: $data['id'] ?? null,
            authCode: $data['site_transaction_id'] ?? null,
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
