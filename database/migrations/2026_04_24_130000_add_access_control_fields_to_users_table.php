<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('dev')->after('phone');
            $table->foreignId('assigned_store_id')
                ->nullable()
                ->after('role')
                ->constrained('stores', 'store_id')
                ->nullOnDelete();
            $table->json('feature_access')->nullable()->after('assigned_store_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_store_id');
            $table->dropColumn(['role', 'feature_access']);
        });
    }
};
