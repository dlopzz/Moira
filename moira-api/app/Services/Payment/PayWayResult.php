<?php

namespace App\Services\Payment;

readonly class PayWayResult
{
    public function __construct(
        public bool    $approved,
        public ?string $transactionId,
        public ?string $authCode,
        public string  $status,
        public int     $amountCents,
        public array   $raw,
    ) {}
}
