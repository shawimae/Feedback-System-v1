@extends('layouts.admin')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-3xl font-bold text-slate-800">User Management</h2>
                <p class="mt-2 text-slate-500">Manage system roles, store assignments, and feature access from one place.</p>
            </div>

            <button
                type="button"
                onclick="openUserModal('addUserModal')"
                class="inline-flex items-center gap-2 rounded-[18px] bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add User
            </button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($users as $managedUser)
                <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate text-lg font-semibold text-slate-900">{{ $managedUser->name }}</h3>
                            <p class="mt-1 truncate text-sm text-slate-500">{{ $managedUser->email }}</p>
                        </div>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">
                            {{ $roles[$managedUser->role] ?? ucfirst(str_replace('_', ' ', $managedUser->role)) }}
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 text-sm">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Phone</p>
                            <p class="mt-1 text-slate-700">{{ $managedUser->phone ?: 'No phone number' }}</p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Assigned Store</p>
                            <p class="mt-1 text-slate-700">{{ $managedUser->assignedStore?->name ?? 'All stores / none assigned' }}</p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Feature Access</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($featureLabels as $featureKey => $featureLabel)
                                    @if($managedUser->hasFeature($featureKey))
                                        <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200">
                                            {{ $featureLabel }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-3">
                        <button
                            type="button"
                            onclick="openUserModal('editUserModal{{ $managedUser->id }}')"
                            class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Edit Access
                        </button>

                        <form action="{{ route('users.destroy', $managedUser) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-100">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-[28px] border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">
                    No users yet. Click Add User to create the first account.
                </div>
            @endforelse
        </div>
    </div>

    <div id="addUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 p-4 backdrop-blur-sm">
        <div class="w-full max-w-2xl overflow-hidden rounded-[30px] border border-white/70 bg-white shadow-[0_28px_70px_rgba(15,23,42,0.18)]">
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-4">
                <div>
                    <h3 class="text-2xl font-semibold text-slate-900">Add User</h3>
                    <p class="mt-1 text-sm text-slate-500">Set the role, assigned store, and allowed features before saving.</p>
                </div>
                <button type="button" onclick="closeUserModal('addUserModal')" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('users.store') }}" method="POST" class="grid gap-4 p-6 md:grid-cols-2">
                @csrf
                <input type="hidden" name="feature_access_present" value="1">

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Role</label>
                    <select name="role" data-user-role class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                        @foreach($availableRoles as $roleValue => $roleLabel)
                            <option value="{{ $roleValue }}" {{ old('role', \App\Models\User::ROLE_ADMIN) === $roleValue ? 'selected' : '' }}>{{ $roleLabel }}</option>
                        @endforeach
                    </select>
                </div>

                @if(auth()->user()?->isDev())
                    <div class="md:col-span-2 hidden" data-license-key-field>
                        <label class="mb-2 block text-sm font-medium text-slate-700">License Key For Super Admin</label>
                        <input type="text" name="software_license_key" value="{{ old('software_license_key') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 font-mono outline-none focus:border-slate-400" placeholder="Paste the dev-issued license key">
                        <p class="mt-2 text-xs text-slate-500">Required when creating a Super Admin so their account stays isolated from other client licenses.</p>
                    </div>
                @endif

                <div class="md:col-span-2" data-store-assignment-field>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Assigned Store</label>
                    <select name="assigned_store_id" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                        <option value="">No store assignment</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->store_id }}" {{ (string) old('assigned_store_id') === (string) $store->store_id ? 'selected' : '' }}>
                                {{ $store->name }} ({{ $store->store_number }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-slate-500">Required for Admin accounts that should only handle one branch.</p>
                </div>

                <div class="md:col-span-2">
                    <p class="mb-2 block text-sm font-medium text-slate-700">Feature Access</p>
                    <div class="grid gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2">
                        @foreach($featureLabels as $featureKey => $featureLabel)
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input type="checkbox" name="feature_access[]" value="{{ $featureKey }}" class="rounded border-slate-300" {{ in_array($featureKey, old('feature_access', []), true) ? 'checked' : '' }}>
                                <span>{{ $featureLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Dev always has full access. Super Admin and Admin can be limited per feature.</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" name="password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                </div>

                <div class="md:col-span-2 flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeUserModal('addUserModal')" class="rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>

    @foreach($users as $managedUser)
        <div id="editUserModal{{ $managedUser->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 p-4 backdrop-blur-sm">
            <div class="w-full max-w-2xl overflow-hidden rounded-[30px] border border-white/70 bg-white shadow-[0_28px_70px_rgba(15,23,42,0.18)]">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-4">
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-900">Edit User</h3>
                        <p class="mt-1 text-sm text-slate-500">Update {{ $managedUser->name }}'s role, branch access, and features.</p>
                    </div>
                    <button type="button" onclick="closeUserModal('editUserModal{{ $managedUser->id }}')" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('users.update', $managedUser) }}" method="POST" class="grid gap-4 p-6 md:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="feature_access_present" value="1">

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                        <input type="text" name="name" value="{{ $managedUser->name }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" name="email" value="{{ $managedUser->email }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Phone</label>
                        <input type="text" name="phone" value="{{ $managedUser->phone }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Role</label>
                        <select name="role" data-user-role class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                            @foreach($availableRoles as $roleValue => $roleLabel)
                                <option value="{{ $roleValue }}" {{ $managedUser->role === $roleValue ? 'selected' : '' }}>{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if(auth()->user()?->isDev())
                        <div class="md:col-span-2 {{ $managedUser->isSuperAdmin() ? '' : 'hidden' }}" data-license-key-field>
                            <label class="mb-2 block text-sm font-medium text-slate-700">License Key For Super Admin</label>
                            <input type="text" name="software_license_key" value="{{ $managedUser->softwareLicense?->license_key }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 font-mono outline-none focus:border-slate-400" placeholder="Paste the dev-issued license key">
                            <p class="mt-2 text-xs text-slate-500">This Super Admin and all admins under them will use this specific client license only.</p>
                        </div>
                    @endif

                    <div class="md:col-span-2" data-store-assignment-field>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Assigned Store</label>
                        <select name="assigned_store_id" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                            <option value="">No store assignment</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->store_id }}" {{ (int) $managedUser->assigned_store_id === (int) $store->store_id ? 'selected' : '' }}>
                                    {{ $store->name }} ({{ $store->store_number }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-slate-500">Admins are usually assigned to one branch only.</p>
                    </div>

                    <div class="md:col-span-2">
                        <p class="mb-2 block text-sm font-medium text-slate-700">Feature Access</p>
                        <div class="grid gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2">
                            @foreach($featureLabels as $featureKey => $featureLabel)
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        name="feature_access[]"
                                        value="{{ $featureKey }}"
                                        class="rounded border-slate-300"
                                        {{ $managedUser->hasFeature($featureKey) ? 'checked' : '' }}
                                        {{ $managedUser->isDev() ? 'disabled' : '' }}
                                    >
                                    <span>{{ $featureLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                        @if($managedUser->isDev())
                            <p class="mt-2 text-xs text-slate-500">Dev accounts always keep full feature access.</p>
                        @elseif(auth()->user()?->isSuperAdmin())
                            <p class="mt-2 text-xs text-slate-500">Super Admin can assign store-level Admin users and choose which features they can use.</p>
                        @endif
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">New Password</label>
                        <input type="password" name="password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-400">
                    </div>

                    <div class="md:col-span-2 flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeUserModal('editUserModal{{ $managedUser->id }}')" class="rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <script>
        function openUserModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeUserModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;

            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function syncUserRoleState(select) {
            const form = select.closest('form');
            const storeField = form?.querySelector('[data-store-assignment-field]');
            const licenseKeyField = form?.querySelector('[data-license-key-field]');
            const featureInputs = form?.querySelectorAll('input[name="feature_access[]"]') ?? [];
            const role = select.value;
            const isAdmin = role === '{{ \App\Models\User::ROLE_ADMIN }}';
            const isDev = role === '{{ \App\Models\User::ROLE_DEV }}';
            const isSuperAdmin = role === '{{ \App\Models\User::ROLE_SUPER_ADMIN }}';

            storeField?.classList.toggle('hidden', !isAdmin);
            licenseKeyField?.classList.toggle('hidden', !isSuperAdmin);

            featureInputs.forEach((input) => {
                input.disabled = isDev;

                if (isDev) {
                    input.checked = true;
                }
            });
        }

        document.querySelectorAll('select[data-user-role]').forEach((select) => {
            select.addEventListener('change', () => syncUserRoleState(select));
            syncUserRoleState(select);
        });
    </script>
@endsection
