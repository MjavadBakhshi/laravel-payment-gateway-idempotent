<?php

namespace Domain\Payment\Builders;

use Illuminate\Database\Eloquent\Builder;

use Domain\Payment\Models\Payment;
use Domain\Payment\ValueObjects\TransactionId;

class PaymentBuilder extends Builder
{
    static function getByTransactionId(TransactionId $transactionId) :?Payment
    {
        return Payment::where(
                    'transaction_id', 
                    $transactionId->value
                )->first();
    }

    function whereTransactionId(TransactionId $transactionId) :static
    {
        return $this->where(
            'transaction_id', 
            $transactionId->value
        );
    }
}