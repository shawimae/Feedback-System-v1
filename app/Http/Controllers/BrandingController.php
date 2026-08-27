<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsActivity;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $licenseId = $request->user()?->software_license_id;

        abort_unless($request->user()?->isSuperAdmin() && $licenseId, 403);

        $branding = AppSetting::brandingForUser($request->user());

        return view('branding.index', [
            'branding' => $branding,
            'savedThemeColor' => $branding['primary'],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $licenseId = $user?->software_license_id;

        abort_unless($user?->isSuperAdmin() && $licenseId, 403);

        $validated = $request->validate([
            'theme_primary' => ['required', 'regex:/^#?[0-9a-fA-F]{6}$/'],
            'brand_logo' => ['nullable', 'image', 'max:10240'],
            'remove_brand_logo' => ['nullable', 'boolean'],
        ]);

        AppSetting::setValue(
            AppSetting::brandingSettingKey($licenseId, 'theme_primary'),
            '#' . strtoupper(ltrim((string) $validated['theme_primary'], '#'))
        );

        $logoKey = AppSetting::brandingSettingKey($licenseId, 'logo_path');
        $existingLogoPath = AppSetting::getValue($logoKey);

        if ($request->boolean('remove_brand_logo') && $existingLogoPath) {
            if (Storage::disk('public')->exists($existingLogoPath)) {
                Storage::disk('public')->delete($existingLogoPath);
            }

            AppSetting::setValue($logoKey, null);
            $existingLogoPath = null;
        }

        if ($request->hasFile('brand_logo')) {
            if ($existingLogoPath && Storage::disk('public')->exists($existingLogoPath)) {
                Storage::disk('public')->delete($existingLogoPath);
            }

            $storedLogoPath = $request->file('brand_logo')->store('brand-logos', 'public');
            AppSetting::setValue($logoKey, $storedLogoPath);
        }

        $this->logActivity(
            'branding.update',
            'Updated navigation branding customization.',
            [
                'metadata' => [
                    'software_license_id' => $licenseId,
                    'theme_primary' => '#' . strtoupper(ltrim((string) $validated['theme_primary'], '#')),
                ],
            ]
        );

        return redirect()
            ->route('branding.index')
            ->with('success', 'Branding updated successfully.');
    }
}
