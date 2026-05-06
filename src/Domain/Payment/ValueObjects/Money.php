<?php

namespace Domain\Payment\ValueObjects;

use Domain\Payment\Enums\Currency;

class Money
{
    public function __construct(
        private readonly int $amountInCents,
        private readonly Currency $currency = Currency::EUR
    ) {
        if ($amountInCents < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
    }

    public static function fromDecimal(
        float $amount, 
        Currency $currency = Currency::EUR
    ): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public function amountInCents(): int
    {
        return $this->amountInCents;
    }

    public function amountInDecimal(): float
    {
        return $this->amountInCents / 100;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function equals(Money $other): bool
    {
        return $this->amountInCents === $other->amountInCents 
            && $this->currency === $other->currency;
    }

    public function __toString(): string
    {
        return number_format($this->amountInDecimal(), 2) . ' ' . $this->currency->value;
    }
}