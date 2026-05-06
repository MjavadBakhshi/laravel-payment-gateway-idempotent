<?php

namespace Domain\Payment\ViewModels;

use Domain\Payment\Models\Payment;
use Domain\Shared\ViewModels\ViewModel;

class PaymentViewModel extends ViewModel
{
    function __construct(protected readonly Payment $payment) {}

    function payment() :array
    {
         return [
            'transaction_id' => $this->payment->transaction_id->value,
            'status' => $this->payment->status->value,
            'amount' => $this->payment->amount->amountInDecimal(),
            'currency' => $this->payment->currency->value,
            'created_at' => $this->payment->created_at->format('Y-m-d H:i:s'),
            'customer_email' => $this->payment->customer_email,
            'merchant_id' => $this->payment->merchant_id,
        ];
    }
}