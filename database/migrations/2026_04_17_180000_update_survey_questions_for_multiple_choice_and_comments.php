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
            MODIFY COLUMN type ENUM('text', 'textarea', 'rating', 'multiple_choice') NOT NULL
        ");

        Schema::table('survey_questions', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_questions', 'options')) {
                $table->json('options')->nullable()->after('type');
            }

            if (!Schema::hasColumn('survey_questions', 'applies_to')) {
                $table->string('applies_to', 20)->default('overall_service')->after('options');
            }
        });

        Schema::table('feedback_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('feedback_answers', 'answer_comment')) {
                $table->text('answer_comment')->nullable()->after('answer_text');
            }
        });

        DB::statement("
            ALTER TABLE feedbacks
            MODIFY COLUMN overall_rating DECIMAL(3,1) NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE survey_questions
            SET type = 'text'
            WHERE type = 'multiple_choice'
        ");

        Schema::table('survey_questions', function (Blueprint $table) {
            if (Schema::hasColumn('survey_questions', 'applies_to')) {
                $table->dropColumn('applies_to');
            }

            if (Schema::hasColumn('survey_questions', 'options')) {
                $table->dropColumn('options');
            }
        });

        Schema::table('feedback_answers', function (Blueprint $table) {
            if (Schema::hasColumn('feedback_answers', 'answer_comment')) {
                $table->dropColumn('answer_comment');
            }
        });

        DB::statement("
            ALTER TABLE survey_questions
            MODIFY COLUMN type ENUM('text', 'textarea', 'rating') NOT NULL
        ");

        DB::statement("
            UPDATE feedbacks
            SET overall_rating = ROUND(overall_rating)
            WHERE overall_rating IS NOT NULL
        ");

        DB::statement("
            ALTER TABLE feedbacks
            MODIFY COLUMN overall_rating UNSIGNED TINYINT NULL
        ");
    }
};
