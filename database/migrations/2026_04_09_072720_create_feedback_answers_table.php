<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_answers', function (Blueprint $table) {
            $table->id('answer_id');

            $table->unsignedBigInteger('feedback_id');
            $table->unsignedBigInteger('question_id');

            $table->text('answer_text')->nullable();
            $table->unsignedTinyInteger('answer_rating')->nullable();

            $table->timestamps();

            $table->foreign('feedback_id')
                ->references('feedback_id')
                ->on('feedbacks')
                ->cascadeOnDelete();

            $table->foreign('question_id')
                ->references('question_id')
                ->on('survey_questions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_answers');
    }
};