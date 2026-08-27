<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE survey_questions
            MODIFY COLUMN type ENUM('text', 'textarea', 'rating', 'multiple_choice', 'image_attachment') NOT NULL
        ");

        Schema::table('feedback_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('feedback_answers', 'answer_attachment')) {
                $table->string('answer_attachment')->nullable()->after('answer_text');
            }
        });
    }

    public function down(): void
    {
        DB::table('survey_questions')
            ->where('type', 'image_attachment')
            ->update(['type' => 'text']);

        Schema::table('feedback_answers', function (Blueprint $table) {
            if (Schema::hasColumn('feedback_answers', 'answer_attachment')) {
                $table->dropColumn('answer_attachment');
            }
        });

        DB::statement("
            ALTER TABLE survey_questions
            MODIFY COLUMN type ENUM('text', 'textarea', 'rating', 'multiple_choice') NOT NULL
        ");
    }
};
