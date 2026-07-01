<?php

namespace App\Services\Payment;

readonly class PayWayResult
{
    public function __construct(
        public bool    $approved,
        public bool    $pending,
        public ?string $transactionId,
        public ?string $siteTransactionId,
        public ?string $authCode,
        public string  $status,
        public int     $amountCents,
        public array   $raw,
    ) {}
}
