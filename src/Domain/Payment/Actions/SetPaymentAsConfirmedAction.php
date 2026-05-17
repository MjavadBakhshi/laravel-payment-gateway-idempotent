<?php

namespace Domain\Payment\Actions;

use Domain\Escrow\Models\EscrowHold;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

use Domain\Payment\DataTransferObjects\WebhookData;
use Domain\Payment\Enums\PaymentStatus;
use Domain\Payment\Models\Payment;
use Domain\Shared\Exceptions\ActionException;

class SetPaymentAsConfirmedAction 
{
    static function execute(
        WebhookData $data, 
        Payment $payment
    ) :bool|ActionException
    {
        try {
            DB::beginTransaction();

            $payment = Payment::lockForUpdate()
                        ->where('status', PaymentStatus::Pending)
                        ->whereTransactionId($data->transaction_id)
                        ->first();

                // Update payment status to HELD
            $payment->update([
                'status' => PaymentStatus::Held,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'bank_reference' => $data->bank_refrence,
                    'webhook_received_at' => now()->toIso8601String(),
                ])
            ]);

            // Create escrow hold record
            $payment->escrow()->save(new EscrowHold([
                'amount_in_cents' => $payment->amount_in_cents,
                'currency' => $payment->currency,
                'held_at' => now(),
                'auto_release_at' => now()->addDays(5),
            ]));

            DB::commit();
            return true;
        }
        catch (QueryException $e) {
            // Check if error code is for duplicate entry (MySQL error 1062)
            if ($e->errorInfo[1] == 1062) {
                // Escrow record already exists, fetch it
                DB::rollBack();
                return new ActionException('DUPLICATE_ESCROW_RECORD');
            } else {
                throw $e;
            }
        }
        catch(\Exception $e)
        {
            DB::rollBack();
            return false;
        }
    }
}