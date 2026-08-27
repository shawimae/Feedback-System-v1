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
    Schema::create('customer_points_logs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('customer_id');
        $table->unsignedBigInteger('feedback_id')->nullable();
        $table->integer('points');
        $table->string('reason')->nullable();
        $table->timestamps();

        $table->foreign('customer_id')
              ->references('customer_id')
              ->on('customers')
              ->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_points_logs');
    }
};
