<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';
    protected $primaryKey = 'feedback_id';

    protected $fillable = [
        'store_id',
        'selected_staff_id',
        'selected_staff_name',
        'selected_staff_photo_path',
        'transaction_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'overall_rating',
        'overall_comment',
        'is_resolved',
        'resolved_at',
        'admin_reply',
        'admin_replied_at',
        'reward_status',
        'reward_points_pending',
        'reward_points_awarded',
        'review_claim_name',
        'review_claim_notes',
        'review_claim_screenshot',
        'review_claimed_at',
        'reward_approved_at',
        'reward_notified_at',
    ];

    protected $casts = [
        'overall_rating' => 'float',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'admin_replied_at' => 'datetime',
        'review_claimed_at' => 'datetime',
        'reward_approved_at' => 'datetime',
        'reward_notified_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    public function answers()
    {
        return $this->hasMany(FeedbackAnswer::class, 'feedback_id', 'feedback_id');
    }

    public function selectedStaff()
    {
        return $this->belongsTo(StoreStaff::class, 'selected_staff_id', 'staff_id');
    }

    public function getRouteKeyName()
    {
        return 'feedback_id';
    }
}
