<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_id')->constrained('users')->cascadeOnDelete();
            $table->nullableMorphs('trigger'); // سفارش محصول یا رزرو خدمتی که پاداش را فعال کرده
            $table->unsignedBigInteger('referrer_amount');
            $table->unsignedBigInteger('referred_amount');
            $table->timestamps();

            $table->unique('referred_id'); // هر کاربر معرفی‌شده فقط یک‌بار پاداش می‌دهد
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
    }
};
