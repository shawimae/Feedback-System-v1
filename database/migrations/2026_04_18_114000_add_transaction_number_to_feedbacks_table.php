<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            if (!Schema::hasColumn('feedbacks', 'transaction_number')) {
                $table->string('transaction_number')->nullable()->after('store_id');
            }
        });

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->unique('transaction_number');
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropUnique(['transaction_number']);
            $table->dropColumn('transaction_number');
        });
    }
};
