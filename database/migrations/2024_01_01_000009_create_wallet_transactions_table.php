<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit'])->default('credit');
            $table->unsignedBigInteger('amount');
            // منبع تراکنش: reward, charge, order_payment, service_payment, ad_purchase, withdraw, refund
            $table->string('source');
            $table->nullableMorphs('reference'); // اشاره به مدل مرتبط (سفارش، استوری و ...)
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
