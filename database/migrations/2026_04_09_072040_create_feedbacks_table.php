<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id('feedback_id');
            $table->unsignedBigInteger('store_id');

            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();

            $table->unsignedTinyInteger('overall_rating')->nullable();
            $table->text('overall_comment')->nullable();

            $table->timestamps();

            $table->foreign('store_id')
                ->references('store_id')
                ->on('stores')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};