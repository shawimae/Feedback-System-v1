<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPointsLog extends Model
{
    protected $table = 'customer_points_logs';

    protected $fillable = [
        'customer_id',
        'feedback_id',
        'points',
        'reason',
    ];

    /**
     * Relationship: belongs to Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    /**
     * Relationship: belongs to Feedback
     */
    public function feedback()
    {
        return $this->belongsTo(Feedback::class, 'feedback_id', 'feedback_id');
    }
}