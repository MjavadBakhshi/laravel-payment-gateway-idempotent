<?php

namespace Tests\Feature\Payment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InsertPaymentTest extends TestCase
{
    use RefreshDatabase;

    private string $idempotencyKey = 'test-idempotency-key-123';

    private array $validPayload = [
        'amount' => 99.99,
        'currency' => 'EUR',
        'merchant_id' => 'ikea',
        'customer_email' => 'customer@example.com'
    ];

    #[Test]
    public function it_creates_a_payment_with_valid_data()
    {}

    #[Test]
    public function it_stores_amount_in_cents_correctly()
    {}

    #[Test]
    public function it_returns_validation_error_when_amount_is_missing()
    {}

    #[Test]
    public function it_returns_validation_error_when_merchant_id_is_missing()
    {}

    #[Test]
    public function it_returns_validation_error_when_customer_email_is_invalid()
    {}

    #[Test]
    public function it_returns_validation_error_when_currency_is_invalid()
    {}

    #[Test]
    public function it_generates_unique_transaction_id_for_each_payment()
    {}

    #[Test]
    public function it_returns_correct_amount_format_in_response()
    {}

    #[Test]
    public function it_sets_default_status_to_pending()
    {}
}

