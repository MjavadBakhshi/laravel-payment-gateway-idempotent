<?php

namespace Domain\Payment\DataTransferObjects;

use Spatie\LaravelData\Data;
use Domain\Payment\ValueObjects\TransactionId;

class WebhookData extends Data
{
    function __construct(
        public readonly TransactionId $transaction_id,
        public readonly string $status,
        public readonly ?string $bank_refrence,
        public readonly ?float $amount,
    ) {}

    static function rules() :array
    {
        return [
            'status' => 'required|string|in:success,failed,pending'
        ];
    }
}