<?php

namespace App\Http\Controllers\API\V1\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store() 
    {
       return $this->successResponse(
            data: [
                'idempotent' => false
            ],      
            message: 'Payment created successfully',
            status: 201
       );
    }
}
