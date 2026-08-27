<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->unsignedBigInteger('selected_staff_id')->nullable()->after('store_id');
            $table->string('selected_staff_name')->nullable()->after('selected_staff_id');
            $table->string('selected_staff_photo_path')->nullable()->after('selected_staff_name');

            $table->foreign('selected_staff_id')
                ->references('staff_id')
                ->on('store_staff')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropForeign(['selected_staff_id']);
            $table->dropColumn([
                'selected_staff_id',
                'selected_staff_name',
                'selected_staff_photo_path',
            ]);
        });
    }
};
