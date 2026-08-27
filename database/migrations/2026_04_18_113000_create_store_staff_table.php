<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_staff', function (Blueprint $table) {
            $table->id('staff_id');
            $table->unsignedBigInteger('store_id');
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->foreign('store_id')
                ->references('store_id')
                ->on('stores')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_staff');
    }
};
