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
            $table->dropForeign(['question_id']);
        });

        Schema::table('feedback_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id')->nullable()->change();
            $table->foreign('question_id')
                ->references('question_id')
                ->on('survey_questions')
                ->nullOnDelete();
        });

        $publishedSnapshotExists = DB::table('app_settings')
            ->where('key', 'published_questionnaire_snapshot')
            ->exists();

        if (!$publishedSnapshotExists) {
            $questions = DB::table('survey_questions')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('question_id')
                ->get([
                    'question_id',
                    'question',
                    'type',
                    'sort_order',
                    'is_required',
                    'is_active',
                ])
                ->map(fn ($question) => [
                    'source_question_id' => $question->question_id,
                    'question' => $question->question,
                    'type' => $question->type,
                    'sort_order' => (int) $question->sort_order,
                    'is_required' => (bool) $question->is_required,
                    'is_active' => (bool) $question->is_active,
                ])
                ->values()
                ->all();

            DB::table('app_settings')->insert([
                [
                    'key' => 'published_questionnaire_snapshot',
                    'value' => json_encode($questions, JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'published_questionnaire_title',
                    'value' => DB::table('app_settings')->where('key', 'questionnaire_title')->value('value') ?? 'Feedback Survey',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'questionnaire_last_synced_at',
                    'value' => now()->toDateTimeString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('feedback_answers', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
        });

        Schema::table('feedback_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id')->nullable(false)->change();
            $table->foreign('question_id')
                ->references('question_id')
                ->on('survey_questions')
                ->cascadeOnDelete();
        });

        DB::table('app_settings')
            ->whereIn('key', [
                'published_questionnaire_snapshot',
                'published_questionnaire_title',
                'questionnaire_last_synced_at',
            ])
            ->delete();
    }
};
