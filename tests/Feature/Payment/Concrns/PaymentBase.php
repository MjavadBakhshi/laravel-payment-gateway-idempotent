<?php

namespace Tests\Feature\Payment\Concrns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class PaymentBase extends TestCase
{    
    protected string $idempotencyKey = 'test-idempotency-key-123';

    protected array $validPayload = [
        'amount' => 99.99,
        'currency' => 'EUR',
        'merchant_id' => 'ikea',
        'customer_email' => 'customer@example.com'
    ];


    protected function makePayment(array $data) 
    {
        $response = $this->withHeaders([
            'Idempotency-Key' => $this->idempotencyKey,
        ])
        ->postJson(
            route('api.payment.store'), 
            $data
        );

        return $response;
    }
}