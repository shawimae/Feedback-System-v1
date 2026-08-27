<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('managed_by_user_id')
                ->nullable()
                ->after('assigned_store_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('software_license_id')
                ->nullable()
                ->after('managed_by_user_id')
                ->constrained('software_licenses')
                ->nullOnDelete();
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->foreignId('owner_user_id')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('software_license_id');
            $table->dropConstrainedForeignId('managed_by_user_id');
        });
    }
};
