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
            if (!Schema::hasColumn('survey_questions', 'allow_attachment')) {
                $table->boolean('allow_attachment')->default(false)->after('allow_comment');
            }
        });

        DB::table('survey_questions')
            ->where('type', 'image_attachment')
            ->update([
                'type' => 'text',
                'allow_attachment' => true,
            ]);

        DB::statement("
            ALTER TABLE survey_questions
            MODIFY COLUMN type ENUM('text', 'textarea', 'rating', 'multiple_choice') NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE survey_questions
            MODIFY COLUMN type ENUM('text', 'textarea', 'rating', 'multiple_choice', 'image_attachment') NOT NULL
        ");

        Schema::table('survey_questions', function (Blueprint $table) {
            if (Schema::hasColumn('survey_questions', 'allow_attachment')) {
                $table->dropColumn('allow_attachment');
            }
        });
    }
};
