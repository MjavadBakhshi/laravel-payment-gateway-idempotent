<?php

namespace Tests\Feature\Payment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Payment\Concrns\PaymentBase;

use Domain\Payment\Enums\PaymentStatus;
use Domain\Escrow\Models\EscrowHold;
use Domain\Payment\Models\Payment;
use Domain\Payment\ValueObjects\TransactionId;

class WebhookTest extends PaymentBase
{
    use RefreshDatabase;

    #[Test]
    public function webhook_changes_payment_from_pending_to_held()
    {
        // Make a payment request
        $response = $this->makePayment($this->validPayload);
        $paymentData = $response->json('data.payment');

        // Simulate webhook as success payment
        $webhookResponse = $this->simulateBankWebhook($paymentData, 'success');
        $webhookResponse->assertStatus(200);

        // Verfiy payment record is in Held status and the escrow record has been created ?

        $this->assertDatabaseHas('payments', [
            'transaction_id' => $paymentData['transaction_id'],
            'status' => PaymentStatus::Held,
        ]);

        // Verify escrow record has been created ?
        $this->assertTrue(
            Payment::whereHas('escrow', fn($q) => $q->whereNotNull('held_at'))
            ->where(
                'transaction_id', 
                $paymentData['transaction_id']
            )
            ->exists()
        );
        
    }

    #[Test]
    public function webhook_marks_payment_as_failed()
    {
        // Make a payment request
        $response = $this->makePayment($this->validPayload);
        $paymentData = $response->json('data.payment');

        // Simulate webhook as failed payment
        $this->simulateBankWebhook($paymentData, 'failed');
        // Verfiy payment record is in Held status and the escrow record has been created ?

        $this->assertDatabaseHas('payments', [
            'transaction_id' => $paymentData['transaction_id'],
            'status' => PaymentStatus::Failed,
        ]);

        // Verify escrow record has been created ?
        $this->assertTrue(
            Payment::whereDoesntHave('escrow')
            ->where(
                'transaction_id', 
                $paymentData['transaction_id']
            )
            ->exists()
        );

    }

    #[Test]
    public function webhook_ignores_already_processed_payment() 
    {
        // Make a payment request
        $response = $this->makePayment($this->validPayload);
        $paymentData = $response->json('data.payment');

        // Bank confirmed the payment
        $webhookResponse = $this->simulateBankWebhook($paymentData, 'success');
        $webhookResponse->assertStatus(200);
        // Keep held_at
        $payment = Payment::with('escrow')
                    ->whereTransactionId(
                        TransactionId::fromString($paymentData['transaction_id'])
                    )
                    ->first();
        
        $heldAt = $payment->escrow->held_at;

        // Accidentally bank would confrim the payment again.
        $webhookResponse = $this->simulateBankWebhook($paymentData, 'success');

        // Check the held_at is not touched:
        $escrowHeldAt = EscrowHold::where('payment_id', $payment->id)->value('held_at');
        $this->assertTrue($escrowHeldAt->eq($payment->escrow->held_at));
    }

    #[Test]
    public function webhook_returns_404_for_unknown_transaction() 
    {
        // Make a payment request
        $response = $this->makePayment($this->validPayload);
        $paymentData = $response->json('data.payment');

        // Simulate webhook as failed payment to validate not exist transaction id.
        $paymentData['transaction_id'] = 'TXN_not_exist';
        $webhookResponse = $this->simulateBankWebhook($paymentData, 'failed');
        $webhookResponse->assertStatus(404);
        
        // Validate wrong transaction id format.
        $paymentData['transaction_id'] = 'wrong_format_txn';
        $webhookResponse = $this->simulateBankWebhook($paymentData, 'failed');
        $webhookResponse->assertStatus(422);
    }
}
