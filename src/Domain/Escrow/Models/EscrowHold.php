<?php

namespace Domain\Escrow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

use Domain\Payment\Models\Payment;
use Domain\Payment\ValueObjects\Money;

class EscrowHold extends Model
{

    public $timestamps = false;

    protected $guarded = ['id'];
    protected $with = ['payment'];  // ← Automatically eager-loads payment

    protected $casts = [
        'amount_in_cents' => 'integer',
        'seller_amount_in_cents' => 'integer',
        'platform_amount_in_cents' => 'integer',
        'held_at' => 'datetime',
        'released_at' => 'datetime',
        'refunded_at' => 'datetime',
        'disputed_at' => 'datetime',
        'auto_release_at' => 'datetime',
    ];

    /**
     * Get the payment that owns this escrow hold.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // Getters/Setters

    function amount() :Attribute
    {
        return Attribute::make(
            get: fn() => new Money($this->amount_in_cents, $this->payment->currency)
        );
    }

    function sellerAmount() :Attribute
    {
        return Attribute::make(
            get: fn() => new Money($this->seller_amount_in_cents, $this->payment->currency)
        );
    }

    function platformAmount() :Attribute
    {
        return Attribute::make(
            get: fn() => new Money($this->platform_amount_in_cents, $this->payment->currency)
        );
    }
}