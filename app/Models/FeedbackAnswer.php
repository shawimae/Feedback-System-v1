<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackAnswer extends Model
{
    protected $table = 'feedback_answers';
    protected $primaryKey = 'answer_id';

    protected $fillable = [
        'feedback_id',
        'question_id',
        'question_snapshot',
        'question_type_snapshot',
        'answer_text',
        'answer_attachment',
        'answer_comment',
        'answer_rating',
    ];

    public function getDisplayQuestionAttribute(): string
    {
        return $this->question_snapshot
            ?? $this->question?->question
            ?? 'Feedback item';
    }

    public function getDisplayQuestionTypeAttribute(): ?string
    {
        return $this->question_type_snapshot
            ?? $this->question?->type;
    }

    public function feedback()
    {
        return $this->belongsTo(Feedback::class, 'feedback_id', 'feedback_id');
    }

    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id', 'question_id');
    }

    public function getRouteKeyName()
    {
        return 'answer_id';
    }
}
