<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsActivity;
use App\Models\AppSetting;
use App\Models\RenewalRequest;
use App\Models\SoftwareLicense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SoftwareLicenseController extends Controller
{
    use LogsActivity;

    protected function licenseStatusOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'expired' => 'Expired',
        ];
    }

    protected function generateLicenseKey(): string
    {
        return collect(range(1, 4))
            ->map(fn () => strtoupper(str()->random(4)))
            ->implode('-');
    }

    protected function buildLicenseSettings(): array
    {
        $startAt = AppSetting::getValue('license_starts_at');
        $endAt = AppSetting::getValue('license_ends_at');
        $maxStores = (int) AppSetting::getValue('max_stores', AppSetting::getValue('license_max_stores', '0'));
        $currentStoreCount = \App\Models\Store::count();

        $startsAt = filled($startAt) ? Carbon::parse($startAt) : null;
        $endsAt = filled($endAt) ? Carbon::parse($endAt) : null;
        $now = now();
        $storedStatus = SoftwareLicense::resolveStatus($startsAt, $endsAt);

        $remainingHours = null;
        $remainingLabel = 'No end date set.';

        if ($endsAt) {
            $remainingHours = max(0, (int) ceil($now->floatDiffInHours($endsAt, false)));

            if ($endsAt->isPast()) {
                $remainingLabel = 'License period has ended.';
            } else {
                $days = intdiv($remainingHours, 24);
                $hours = $remainingHours % 24;
                $parts = [];

                if ($days > 0) {
                    $parts[] = $days . ' day' . ($days === 1 ? '' : 's');
                }

                $parts[] = $hours . ' hr' . ($hours === 1 ? '' : 's');
                $remainingLabel = implode(' ', $parts) . ' remaining';
            }
        }

        $canAddStores = true;
        $storeLimitMessage = null;

        if ($storedStatus !== 'active') {
            $canAddStores = false;
            $storeLimitMessage = 'Store creation is disabled because the license has expired.';
        } elseif ($endsAt && $endsAt->isPast()) {
            $canAddStores = false;
            $storeLimitMessage = 'Store creation is disabled because the license has expired.';
        } elseif ($startsAt && $startsAt->isFuture()) {
            $canAddStores = false;
            $storeLimitMessage = 'Store creation is disabled until the license start date begins.';
        } elseif ($maxStores > 0 && $currentStoreCount >= $maxStores) {
            $canAddStores = false;
            $storeLimitMessage = 'Store limit reached. Increase the max stores in the license settings to add more branches.';
        }

        return [
            'license_name' => AppSetting::getValue('license_name', 'Feedback System License'),
            'license_key' => AppSetting::getValue('license_key'),
            'license_status' => $storedStatus,
            'license_starts_at' => $startAt,
            'license_ends_at' => $endAt,
            'client_name' => AppSetting::getValue('client_name'),
            'max_stores' => $maxStores,
            'license_notes' => AppSetting::getValue('license_notes'),
            'remaining_hours' => $remainingHours,
            'remaining_label' => $remainingLabel,
            'current_store_count' => $currentStoreCount,
            'can_add_stores' => $canAddStores,
            'store_limit_message' => $storeLimitMessage,
        ];
    }

    protected function validateLicensePayload(Request $request, ?SoftwareLicense $softwareLicense = null): array
    {
        return $request->validate([
            'license_name' => ['required', 'string', 'max:255'],
            'license_key' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('software_licenses', 'license_key')->ignore($softwareLicense?->id),
            ],
            'license_starts_at' => ['nullable', 'date'],
            'license_ends_at' => ['nullable', 'date', 'after_or_equal:license_starts_at'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'max_stores' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'license_notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    protected function resolveLicenseStatus(array $payload): string
    {
        $startsAt = filled($payload['license_starts_at'] ?? null)
            ? Carbon::parse($payload['license_starts_at'])
            : null;
        $endsAt = filled($payload['license_ends_at'] ?? null)
            ? Carbon::parse($payload['license_ends_at'])
            : null;

        return SoftwareLicense::resolveStatus($startsAt, $endsAt);
    }

    protected function applyResolvedStatus(array $payload): array
    {
        $payload['license_status'] = $this->resolveLicenseStatus($payload);

        return $payload;
    }

    protected function syncAppSettingsFromLicense(SoftwareLicense $softwareLicense): void
    {
        AppSetting::setValue('license_name', $softwareLicense->license_name);
        AppSetting::setValue('license_key', $softwareLicense->license_key);
        AppSetting::setValue('license_status', $softwareLicense->license_status);
        AppSetting::setValue('license_starts_at', optional($softwareLicense->starts_at)?->format('Y-m-d H:i:s'));
        AppSetting::setValue('license_ends_at', optional($softwareLicense->ends_at)?->format('Y-m-d H:i:s'));
        AppSetting::setValue('client_name', $softwareLicense->client_name);
        AppSetting::setValue('max_stores', $softwareLicense->max_stores ? (string) $softwareLicense->max_stores : null);
        AppSetting::setValue('license_notes', $softwareLicense->license_notes);
    }

    protected function syncCurrentLicenseRecord(array $payload): SoftwareLicense
    {
        SoftwareLicense::query()->update(['is_current' => false]);

        $currentLicense = SoftwareLicense::query()->firstWhere('is_current', true);

        if (! $currentLicense) {
            $currentLicense = SoftwareLicense::query()->firstWhere('license_key', $payload['license_key'] ?? null);
        }

        if (! $currentLicense) {
            $currentLicense = new SoftwareLicense();
        }

        $currentLicense->fill([
            'license_name' => $payload['license_name'],
            'client_name' => $payload['client_name'] ?? null,
            'license_key' => $payload['license_key'] ?? null,
            'license_status' => $payload['license_status'],
            'starts_at' => $payload['license_starts_at'] ?? null,
            'ends_at' => $payload['license_ends_at'] ?? null,
            'max_stores' => $payload['max_stores'] ?? null,
            'license_notes' => $payload['license_notes'] ?? null,
            'is_current' => true,
        ]);
        $currentLicense->save();

        return $currentLicense;
    }

    protected function ensureRegistryHasCurrentLicense(): void
    {
        SoftwareLicense::syncAllResolvedStatuses();

        if (SoftwareLicense::query()->where('is_current', true)->exists()) {
            return;
        }

        $licenseKey = AppSetting::getValue('license_key');
        $licenseName = AppSetting::getValue('license_name');
        $clientName = AppSetting::getValue('client_name');

        if (blank($licenseKey) && blank($licenseName) && blank($clientName)) {
            return;
        }

        SoftwareLicense::create([
            'license_name' => $licenseName ?: 'Feedback System License',
            'client_name' => $clientName,
            'license_key' => $licenseKey,
            'license_status' => SoftwareLicense::resolveStatus(
                filled(AppSetting::getValue('license_starts_at')) ? Carbon::parse(AppSetting::getValue('license_starts_at')) : null,
                filled(AppSetting::getValue('license_ends_at')) ? Carbon::parse(AppSetting::getValue('license_ends_at')) : null
            ),
            'starts_at' => AppSetting::getValue('license_starts_at'),
            'ends_at' => AppSetting::getValue('license_ends_at'),
            'max_stores' => (int) AppSetting::getValue('max_stores', '0') ?: null,
            'license_notes' => AppSetting::getValue('license_notes'),
            'is_current' => true,
        ]);
    }

    public function index()
    {
        $this->ensureRegistryHasCurrentLicense();
        if ($currentLicense = SoftwareLicense::query()->where('is_current', true)->first()) {
            $currentLicense->syncResolvedStatus();
            $this->syncAppSettingsFromLicense($currentLicense->fresh());
        }
        $licenseSettings = $this->buildLicenseSettings();
        $statusOptions = $this->licenseStatusOptions();
        $licenses = SoftwareLicense::query()
            ->orderByDesc('is_current')
            ->orderByDesc('created_at')
            ->get();
        $renewalRequests = RenewalRequest::query()
            ->with(['softwareLicense', 'reviewedBy'])
            ->latest()
            ->get();

        return view('licensing.index', compact('licenseSettings', 'statusOptions', 'licenses', 'renewalRequests'));
    }

    public function refreshKey()
    {
        $licenseKey = $this->generateLicenseKey();

        AppSetting::setValue('license_key', $licenseKey);

        $this->logActivity(
            'licensing.generate_key',
            'Generated a new software license key.'
        );

        return redirect()
            ->route('licensing.index')
            ->with('success', 'A new license key has been generated successfully.');
    }

    public function store(Request $request)
    {
        $validated = $this->applyResolvedStatus($this->validateLicensePayload($request));

        $license = SoftwareLicense::create([
            'license_name' => $validated['license_name'],
            'client_name' => $validated['client_name'] ?? null,
            'license_key' => filled($validated['license_key'] ?? null) ? $validated['license_key'] : $this->generateLicenseKey(),
            'license_status' => $validated['license_status'],
            'starts_at' => $validated['license_starts_at'] ?? null,
            'ends_at' => $validated['license_ends_at'] ?? null,
            'max_stores' => $validated['max_stores'] ?? null,
            'license_notes' => $validated['license_notes'] ?? null,
            'is_current' => false,
        ]);

        $this->logActivity(
            'licensing.create',
            'Added client license for ' . ($license->client_name ?: $license->license_name) . '.',
            [
                'subject' => $license,
            ]
        );

        return redirect()
            ->route('licensing.index')
            ->with('success', 'Client license for ' . ($license->client_name ?: $license->license_name) . ' was added successfully.');
    }

    public function update(Request $request)
    {
        $validated = $this->applyResolvedStatus($this->validateLicensePayload($request));

        foreach ($validated as $key => $value) {
            AppSetting::setValue($key, blank($value) ? null : (string) $value);
        }

        $currentLicense = $this->syncCurrentLicenseRecord($validated);

        $this->logActivity(
            'licensing.update_current',
            'Updated the active software license settings.',
            [
                'subject' => $currentLicense,
            ]
        );

        return redirect()
            ->route('licensing.index')
            ->with('success', 'Software licensing details updated successfully.');
    }

    public function updateRegistry(Request $request, SoftwareLicense $softwareLicense)
    {
        $validated = $this->applyResolvedStatus($this->validateLicensePayload($request, $softwareLicense));

        $softwareLicense->update([
            'license_name' => $validated['license_name'],
            'client_name' => $validated['client_name'] ?? null,
            'license_key' => filled($validated['license_key'] ?? null) ? $validated['license_key'] : $softwareLicense->license_key,
            'license_status' => $validated['license_status'],
            'starts_at' => $validated['license_starts_at'] ?? null,
            'ends_at' => $validated['license_ends_at'] ?? null,
            'max_stores' => $validated['max_stores'] ?? null,
            'license_notes' => $validated['license_notes'] ?? null,
        ]);

        if ($softwareLicense->is_current) {
            $this->syncAppSettingsFromLicense($softwareLicense->fresh());
        }

        $this->logActivity(
            'licensing.update',
            'Updated client license for ' . ($softwareLicense->client_name ?: $softwareLicense->license_name) . '.',
            [
                'subject' => $softwareLicense,
            ]
        );

        return redirect()
            ->route('licensing.index')
            ->with('success', 'Client license updated successfully.');
    }

    public function activate(SoftwareLicense $softwareLicense)
    {
        SoftwareLicense::query()->update(['is_current' => false]);
        $softwareLicense->update([
            'is_current' => true,
            'license_status' => SoftwareLicense::resolveStatus($softwareLicense->starts_at, $softwareLicense->ends_at),
        ]);
        $this->syncAppSettingsFromLicense($softwareLicense->fresh());

        $this->logActivity(
            'licensing.activate',
            'Switched the active client license to ' . ($softwareLicense->client_name ?: $softwareLicense->license_name) . '.',
            [
                'subject' => $softwareLicense,
            ]
        );

        return redirect()
            ->route('licensing.index')
            ->with('success', 'Current active license switched to ' . ($softwareLicense->client_name ?: $softwareLicense->license_name) . '.');
    }
}
