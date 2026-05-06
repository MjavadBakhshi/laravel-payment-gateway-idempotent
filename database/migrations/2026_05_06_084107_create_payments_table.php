<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Domain\Payment\Enums\{Currency, PaymentStatus};

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Idempotency
            $table->string('idempotency_key')->unique();
            
            // Transaction identifiers
            $table->string('transaction_id')->unique();
            $table->string('merchant_id');
            $table->string('customer_email');
            
            // Money fields (store in cents to avoid floating point errors)
            $table->bigInteger('amount_in_cents');
            
            $allCurrencies = collect(Currency::cases())
                                ->pluck('value')->toArray();
                                
                                
            $table->enum('currency', $allCurrencies)->default(Currency::EUR->value);
                                
            $allStatus = collect(PaymentStatus::cases())
                                ->pluck('value')->toArray();
            // Status tracking
            $table->enum('status', $allStatus)->default(PaymentStatus::Pending->value);
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('merchant_id');
            $table->index('customer_email');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};