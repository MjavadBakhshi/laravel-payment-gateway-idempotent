<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\V1\{
    Payment\PaymentController,
    Payment\WebhookController
};

Route::prefix('v1')
->name('api.')
->group(function(){

    // Payment / making payment
    Route::post(
        '/payment', 
        [PaymentController::class, 'store']
    )
    ->middleware(['idempotency'])
    ->name('payment.store');

    // Payment / bank webhook
    Route::post(
        '/payment/bank-webhook', 
        WebhookController::class
    )->name('payment.bank-webhook');
    
});


