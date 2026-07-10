<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->nullableMorphs('payable'); // Order, Story (خرید تبلیغات/استوری), ServiceBooking و ...
            $table->unsignedBigInteger('amount');
            $table->enum('method', ['wallet', 'gateway']);
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('gateway_ref')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
