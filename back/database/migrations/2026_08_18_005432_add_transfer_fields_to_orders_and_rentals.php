<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('transfer_status', ['pending', 'onWay', 'reached', 'finished'])->default('pending')->after('status');
            $table->timestamp('expected_arrival_at')->nullable()->after('transfer_status'); // نوع Timestamp ليعمل العداد بدقة
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->enum('transfer_status', ['pending', 'onWay', 'reached', 'finished'])->default('pending')->after('status');
            $table->timestamp('expected_arrival_at')->nullable()->after('transfer_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['transfer_status', 'expected_arrival_at']);
        });
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn(['transfer_status', 'expected_arrival_at']);
        });
    }
};