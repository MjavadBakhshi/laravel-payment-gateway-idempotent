<?php

namespace App\Http\Controllers\API\V1\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Domain\Payment\Actions\InsertPaymentAction;
use Domain\Shared\Exceptions\ActionException;
use Domain\Payment\DataTransferObjects\InsertPaymentFormData;
use Domain\Payment\ViewModels\PaymentViewModel;

class PaymentController extends Controller
{
    public function store(Request $request) 
    {
        // Validate data and make DTO
        $data = InsertPaymentFormData::validateAndCreate([
            ...$request->all(),
            'idempotency_key' => $request->header('Idempotency-Key'),
        ]);

        // Insert payment record by Action class.
        $result = InsertPaymentAction::execute($data);

        // Processing result:

        // 1. Checking failure
        if($result instanceof ActionException) 
        {
            // In case of cache expired or redis stoped working.
            if($result->getMessage() == "PAYMENT_PROCESSED")
            {
                $payment = $result->getData()['payment'];

                return $this->successResponse(
                    data: [
                        ...(new PaymentViewModel($payment))->toArray(),
                        'idempotent' => true,
                    ],
                    message: "The payment has already been processed.",
                    status: 200
                );
            }

            // An error occured.
            return $this->failedResponse(message: $result->getMessage());
        }

        // 2. Everything worked fine.
       return $this->successResponse(
            data: [
                ...(new PaymentViewModel($result))->toArray(),
                'idempotent' => false
            ],      
            message: 'Payment created successfully',
            status: 201
       );
    }
}
