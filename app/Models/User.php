<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_DEV = 'dev';
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';

    public const FEATURE_DASHBOARD = 'dashboard';
    public const FEATURE_STORES = 'stores';
    public const FEATURE_STAFF = 'staff';
    public const FEATURE_QUESTIONS = 'questions';
    public const FEATURE_FEEDBACKS = 'feedbacks';
    public const FEATURE_ANALYTICS = 'analytics';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'assigned_store_id',
        'managed_by_user_id',
        'software_license_id',
        'feature_access',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'feature_access' => 'array',
            'password' => 'hashed',
        ];
    }

    public static function roles(): array
    {
        return [
            self::ROLE_DEV => 'Dev',
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_ADMIN => 'Admin',
        ];
    }

    public static function featureLabels(): array
    {
        return [
            self::FEATURE_DASHBOARD => 'Dashboard',
            self::FEATURE_STORES => 'Manage Stores',
            self::FEATURE_STAFF => 'Manage Staff',
            self::FEATURE_QUESTIONS => 'Master Questionnaire',
            self::FEATURE_FEEDBACKS => 'Feedbacks',
            self::FEATURE_ANALYTICS => 'Analytics and Reports',
        ];
    }

    public static function defaultFeatureAccessFor(string $role): array
    {
        return match ($role) {
            self::ROLE_SUPER_ADMIN => [
                self::FEATURE_DASHBOARD,
                self::FEATURE_STORES,
                self::FEATURE_STAFF,
                self::FEATURE_FEEDBACKS,
                self::FEATURE_ANALYTICS,
            ],
            self::ROLE_ADMIN => [
                self::FEATURE_DASHBOARD,
                self::FEATURE_STORES,
                self::FEATURE_STAFF,
                self::FEATURE_FEEDBACKS,
                self::FEATURE_ANALYTICS,
            ],
            default => array_keys(self::featureLabels()),
        };
    }

    public static function normalizeFeatureAccess(string $role, ?array $requestedFeatures = null): array
    {
        if ($role === self::ROLE_DEV) {
            return array_keys(self::featureLabels());
        }

        $requestedFeatures ??= self::defaultFeatureAccessFor($role);
        $allowedFeatures = array_keys(self::featureLabels());

        return array_values(array_intersect($allowedFeatures, $requestedFeatures));
    }

    public function assignedStore()
    {
        return $this->belongsTo(Store::class, 'assigned_store_id', 'store_id');
    }

    public function manager()
    {
        return $this->belongsTo(self::class, 'managed_by_user_id');
    }

    public function managedUsers()
    {
        return $this->hasMany(self::class, 'managed_by_user_id');
    }

    public function softwareLicense()
    {
        return $this->belongsTo(SoftwareLicense::class, 'software_license_id');
    }

    public function isDev(): bool
    {
        return $this->role === self::ROLE_DEV;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function hasFeature(string $feature): bool
    {
        if ($this->isDev()) {
            return true;
        }

        return in_array($feature, $this->feature_access ?? [], true);
    }
}
