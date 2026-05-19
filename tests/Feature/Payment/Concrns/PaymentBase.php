<?php

namespace Tests\Feature\Payment\Concrns;

use Illuminate\Support\Str;
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

    protected function generateNewIdempotentKey()
    {
        $this->idempotencyKey = Str::uuid()->toString();
    }

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

    protected function simulateBankWebhook(array $paymentData, string $bankStatus)
    {
        $webhookData = [
            'transaction_id' => $paymentData['transaction_id'],
            'status' => $bankStatus,
            'amount' => $paymentData['amount'],
        ];

        return $this->post(route('api.payment.bank-webhook', $webhookData));
    }
}