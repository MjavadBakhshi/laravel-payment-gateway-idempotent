<?php

namespace Domain\Payment\ValueObjects;

class TransactionId 
{
    private function __construct(
        public readonly string $value
    ) {}

    public static function generate(): self
    {
        return new self('TXN_' . strtoupper(uniqid()));
    }

    public static function fromString(string $value): self
    {
        if (!str_starts_with($value, 'TXN_')) {
            throw new \InvalidArgumentException('Invalid transaction ID format');
        }
        return new self($value);
    }

    public function equals(TransactionId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}