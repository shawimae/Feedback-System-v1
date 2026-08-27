<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id('store_id');
            $table->string('store_number')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('store_manager')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->string('qr_pdf_path')->nullable();
            $table->text('qr_url')->nullable();
            $table->string('google_review_url')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};