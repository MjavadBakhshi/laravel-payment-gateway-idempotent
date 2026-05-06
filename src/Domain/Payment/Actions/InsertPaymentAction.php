<?php

namespace Domain\Payment\Actions;

use Domain\Payment\DataTransferObjects\InsertPaymentFormData;
use Domain\Shared\Exceptions\ActionException;
use Domain\Payment\Models\Payment;
use Domain\Payment\ValueObjects\Money;
use Domain\Payment\ValueObjects\TransactionId;
use Illuminate\Support\Facades\DB;

class InsertPaymentAction
{
    static function execute(InsertPaymentFormData $data) :Payment|ActionException
    {
        try {
            DB::beginTransaction();

            $payment = Payment::lockForUpdate()
                            ->where('idempotency_key', $data->idempotency_key)
                            ->first();

            if($payment) 
            {
                DB::commit();
                return new ActionException("PAYMENT_PROCESSED", data:[
                    'payment' => $payment
                ]);
            }
            
            $amount = Money::fromDecimal($data->amount, $data->currency);
          
            $payment = Payment::create([
                ...$data->toArray(),
                'transaction_id' => TransactionId::generate(),
                'amount_in_cents' => $amount->amountInCents(),
                'currency' => $data->currency,
            ]);

            DB::commit();

            return $payment->refresh();
        }
        catch(\Exception $e)
        {
            DB::rollBack();
            return ActionException::from($e);
        }
    }
}