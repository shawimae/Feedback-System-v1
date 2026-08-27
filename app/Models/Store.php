<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $table = 'stores';
    protected $primaryKey = 'store_id';

    protected $fillable = [
        'store_number',
        'name',
        'slug',
        'store_manager',
        'store_type',
        'store_type_other',
        'email',
        'phone',
        'address',
        'profile_photo_path',
        'qr_code_path',
        'qr_pdf_path',
        'qr_url',
        'google_review_url',
        'status',
        'owner_user_id',
    ];

    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class, 'store_id', 'store_id')
            ->orderBy('sort_order');
    }

    public function feedbacks()
    {
        return $this->hasMany(\App\Models\Feedback::class, 'store_id', 'store_id');
    }

    public function staffMembers()
    {
        return $this->hasMany(\App\Models\StoreStaff::class, 'store_id', 'store_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function getRouteKeyName()
    {
        return 'store_id';
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo_path) {
            return null;
        }

        return '/storage/' . ltrim($this->profile_photo_path, '/');
    }
}
