<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('survey_questions')
            ->where('applies_to', 'general')
            ->update(['applies_to' => 'overall_service']);
    }

    public function down(): void
    {
        DB::table('survey_questions')
            ->where('applies_to', 'overall_service')
            ->update(['applies_to' => 'general']);
    }
};
