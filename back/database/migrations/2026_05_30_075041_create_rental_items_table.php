<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rental_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->cascadeOnDelete();
            $table->foreignId('lessor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_item_id')->constrained('product_items');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('rent_days');
            $table->decimal('unit_price', 10, 2);
            $table->string('status')->default('rented');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_items');
    }
};
