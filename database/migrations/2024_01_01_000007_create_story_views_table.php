<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('watched_full')->default(false);
            $table->boolean('liked')->default(false);
            $table->boolean('shared')->default(false);
            $table->boolean('commented')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->string('device_id')->nullable();
            $table->boolean('reward_granted')->default(false);
            $table->timestamps();

            $table->unique(['story_id', 'user_id']); // ضدتقلب: هر کاربر یک‌بار برای هر استوری
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_views');
    }
};
