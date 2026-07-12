<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // هر غرفه یا فروشگاهی است یا خدماتی؛ برای هر دو باید دو غرفه جدا ساخته شود
            $table->enum('type', ['product', 'service'])->default('product')->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
