<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithUserAccess;
use App\Http\Controllers\Concerns\LogsActivity;
use App\Models\AppSetting;
use App\Models\SoftwareLicense;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    use InteractsWithUserAccess, LogsActivity;

    public function index()
    {
        $actor = request()->user();

        return view('users.index', [
            'users' => $this->manageableUsers($actor),
            'stores' => $this->accessibleStoresQuery($actor)
                ->orderBy('name')
                ->get(['store_id', 'name', 'store_number']),
            'roles' => User::roles(),
            'availableRoles' => $this->availableRoles($actor),
            'featureLabels' => User::featureLabels(),
            'availableLicenses' => $actor->isDev()
                ? SoftwareLicense::query()->orderByDesc('is_current')->orderBy('client_name')->get(['id', 'license_key', 'client_name', 'license_name', 'license_status'])
                : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, null, $request->user());
        $user = User::create($this->buildUserPayload($validated, null, $request->user()));

        if (($validated['role'] ?? null) === User::ROLE_SUPER_ADMIN) {
            AppSetting::resetPublishedSurveyForNewEntity();
        }

        $this->logActivity(
            'users.create',
            'Created user account for ' . $user->name . ' as ' . (User::roles()[$user->role] ?? $user->role) . '.',
            [
                'subject' => $user,
                'store_id' => $user->assigned_store_id,
            ]
        );

        return redirect()->route('users.index')->with('success', 'User account created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $actor = $request->user();
        $this->ensureManageableUser($actor, $user);
        $validated = $this->validatePayload($request, $user, $actor);

        if ($actor->isDev() && $user->id === $actor->id && ($validated['role'] ?? $user->role) !== User::ROLE_DEV) {
            return back()->withErrors(['user' => 'Your current account must remain a Dev account while you are signed in.']);
        }

        if ($user->isDev() && ($validated['role'] ?? $user->role) !== User::ROLE_DEV && User::where('role', User::ROLE_DEV)->count() <= 1) {
            return back()->withErrors(['user' => 'At least one Dev account must remain in the system.']);
        }

        $user->update($this->buildUserPayload($validated, $user, $actor));

        $this->logActivity(
            'users.update',
            'Updated user account for ' . $user->name . '.',
            [
                'subject' => $user,
                'store_id' => $user->assigned_store_id,
            ]
        );

        return redirect()->route('users.index')->with('success', 'User account updated successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->ensureManageableUser($request->user(), $user);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot delete the account you are currently using.']);
        }

        if ($user->isDev() && User::where('role', User::ROLE_DEV)->count() <= 1) {
            return back()->withErrors(['user' => 'At least one Dev account must remain in the system.']);
        }

        $deletedName = $user->name;
        $deletedStoreId = $user->assigned_store_id;
        $user->delete();

        $this->logActivity(
            'users.delete',
            'Deleted user account for ' . $deletedName . '.',
            [
                'subject_type' => User::class,
                'subject_id' => (string) $user->id,
                'store_id' => $deletedStoreId,
            ]
        );

        return redirect()->route('users.index')->with('success', 'User account deleted successfully.');
    }

    protected function validatePayload(Request $request, ?User $user = null, ?User $actor = null): array
    {
        $actor ??= $request->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(array_keys($this->availableRoles($actor)))],
            'assigned_store_id' => ['nullable', 'integer', 'exists:stores,store_id'],
            'software_license_key' => ['nullable', 'string'],
            'feature_access_present' => ['nullable', 'in:1'],
            'feature_access' => ['nullable', 'array'],
            'feature_access.*' => ['string', Rule::in(array_keys(User::featureLabels()))],
        ];

        if ($user) {
            $rules['password'] = ['nullable', 'confirmed', 'min:8'];
        } else {
            $rules['password'] = ['required', 'confirmed', 'min:8'];
        }

        $validated = $request->validate($rules);

        if (($validated['role'] ?? null) === User::ROLE_ADMIN && empty($validated['assigned_store_id'])) {
            return back()
                ->withErrors(['assigned_store_id' => 'Assigned store is required for Admin users.'])
                ->withInput()
                ->throwResponse();
        }

        if (($validated['role'] ?? null) === User::ROLE_SUPER_ADMIN && $actor->isDev()) {
            $licenseKey = trim((string) ($validated['software_license_key'] ?? ''));
            $license = SoftwareLicense::query()
                ->where('license_key', $licenseKey)
                ->first();

            if ($licenseKey === '' && $user?->software_license_id) {
                $license = $user->softwareLicense;
            }

            if (! $license) {
                return back()
                    ->withErrors(['software_license_key' => 'A valid license key from Dev registry is required for each Super Admin.'])
                    ->withInput()
                    ->throwResponse();
            }

            $licenseAlreadyAssigned = User::query()
                ->where('role', User::ROLE_SUPER_ADMIN)
                ->where('software_license_id', $license->id)
                ->when($user, fn ($query) => $query->whereKeyNot($user->id))
                ->exists();

            if ($licenseAlreadyAssigned) {
                return back()
                    ->withErrors(['software_license_key' => 'This license key is already assigned to another Super Admin account.'])
                    ->withInput()
                    ->throwResponse();
            }
        }

        return $validated;
    }

    protected function availableRoles(User $actor): array
    {
        if ($actor->isDev()) {
            return [
                User::ROLE_DEV => User::roles()[User::ROLE_DEV],
                User::ROLE_SUPER_ADMIN => User::roles()[User::ROLE_SUPER_ADMIN],
                User::ROLE_ADMIN => User::roles()[User::ROLE_ADMIN],
            ];
        }

        return [
            User::ROLE_ADMIN => User::roles()[User::ROLE_ADMIN],
        ];
    }

    protected function manageableUsers(User $actor)
    {
        return User::with(['assignedStore', 'softwareLicense'])
            ->when(
                $actor->isSuperAdmin(),
                fn ($query) => $query
                    ->where(function ($scopedQuery) use ($actor) {
                        $scopedQuery
                            ->where('id', $actor->id)
                            ->orWhere(function ($adminQuery) use ($actor) {
                                $adminQuery
                                    ->where('role', User::ROLE_ADMIN)
                                    ->where('managed_by_user_id', $actor->id);
                            });
                    })
            )
            ->latest()
            ->get();
    }

    protected function ensureManageableUser(User $actor, User $user): void
    {
        if ($actor->isDev()) {
            return;
        }

        abort_unless(
            $user->id === $actor->id
                || ($user->role === User::ROLE_ADMIN && (int) $user->managed_by_user_id === (int) $actor->id),
            403
        );
    }

    protected function buildUserPayload(array $validated, ?User $user = null, ?User $actor = null): array
    {
        $actor ??= request()->user();
        $role = $validated['role'];
        $resolvedLicense = null;

        if ($role === User::ROLE_SUPER_ADMIN && $actor?->isDev()) {
            $licenseKey = trim((string) ($validated['software_license_key'] ?? ''));
            $resolvedLicense = $licenseKey !== ''
                ? SoftwareLicense::query()->where('license_key', $licenseKey)->first()
                : $user?->softwareLicense;
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $role,
            'assigned_store_id' => $role === User::ROLE_ADMIN
                ? ($validated['assigned_store_id'] ?? null)
                : null,
            'managed_by_user_id' => match (true) {
                $role === User::ROLE_ADMIN && $actor?->isSuperAdmin() => $actor->id,
                $role === User::ROLE_SUPER_ADMIN && $actor?->isDev() => $actor->id,
                $role === User::ROLE_DEV => null,
                default => $user?->managed_by_user_id,
            },
            'software_license_id' => match (true) {
                $role === User::ROLE_SUPER_ADMIN && $actor?->isDev() => $resolvedLicense?->id,
                $role === User::ROLE_ADMIN && $actor?->isSuperAdmin() => $actor->software_license_id,
                $role === User::ROLE_DEV => null,
                default => $user?->software_license_id,
            },
            'feature_access' => User::normalizeFeatureAccess(
                $role,
                array_key_exists('feature_access_present', $validated)
                    ? ($validated['feature_access'] ?? [])
                    : null
            ),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        } elseif (!$user) {
            $payload['password'] = $validated['password'];
        }

        return $payload;
    }
}
