<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\V1\{
    Payment\PaymentController,
};

Route::prefix('v1')->middleware(['idempotency'])->group(function(){

    // Payment
    Route::post(
        '/payment', 
        [PaymentController::class, 'store']
    )->name('payment.store');

});