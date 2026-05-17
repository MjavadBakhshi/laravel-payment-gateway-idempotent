<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Domain\Payment\Enums\Currency;
use Domain\Payment\Models\Payment;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('escrow_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Payment::class)->constrained();
            
            $allCurrencies = collect(Currency::cases())
                    ->pluck('value')->toArray();
                                 
            $table->enum('currency', $allCurrencies)->default(Currency::EUR->value);

            $table->bigInteger('amount_in_cents'); // Original: 10000
            // Split destinations
            $table->bigInteger('seller_amount_in_cents')->nullable();  // 9500
            $table->bigInteger('platform_amount_in_cents')->nullable(); // 500

            $table->timestamp('held_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('disputed_at')->nullable(); // ← Active dispute flag
            $table->timestamp('auto_release_at')->nullable();

            $table->unique('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escrow_holds');
    }
};
