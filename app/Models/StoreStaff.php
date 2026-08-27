<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreStaff extends Model
{
    protected $table = 'store_staff';
    protected $primaryKey = 'staff_id';

    protected $fillable = [
        'store_id',
        'name',
        'email',
        'phone',
        'role',
        'status',
        'profile_photo_path',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    public function getRouteKeyName()
    {
        return 'staff_id';
    }
}
