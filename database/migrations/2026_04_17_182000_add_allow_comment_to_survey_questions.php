<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_questions', 'allow_comment')) {
                $table->boolean('allow_comment')->default(false)->after('applies_to');
            }
        });

        DB::table('survey_questions')
            ->whereIn('type', ['rating', 'multiple_choice'])
            ->update(['allow_comment' => true]);
    }

    public function down(): void
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            if (Schema::hasColumn('survey_questions', 'allow_comment')) {
                $table->dropColumn('allow_comment');
            }
        });
    }
};
