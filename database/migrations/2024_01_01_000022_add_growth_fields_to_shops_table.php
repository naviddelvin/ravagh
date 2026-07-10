<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // درصد کمیسیون این غرفه (در دوره رایگان صفر است)
            $table->unsignedTinyInteger('commission_percent')->default(10)->after('status');
            // پایان دوره ۳ ماهه رایگان (بدون کمیسیون)
            $table->timestamp('trial_ends_at')->nullable()->after('commission_percent');
            // نشان «تأیید شده توسط رواق» — بعد از بررسی مدارک توسط ادمین
            $table->timestamp('verified_at')->nullable()->after('trial_ends_at');
            // باشگاه وفاداری غرفه‌داران
            $table->enum('loyalty_tier', ['bronze', 'silver', 'gold'])->default('bronze')->after('verified_at');
            $table->unsignedBigInteger('loyalty_points')->default(0)->after('loyalty_tier');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['commission_percent', 'trial_ends_at', 'verified_at', 'loyalty_tier', 'loyalty_points']);
        });
    }
};
