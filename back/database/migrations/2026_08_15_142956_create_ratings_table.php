<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            // الشخص الذي يكتب التقييم 
            $table->foreignId('rater_id')->constrained('users')->cascadeOnDelete();
            // الشخص الذي يتلقى التقييم 
            $table->foreignId('rated_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('score')->unsigned(); 
            $table->text('comment')->nullable();
            $table->timestamps();

            //  منع تكرار التقييم لنفس البائع من نفس المستخدم على مستوى قاعدة البيانات
            $table->unique(['rater_id', 'rated_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};