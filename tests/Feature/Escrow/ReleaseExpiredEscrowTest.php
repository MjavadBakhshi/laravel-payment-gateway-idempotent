<?php

namespace Tests\Feature\Escrow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Payment\Concrns\PaymentBase;

use Domain\Payment\Enums\PaymentStatus;
use Domain\Payment\Models\Payment;
use Domain\Payment\ValueObjects\TransactionId;

class ReleaseExpiredEscrowTest extends PaymentBase
{
    use RefreshDatabase;

    #[Test]
    public function it_releases_expired_escrow_with_split_amounts() 
    {
        // Making two success payments (pending -> held)
        $this->generateNewIdempotentKey();
        $paymentAResponse = $this->makePayment($this->validPayload);
        $transactionIdA = $paymentAResponse->json('data.payment.transaction_id');
        
        $this->generateNewIdempotentKey();
        $paymentBResponse = $this->makePayment($this->validPayload);
        $transactionIdB = $paymentBResponse->json('data.payment.transaction_id');
        
        // Confirming payments by bank webhook.
        foreach(
            [$paymentAResponse->json('data.payment'), 
            $paymentBResponse->json('data.payment')] 
            as $paymentData
        ) {
            $this->simulateBankWebhook(
                $paymentData,
                'success'
            );
        }

        // Now moving time forward for 6 days
        // Auto released is done after 5 days.
        $this->travel(6)->days();

        // Calling cron job command
        Artisan::call('escrow:release-expired');

        // Asseerting payment status of records have been changed to completed
        $releasedPayments = 
        Payment::with('escrow')
        ->whereIn(
        'transaction_id', [
            $transactionIdA,
            $transactionIdB
        ])
        ->where('status', PaymentStatus::Completed)
        ->whereHas('escrow', fn($q) => $q->whereNotNull('released_at'))
        ->get();

        $this->assertEquals(2, $releasedPayments->count());


        // Asserting split amount of records have been done correctlly.
        foreach($releasedPayments as $payment)
        {
            $amount = $payment->amount->amountIncents();
            // Imagine platform rate is 5%
            $platformAMount = (int) round($amount * 0.05);
            $sellerAmount = $amount - $platformAMount;

            $this->assertEquals(
                [
                    $platformAMount, 
                    $sellerAmount
                ],
                [
                    $payment->escrow->platform_amount->amountInCents(), 
                    $payment->escrow->seller_amount->amountIncents()
                ]
            );
        }
    }

    #[Test]
    public function it_does_not_release_non_expired_escrow() 
    {
        // Making a payment
        $this->generateNewIdempotentKey();
        $paymentAResponse = $this->makePayment($this->validPayload);
        $paymentData = $paymentAResponse->json('data.payment');
        $transactionIdA = $paymentAResponse->json('data.payment.transaction_id');
        
        // Confirming
        $this->simulateBankWebhook(
            $paymentData,
            'success'
        );

        // Try to release non-expired payment
        Artisan::call('escrow:release-expired');

        // The payment record status must be untouchted.
        $this->assertFalse(
            Payment::whereTransactionid(
                TransactionId::fromString($transactionIdA)
            )
            ->whereHas('escrow', fn($q) => $q->whereNull('released_at'))
            ->where('status', PaymentStatus::Completed)
            ->exists()
        );

    }

    #[Test]
    public function it_does_not_release_already_refunded_escrow() 
    {
         // Making a payment
        $this->generateNewIdempotentKey();
        $paymentAResponse = $this->makePayment($this->validPayload);
        $paymentData = $paymentAResponse->json('data.payment');
        $transactionIdA = $paymentAResponse->json('data.payment.transaction_id');
        
        // Confirming
        $this->simulateBankWebhook(
            $paymentData,
            'success'
        );

        // Moving forward to make the record released.
        $this->travel(6)->days();

        // Try to release non-expired payment
        Artisan::call('escrow:release-expired');

        $payment =  
        Payment::with('escrow')
        ->whereTransactionid(
            TransactionId::fromString($transactionIdA)
        )
        ->whereHas('escrow', fn($q) => $q->whereNotNull('released_at'))
        ->where('status', PaymentStatus::Completed)
        ->first();

        // The record exists so the relase process has been done successfully.
        $this->assertTrue(!is_null($payment));

        // Keep the release date to compare.
        $releasedDate = $payment->escrow->released_at;
       
        // Moving forward to retry to release that record.
        $this->travel(5)->hours();

        // Try to release again.
        Artisan::call('escrow:release-expired');

        // refetch the payment
        $payment->fresh();
        // The relased_at field must be untouched after release again.
        $this->assertEquals($releasedDate, $payment->escrow->released_at);
    }
}
