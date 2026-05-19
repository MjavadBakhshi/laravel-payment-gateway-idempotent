<?php

namespace Domain\Escrow\Actions;

use Illuminate\Support\Facades\DB;

use Domain\Payment\Enums\PaymentStatus;
use Domain\Shared\Exceptions\ActionException;
use Domain\Escrow\Models\EscrowHold;
use Domain\Payment\Models\Payment;

class ReleaseExpiredEscrowAction
{
    static function execute(int $batchSize = 50)
    {
        EscrowHold::select('id')
        ->whereHas(
            'payment', 
            fn($q) => $q->where('status', PaymentStatus::Held->value)
        )
        ->whereNull('released_at')
        ->whereNull('refunded_at')
        ->whereNull('disputed_at')
        ->where('auto_release_at', '<=', now())
        ->chunkById(
            $batchSize,
            function($escrowIds) {
                DB::beginTransaction();
                    EscrowHold::with([
                        'payment' => fn($q) => $q->lockForUpdate()
                    ])
                    ->whereIn('id', $escrowIds->pluck('id'))
                    ->lockForUpdate()
                    ->get()
                    ->each(
                        fn($escrowHold) => 
                        self::release($escrowHold)
                    );
                DB::commit();
            }
        );
    }

    private static function release(
        EscrowHold &$escrowHold
    )
    {
        try {
            DB::beginTransaction();

                // Lock the escrow and payment record to prevent race conditon 
                // and make the result determenistic.
                // $escrowHold = EscrowHold::lockForUpdate()->find($escrowHold);
                // $payment = Payment::lockForUpdate()->find($escrowHold->payment_id);
                $payment = $escrowHold->payment;

                // Checking can release amount?
                self::canRelease($payment, $escrowHold);

                // Release with split amounts
                
                //TODO: it will be fetch from settings instead of hard coded.
                $platformRate = 0.05;
                $platformAmount = (int) round($escrowHold->amount->amountInCents() * $platformRate);
                $sellerAmount = $escrowHold->amount->amountInCents() - $platformAmount;

                $escrowHold->update([
                    'seller_amount_in_cents' => $sellerAmount,
                    'platform_amount_in_cents' => $platformAmount,
                    'released_at' => now(),
                ]);

                // Change the payment status to completed
                $payment->update([
                    'status' => PaymentStatus::Completed
                ]);

            DB::commit();
        }
        catch(\Exception|ActionException $e)
        {
            DB::rollBack();
            logger()->error('Escrow processing failed', [
                'id' => $escrowHold->id,
                'message' => $e->getMessage()
            ]);
        }
    }

    private static function canRelease(Payment &$payment, EscrowHold &$escrowHold)
    {
        // Preventing release amount if the money has been refunded.
        throw_if(
            $payment->status == PaymentStatus::Refunded,
            new ActionException("The payment has been refunded!")
        );

        // Preventing release amount if the payment is under dispute process.
        throw_if(
            $payment->status == PaymentStatus::Disputed,
            new ActionException("The payment is under dispute process!")
        );

        // Checking the released at is expired
        // and the record has not already released.

        throw_if(
            $payment->status == PaymentStatus::Completed,
            new ActionException("The payment status is already completed and cannot be released.")
        );

        throw_if(
            !is_null($escrowHold->released_at),
            new ActionException("Escrow already released.")
        );

        throw_if(
            $escrowHold->auto_release_at->gt(now()),
            new ActionException("Auto-release date not yet reached")
        );
    }
}