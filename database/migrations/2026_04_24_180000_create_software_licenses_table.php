<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_name');
            $table->string('client_name')->nullable();
            $table->string('license_key')->nullable()->unique();
            $table->string('license_status')->default('inactive');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_stores')->nullable();
            $table->text('license_notes')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_licenses');
    }
};
