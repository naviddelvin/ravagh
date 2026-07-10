<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->string('media_url');
            $table->enum('media_type', ['image', 'video'])->default('image');
            // تعرفه پرداختی: 100000 | 500000 | 1000000 | 10000000 ریال/تومان
            $table->unsignedBigInteger('amount_paid');
            $table->unsignedBigInteger('platform_share'); // 30%
            $table->unsignedBigInteger('reward_pool_share'); // 70%
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->enum('status', ['active', 'expired'])->default('active');
            $table->timestamp('expires_at'); // زمان انتشار + 24 ساعت
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
