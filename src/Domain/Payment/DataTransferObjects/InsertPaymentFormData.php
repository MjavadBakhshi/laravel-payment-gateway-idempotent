<?php

namespace Domain\Payment\DataTransferObjects;

use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

use Domain\Payment\Enums\Currency;
use Domain\Payment\ValueObjects\Money;
use Illuminate\Http\Request;

class InsertPaymentFormData extends Data
{
    function __construct(
        public readonly string $idempotency_key, 
        public readonly float $amount,
        #[WithCast(EnumCast::class)]
        public readonly Currency $currency,
        public readonly string $merchant_id,
        public readonly string $customer_email,
        public readonly ?array $metadata = null
    ) {}

    static function rules() :array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'customer_email' => 'required|string|email',
        ];
    }
}