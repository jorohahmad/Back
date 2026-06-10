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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_item_id')->nullable()->constrained('product_items')->cascadeOnDelete();
            $table->enum('type', ['sale', 'rent']);
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->date('rent_start_date')->nullable();  
            $table->date('rent_end_date')->nullable();   
            $table->integer('rent_days')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
