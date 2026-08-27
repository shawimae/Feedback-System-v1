<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $table = 'notification_logs';

    protected $fillable = [
        'customer_id',
        'feedback_id',
        'channel',
        'recipient',
        'subject',
        'message',
        'status',
        'error_message',
        'notification_type',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function feedback()
    {
        return $this->belongsTo(Feedback::class, 'feedback_id', 'feedback_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
}
