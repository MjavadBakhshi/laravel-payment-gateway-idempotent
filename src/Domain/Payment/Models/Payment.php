<?php

namespace Domain\Payment\Models;

use Domain\Payment\Enums\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

use Domain\Payment\Enums\PaymentStatus;
use Domain\Payment\ValueObjects\Money;
use Domain\Payment\ValueObjects\TransactionId;

class Payment extends Model
{
    protected $fillable = [
        'idempotency_key',
        'transaction_id',
        'merchant_id',
        'customer_email',
        'amount_in_cents',
        'currency',
        'metadata',
    ];

    protected $casts = [
        'amount_in_cents' => 'integer',
        'currency' => Currency::class,
        'metadata' => 'array',
        'status' => PaymentStatus::class,
    ];

    function transactionId() :Attribute
    {
        return Attribute::make(
            get: fn() => TransactionId::fromString($this->attributes['transaction_id'])
        );
    }

    function amount() :Attribute
    {
        return Attribute::make(
            get: fn() => new Money($this->amount_in_cents, $this->currency)
        );
    }
}