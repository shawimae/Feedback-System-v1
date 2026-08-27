<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_answers', function (Blueprint $table) {
            $table->text('question_snapshot')->nullable()->after('question_id');
            $table->string('question_type_snapshot', 50)->nullable()->after('question_snapshot');
        });

        DB::table('feedback_answers')
            ->join('survey_questions', 'feedback_answers.question_id', '=', 'survey_questions.question_id')
            ->update([
                'feedback_answers.question_snapshot' => DB::raw('survey_questions.question'),
                'feedback_answers.question_type_snapshot' => DB::raw('survey_questions.type'),
            ]);
    }

    public function down(): void
    {
        Schema::table('feedback_answers', function (Blueprint $table) {
            $table->dropColumn(['question_snapshot', 'question_type_snapshot']);
        });
    }
};
