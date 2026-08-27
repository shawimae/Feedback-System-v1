<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'total_points',
    ];

    /**
     * Relationship: Customer has many feedbacks
     */
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'customer_email', 'email');
    }

    /**
     * Relationship: Customer has many points logs
     */
    public function pointsLogs()
    {
        return $this->hasMany(CustomerPointsLog::class, 'customer_id', 'customer_id');
    }
}