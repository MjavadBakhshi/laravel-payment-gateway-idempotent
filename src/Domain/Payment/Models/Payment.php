<?php

namespace Domain\Payment\Models;

use Domain\Escrow\Models\EscrowHold;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

use Domain\Payment\Builders\PaymentBuilder;
use Domain\Payment\Enums\{Currency, PaymentStatus};
use Domain\Payment\ValueObjects\{Money, TransactionId};
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected static string $builder = PaymentBuilder::class;

    protected $fillable = [
        'idempotency_key',
        'transaction_id',
        'merchant_id',
        'customer_email',
        'amount_in_cents',
        'currency',
        'metadata',
        'status'
    ];

    protected $casts = [
        'amount_in_cents' => 'integer',
        'currency' => Currency::class,
        'metadata' => 'array',
        'status' => PaymentStatus::class,
    ];

    // Relations

    function escrow() :HasOne
    {
        return $this->hasOne(EscrowHold::class);
    }

    // Getters/Setters
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

    // helper methods

    function isPending() :bool
    {
        return $this->status == PaymentStatus::Pending;
    }
}