<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // إضافة الرصيد بقيمة افتراضية 0 (استخدمنا decimal لضمان دقة العمليات المالية)
            $table->decimal('balance', 12, 2)->default(0)->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }
};