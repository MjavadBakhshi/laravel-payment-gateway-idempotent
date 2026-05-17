<?php

namespace Tests\Feature\Payment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;

use Domain\Payment\Enums\PaymentStatus;
use Tests\Feature\Payment\Concrns\PaymentBase;

class InsertPaymentTest extends PaymentBase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_payment_with_valid_data()
    {
        $this->makePayment($this->validPayload)
        ->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'payment' => [
                    'transaction_id',
                    'amount',
                    'merchant_id',
                    'customer_email',
                    'created_at',
                    'currency'
                ]
            ]
        ]);

        $this->assertDatabaseHas('payments', [
            'merchant_id' => 'ikea',
            'customer_email' => 'customer@example.com',
            'amount_in_cents' => 9999,
            'currency' => 'EUR',
            'status' => PaymentStatus::Pending->value
        ]);
    }

    #[Test]
    public function it_stores_amount_in_cents_correctly()
    {
        $this->makePayment([
            ...$this->validPayload,
            'amount' => 49.32,
            'currency' => 'USD',
        ]);

        $this->assertDatabaseHas('payments', [
            'amount_in_cents' => 4932,
            'currency' => 'USD'
        ]);

    }

    #[Test]
    public function it_returns_validation_error_when_amount_is_missing()
    {
        unset($this->validPayload['amount']);

        $this->makePayment($this->validPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function it_returns_validation_error_when_amount_is_negative()
    {
        $this->validPayload['amount'] = -10.00;

        $this->makePayment($this->validPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }
            
    #[Test]
    public function it_returns_validation_error_when_merchant_id_is_missing()
    {  
        unset($this->validPayload['merchant_id']);

        $this->makePayment($this->validPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['merchant_id']);
    }

    #[Test]
    public function it_returns_validation_error_when_customer_email_is_invalid()
    {
        $this->validPayload['customer_email'] = 'HelloWorld@';

        $this->makePayment($this->validPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email']);

    }

    #[Test]
    public function it_returns_validation_error_when_currency_is_invalid()
    {
        $this->validPayload['currency'] = 'Yen';

        $this->makePayment($this->validPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['currency']);

    }

    #[Test]
    public function it_generates_unique_transaction_id_for_each_payment()
    {
        $response1 = $this->makePayment($this->validPayload);
        $this->idempotencyKey = 'second-payment-1234';
        $response2 = $this->makePayment($this->validPayload);

        $this->assertDatabaseCount('payments', 2);

        $this->assertNotEquals(
            $response1->json('data.payment.transaction_id'),
            $response2->json('data.payment.transaction_id')
        );
    }   

    #[Test]
    public function it_returns_correct_amount_format_in_response()
    {
        $response = $this->makePayment($this->validPayload);

        $response->assertJsonPath('data.payment.amount', 99.99);
        $response->assertJsonPath('data.payment.currency', 'EUR');
    }

    #[Test]
    public function it_sets_default_status_to_pending()
    {
        $this->makePayment($this->validPayload)
                ->assertJsonPath('data.payment.status', PaymentStatus::Pending->value);
    }

}

