<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('booking_number')->unique();
            $table->string('service_name'); // نگهداری نام خدمت در لحظه رزرو
            $table->dateTime('scheduled_at'); // زمان نوبت
            $table->unsignedInteger('duration_minutes');
            $table->unsignedBigInteger('total_amount'); // مبلغ خدمت در لحظه رزرو
            $table->enum('payment_method', ['wallet', 'gateway'])->default('gateway');
            $table->boolean('is_paid')->default(false);
            $table->boolean('cashback_applied')->default(false);
            // ثبت شد -> تایید شد -> انجام شد -> لغو (مشابه چرخه سفارش، برای نوبت‌دهی)
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('reminder_sent_at')->nullable(); // یادآوری خودکار نوبت
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
