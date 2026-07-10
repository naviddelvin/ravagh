<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile', 15)->unique();
            $table->string('email')->nullable()->unique();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->enum('role', ['user', 'shop_owner', 'admin'])->default('user');
            $table->string('avatar')->nullable();
            $table->string('referral_code', 12)->unique();
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
