<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    protected $table = 'survey_questions';
    protected $primaryKey = 'question_id';

    protected $fillable = [
        'question',
        'type',
        'options',
        'applies_to',
        'allow_comment',
        'allow_attachment',
        'sort_order',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'allow_comment' => 'boolean',
        'allow_attachment' => 'boolean',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getAppliesToLabelAttribute(): string
    {
        return match ($this->applies_to) {
            'overall_service' => 'Overall/Service',
            'staff' => 'Staff',
            'manager' => 'Manager',
            default => 'Overall/Service',
        };
    }

    public function getRouteKeyName()
    {
        return 'question_id';
    }
}
