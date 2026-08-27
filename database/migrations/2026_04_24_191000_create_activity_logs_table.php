<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('user_role')->nullable();
            $table->string('action', 120);
            $table->text('description');
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('route_name')->nullable();
            $table->string('method', 12)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['user_role', 'created_at']);
            $table->index(['store_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
