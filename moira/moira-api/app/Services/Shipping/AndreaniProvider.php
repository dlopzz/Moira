<?php

namespace App\Services\Shipping;

use App\Models\ShippingMethod;
use Illuminate\Support\Facades\Http;

class AndreaniProvider
{
    private const API_BASE = 'https://apis.andreani.com/v1';

    private const RATE_CODES = [
        'EP'  => 'Andreani Envío a Domicilio',
        'SUC' => 'Andreani Retiro en Sucursal',
    ];

    public function __construct(private readonly ShippingMethod $method) {}

    /**
     * @return ShippingRate[]
     */
    public function getRates(string $destinationZip, int $weightGrams, float $declaredValue): array
    {
        if ($this->method->is_simulation) {
            return $this->simulatedRates($destinationZip, $weightGrams, $declaredValue);
        }

        return $this->liveRates($destinationZip, $weightGrams, $declaredValue);
    }

    /** @return ShippingRate[] */
    private function simulatedRates(string $destinationZip, int $weightGrams, float $declaredValue): array
    {
        $base = max(1200.0, $declaredValue * 0.02 + $weightGrams * 0.5);
        $markup = (float) ($this->method->configValue('markup_percentage', 0) / 100);

        return [
            new ShippingRate(
                code: 'EP',
                label: self::RATE_CODES['EP'],
                price: round($base * (1 + $markup), 2),
                estimatedDays: '3-5 días hábiles',
            ),
            new ShippingRate(
                code: 'SUC',
                label: self::RATE_CODES['SUC'],
                price: round($base * 0.75 * (1 + $markup), 2),
                estimatedDays: '2-4 días hábiles',
            ),
        ];
    }

    /** @return ShippingRate[] */
    private function liveRates(string $destinationZip, int $weightGrams, float $declaredValue): array
    {
        $token = $this->authenticate();
        if (! $token) {
            return [];
        }

        $clientNumber = $this->method->credential('client_number');
        $contract     = $this->method->credential('contract_number');

        $rates = [];

        foreach (array_keys(self::RATE_CODES) as $modalidad) {
            $response = Http::withToken($token)
                ->post(self::API_BASE . '/tarifas', [
                    'cpDestino'      => $destinationZip,
                    'pesoTotal'      => $weightGrams,
                    'volumenTotal'   => $this->method->configValue('default_volume_cm3', 1000),
                    'valorDeclarado' => (int) ceil($declaredValue),
                    'cliente'        => $clientNumber,
                    'contrato'       => $contract,
                    'modalidad'      => $modalidad,
                ]);

            if ($response->successful()) {
                $data  = $response->json();
                $price = (float) ($data['tarifaConIva'] ?? $data['tarifa'] ?? 0);

                if ($price > 0) {
                    $markup = (float) ($this->method->configValue('markup_percentage', 0) / 100);
                    $rates[] = new ShippingRate(
                        code: $modalidad,
                        label: self::RATE_CODES[$modalidad],
                        price: round($price * (1 + $markup), 2),
                        estimatedDays: $data['plazoEntrega'] ?? '3-5 días hábiles',
                    );
                }
            }
        }

        return $rates;
    }

    private function authenticate(): ?string
    {
        $username = $this->method->credential('username');
        $password = $this->method->credential('password');

        if (! $username || ! $password) {
            return null;
        }

        $response = Http::post(self::API_BASE . "/usuarios/{$username}/login", [
            'password' => $password,
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        return null;
    }
}
