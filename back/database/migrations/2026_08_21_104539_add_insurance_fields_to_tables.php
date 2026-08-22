<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('insurance_amount', 10, 2)->default(0)->after('rent_price_daily');
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->decimal('total_insurance', 10, 2)->default(0)->after('total_price');
            $table->enum('insurance_status', ['held', 'refunded', 'forfeited'])->default('held')->after('total_insurance');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('insurance_amount');
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn(['total_insurance', 'insurance_status']);
        });
    }
};