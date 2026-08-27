<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenewalRequest extends Model
{
    protected $fillable = [
        'user_id',
        'software_license_id',
        'requester_name',
        'requester_email',
        'request_note',
        'status',
        'reviewed_by_user_id',
        'resolution_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function softwareLicense()
    {
        return $this->belongsTo(SoftwareLicense::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
