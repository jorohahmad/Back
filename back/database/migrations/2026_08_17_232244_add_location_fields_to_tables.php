<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('governorate')->nullable()->after('description');
            $table->string('office')->nullable()->after('governorate');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('receive_governorate')->nullable()->after('status');
            $table->string('receive_office')->nullable()->after('receive_governorate');
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->string('receive_governorate')->nullable()->after('status');
            $table->string('receive_office')->nullable()->after('receive_governorate');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['governorate', 'office']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['receive_governorate', 'receive_office']);
        });
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn(['receive_governorate', 'receive_office']);
        });
    }
};