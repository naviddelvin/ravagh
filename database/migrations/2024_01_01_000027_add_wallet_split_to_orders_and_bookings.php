<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'wallet_amount')) {
                $table->unsignedBigInteger('wallet_amount')->default(0)->after('total_amount');
            }
            if (! Schema::hasColumn('orders', 'gateway_amount')) {
                $table->unsignedBigInteger('gateway_amount')->default(0)->after('wallet_amount');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'wallet_amount')) {
                $table->unsignedBigInteger('wallet_amount')->default(0)->after('total_amount');
            }
            if (! Schema::hasColumn('bookings', 'gateway_amount')) {
                $table->unsignedBigInteger('gateway_amount')->default(0)->after('wallet_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['wallet_amount', 'gateway_amount']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['wallet_amount', 'gateway_amount']);
        });
    }
};
