<?php

namespace Domain\Payment\Actions;

use Illuminate\Support\Facades\DB;

use Domain\Payment\DataTransferObjects\WebhookData;
use Domain\Payment\Enums\PaymentStatus;
use Domain\Payment\Models\Payment;

class SetPaymentAsFailedAction
{
    static function execute(
        WebhookData $data, 
        Payment $payment
    ) :bool
    {
        try {
            DB::beginTransaction();

            $payment = Payment::lockForUpdate()
                        ->where('status', PaymentStatus::Pending)
                        ->whereTransactionId($data->transaction_id)
                        ->first();

            // Update payment status to Failed
            $payment->update([
                'status' => PaymentStatus::Failed,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'webhook_received_at' => now()->toIso8601String(),
                ])
            ]);

            DB::commit();
            return true;
        }       
        catch(\Exception $e)
        {
            DB::rollBack();
            return false;
        }
    }

}