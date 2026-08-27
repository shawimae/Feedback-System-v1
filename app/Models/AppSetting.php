<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected const DEFAULT_BRANDING = [
        'primary' => '#4f81c7',
        'dark' => '#3d6aaa',
        'soft' => '#e8f0fb',
        'soft_strong' => '#d7e5f8',
        'ink' => '#214d84',
        'ring' => 'rgba(79, 129, 199, 0.18)',
        'header_bg' => 'linear-gradient(180deg, #5d91d1 0%, #4f81c7 100%)',
        'brand_text' => '#ffffff',
        'logo_url' => null,
    ];

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public static function getJson(string $key, array $default = []): array
    {
        $value = static::getValue($key);

        if (blank($value)) {
            return $default;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $default;
    }

    public static function setJson(string $key, array $value): void
    {
        static::setValue($key, json_encode($value, JSON_UNESCAPED_UNICODE));
    }

    public static function resetPublishedSurveyForNewEntity(): void
    {
        static::setJson('published_questionnaire_snapshot', []);
        static::setValue('published_questionnaire_title', 'Customer Feedback');
        static::setValue('questionnaire_title', 'Customer Feedback');
        static::setValue('questionnaire_last_synced_at', null);
    }

    public static function licenseState(): array
    {
        $licenseKey = trim((string) static::getValue('license_key', ''));
        $startAtValue = static::getValue('license_starts_at');
        $endAtValue = static::getValue('license_ends_at');
        $startAt = filled($startAtValue) ? Carbon::parse($startAtValue) : null;
        $endAt = filled($endAtValue) ? Carbon::parse($endAtValue) : null;
        $status = SoftwareLicense::resolveStatus($startAt, $endAt);
        $now = now();

        if ($licenseKey === '') {
            return [
                'is_valid' => false,
                'reason' => 'missing_key',
                'message' => 'A license key is required before the system can be used.',
            ];
        }

        if ($status !== 'active') {
            return [
                'is_valid' => false,
                'reason' => 'expired',
                'message' => 'The software license has expired. Please subscribe to continue.',
            ];
        }

        if ($startAt && $startAt->isFuture()) {
            return [
                'is_valid' => false,
                'reason' => 'not_started',
                'message' => 'The software license has not started yet.',
            ];
        }

        if ($endAt && $endAt->lte($now)) {
            return [
                'is_valid' => false,
                'reason' => 'expired',
                'message' => 'The software license has expired. Please subscribe to continue.',
            ];
        }

        return [
            'is_valid' => true,
            'reason' => 'active',
            'message' => 'The software license is active.',
        ];
    }

    public static function licenseStateForUser(?User $user): array
    {
        if (! $user || $user->isDev()) {
            return static::licenseState();
        }

        $license = $user->softwareLicense;

        if (! $license) {
            return [
                'is_valid' => false,
                'reason' => 'missing_key',
                'message' => 'This account is not linked to a valid client license key from Dev.',
            ];
        }

        $license->syncResolvedStatus();

        if ($license->license_status !== 'active') {
            $latestRenewalRequest = RenewalRequest::query()
                ->where('software_license_id', $license->id)
                ->where('requester_email', $user->email)
                ->latest('reviewed_at')
                ->latest()
                ->first();

            if ($latestRenewalRequest && in_array($latestRenewalRequest->status, ['approved', 'completed'], true)) {
                return [
                    'is_valid' => true,
                    'reason' => 'renewal_approved',
                    'message' => 'Renewal request approved. Access restored for this account.',
                ];
            }

            return [
                'is_valid' => false,
                'reason' => 'expired',
                'message' => 'The client license linked to this account has expired.',
            ];
        }

        return [
            'is_valid' => true,
            'reason' => 'active',
            'message' => 'The software license is active.',
        ];
    }

    public static function licenseStateForStore(?Store $store): array
    {
        $license = $store?->owner?->softwareLicense;

        if (! $license) {
            return static::licenseState();
        }

        $license->syncResolvedStatus();

        if ($license->license_status !== 'active') {
            return [
                'is_valid' => false,
                'reason' => 'expired',
                'message' => 'This store subscription has expired. Please contact the store administrator.',
            ];
        }

        return [
            'is_valid' => true,
            'reason' => 'active',
            'message' => 'The software license is active.',
        ];
    }

    public static function brandingForUser(?User $user): array
    {
        $licenseId = $user?->software_license_id;

        if (! $licenseId) {
            return static::DEFAULT_BRANDING;
        }

        $primary = static::normalizeHexColor(
            static::getValue(static::brandingSettingKey($licenseId, 'theme_primary')),
            static::DEFAULT_BRANDING['primary']
        );

        $logoPath = static::getValue(static::brandingSettingKey($licenseId, 'logo_path'));

        return [
            'primary' => $primary,
            'dark' => static::adjustHexColor($primary, -0.2),
            'soft' => static::mixHexColor($primary, '#ffffff', 0.88),
            'soft_strong' => static::mixHexColor($primary, '#ffffff', 0.8),
            'ink' => static::adjustHexColor($primary, -0.45),
            'ring' => static::hexToRgba($primary, 0.18),
            'header_bg' => 'linear-gradient(180deg, '
                . static::mixHexColor($primary, '#ffffff', 0.14)
                . ' 0%, '
                . $primary
                . ' 100%)',
            'brand_text' => '#ffffff',
            'logo_url' => filled($logoPath) ? asset('storage/' . ltrim($logoPath, '/')) : null,
        ];
    }

    public static function brandingSettingKey(int $licenseId, string $suffix): string
    {
        return 'license_' . $licenseId . '_branding_' . $suffix;
    }

    protected static function normalizeHexColor(?string $value, string $default): string
    {
        $value = trim((string) $value);

        if (! preg_match('/^#?[0-9a-fA-F]{6}$/', $value)) {
            return $default;
        }

        return '#' . strtoupper(ltrim($value, '#'));
    }

    protected static function adjustHexColor(string $hex, float $percent): string
    {
        [$red, $green, $blue] = static::hexToRgb($hex);

        $transform = function (int $channel) use ($percent): int {
            if ($percent >= 0) {
                return (int) round($channel + ((255 - $channel) * $percent));
            }

            return (int) round($channel * (1 + $percent));
        };

        return static::rgbToHex($transform($red), $transform($green), $transform($blue));
    }

    protected static function mixHexColor(string $hexA, string $hexB, float $weightOfB): string
    {
        [$redA, $greenA, $blueA] = static::hexToRgb($hexA);
        [$redB, $greenB, $blueB] = static::hexToRgb($hexB);

        $mix = function (int $channelA, int $channelB) use ($weightOfB): int {
            return (int) round(($channelA * (1 - $weightOfB)) + ($channelB * $weightOfB));
        };

        return static::rgbToHex(
            $mix($redA, $redB),
            $mix($greenA, $greenB),
            $mix($blueA, $blueB)
        );
    }

    protected static function hexToRgba(string $hex, float $alpha): string
    {
        [$red, $green, $blue] = static::hexToRgb($hex);

        return sprintf('rgba(%d, %d, %d, %.2f)', $red, $green, $blue, $alpha);
    }

    protected static function hexToRgb(string $hex): array
    {
        $normalized = ltrim(static::normalizeHexColor($hex, static::DEFAULT_BRANDING['primary']), '#');

        return [
            hexdec(substr($normalized, 0, 2)),
            hexdec(substr($normalized, 2, 2)),
            hexdec(substr($normalized, 4, 2)),
        ];
    }

    protected static function rgbToHex(int $red, int $green, int $blue): string
    {
        return sprintf(
            '#%02X%02X%02X',
            max(0, min(255, $red)),
            max(0, min(255, $green)),
            max(0, min(255, $blue))
        );
    }
}
