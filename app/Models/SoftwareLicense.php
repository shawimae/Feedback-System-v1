<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SoftwareLicense extends Model
{
    protected $fillable = [
        'license_name',
        'client_name',
        'license_key',
        'license_status',
        'starts_at',
        'ends_at',
        'max_stores',
        'license_notes',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_current' => 'boolean',
            'max_stores' => 'integer',
        ];
    }

    public static function resolveStatus(?Carbon $startsAt, ?Carbon $endsAt): string
    {
        if ($endsAt && $endsAt->lte(now())) {
            return 'expired';
        }

        return 'active';
    }

    public function syncResolvedStatus(): bool
    {
        $resolvedStatus = static::resolveStatus($this->starts_at, $this->ends_at);

        if ($this->license_status === $resolvedStatus) {
            return false;
        }

        $this->forceFill([
            'license_status' => $resolvedStatus,
        ])->save();

        return true;
    }

    public static function syncAllResolvedStatuses(): void
    {
        static::query()->get()->each->syncResolvedStatus();
    }
}
