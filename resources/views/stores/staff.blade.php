@extends('layouts.admin')

@section('content')
    <style>
        .staff-toolbar-btn {
            min-height: 38px;
            padding: 0.55rem 0.85rem;
            border-radius: 14px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        .staff-section-tab {
            min-height: 38px;
            padding: 0.55rem 0.9rem;
            border-radius: 14px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        .staff-section-tab.is-active {
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: #c2410c;
        }

        .staff-modal-shell {
            width: min(100%, 760px);
            border-radius: 22px;
        }

        .staff-form-control {
            min-height: 44px;
        }

        .staff-form-grid {
            display: grid;
            gap: 1rem;
        }

        .staff-view-toggle {
            min-height: 38px;
            min-width: 38px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        .staff-view-toggle.is-active {
            border-color: #fdba74;
            background: #fff7ed;
            color: #c2410c;
        }

        .staff-search-panel {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            min-width: 0;
            width: 100%;
        }

        .staff-header-main {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
        }

        .staff-header-top {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .staff-header-bottom {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
        }

        .staff-context-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .staff-search-shell {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            flex: 1 1 430px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            padding: 0.75rem 0.9rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }

        .staff-search-input {
            width: 100%;
            border: 0;
            background: transparent;
            font-size: 14px;
            color: #0f172a;
            outline: none;
        }

        .staff-search-input::placeholder {
            color: #94a3b8;
        }

        .staff-search-clear {
            display: inline-flex;
            height: 28px;
            width: 28px;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            color: #0f172a;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .staff-search-clear:hover {
            background: #f8fafc;
        }

        .staff-showing-shell {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            min-height: 46px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            padding: 0 0.75rem;
            color: #64748b;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            white-space: nowrap;
        }

        .staff-showing-select {
            border: 0;
            background: transparent;
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
            outline: none;
        }

        .staff-store-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }

        .staff-store-title-icon {
            display: inline-flex;
            height: 44px;
            width: 72px;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #fff7ed;
            color: #c2410c;
        }

        .staff-store-title-copy {
            min-width: 0;
        }

        .staff-store-title-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 700;
            color: #0f172a;
        }

        .staff-inline-filter {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            min-height: 42px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #f8fafc;
            padding: 0 0.8rem;
            color: #475569;
        }

        .staff-inline-select {
            border: 0;
            background: transparent;
            font-size: 13px;
            color: #0f172a;
            outline: none;
        }

        .staff-actions-wrap {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .staff-bottom-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.45rem;
        }

        .staff-total-label {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.2rem 0.1rem;
            font-size: 12px;
            color: #64748b;
        }

        .staff-status-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            border: 1px solid #dbe3ee;
            background: #f8fafc;
            padding: 0.45rem 0.95rem;
            font-size: 11px;
            font-weight: 500;
            color: #475569;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        }

        .staff-status-chip.is-active-filter {
            border-color: #bbf7d0;
            background: #f3fff7;
            color: #15803d;
        }

        .staff-status-chip.is-inactive-filter {
            border-color: #dbe3ee;
            background: #fbfcfd;
            color: #475569;
        }

        .staff-grid {
            display: grid;
            gap: 0.9rem;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        .staff-grid-card {
            position: relative;
            overflow: hidden;
            border: 1px solid #e8edf5;
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
        }

        .staff-grid-photo {
            height: 58px;
            width: 58px;
            border-radius: 9999px;
            border: 3px solid #f8fafc;
            background: #f1f5f9;
            object-fit: cover;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .staff-grid-photo-fallback {
            display: inline-flex;
            height: 58px;
            width: 58px;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            border: 3px solid #f8fafc;
            background: linear-gradient(135deg, #eef2f7, #f8fafc);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .staff-card-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 88px;
            border-radius: 9999px;
            padding: 0.4rem 0.8rem;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .staff-card-status.is-active {
            background: #eefbf3;
            color: #15803d;
        }

        .staff-card-status.is-inactive {
            background: #f3f0ff;
            color: #7c3aed;
        }

        .staff-card-contact {
            border: 1px solid #eef2f7;
            border-radius: 18px;
            background: #f8fafc;
            padding: 0.7rem 0.8rem;
        }

        .staff-card-meta {
            border-radius: 18px;
            background: linear-gradient(180deg, #f8fafc 0%, #f6f0ff 100%);
            padding: 0.75rem 0.9rem;
        }

        .staff-card-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            border-radius: 12px;
            padding: 0.5rem 0.85rem;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        @media (min-width: 640px) {
            .staff-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .staff-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .staff-header-main {
                align-items: stretch;
                gap: 0.75rem;
            }

            .staff-header-top {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center;
                gap: 0.75rem;
            }

            .staff-header-bottom {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center;
                gap: 0.75rem;
            }

            .staff-actions-wrap {
                justify-content: flex-end;
                flex-wrap: nowrap;
            }
        }

        @media (min-width: 900px) {
            .staff-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1180px) {
            .staff-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (min-width: 1440px) {
            .staff-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }
    </style>

    <div class="space-y-5">
        <div class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="staff-header-main">
                <div class="staff-header-top">
                    <div class="min-w-0 flex-1">
                        <div class="staff-search-panel">
                            <div class="staff-search-shell">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                </svg>
                                <input
                                    type="search"
                                    id="staffSearchInput"
                                    class="staff-search-input"
                                    placeholder="Search any employee"
                                    aria-label="Search employees"
                                >
                                <button type="button" id="staffSearchClear" class="staff-search-clear" aria-label="Clear search">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="staff-actions-wrap">
                        <a href="{{ route('stores.staff.analytics', $store) }}" class="staff-toolbar-btn inline-flex items-center gap-2 bg-[#4f81c7] text-white transition hover:bg-[#3d6aaa]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h16.5v16.5H3.75V3.75Zm3.75 11.25h1.5v1.5H7.5V15Zm3.75-4.5h1.5v6h-1.5v-6Zm3.75-3h1.5v9h-1.5v-9Z" />
                            </svg>
                            Staff Analytics
                        </a>
                        <button onclick="openModal('addEmployeeModal')" class="staff-toolbar-btn inline-flex items-center gap-2 bg-[#4f81c7] text-white transition hover:bg-[#3d6aaa]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add New Employee
                        </button>
                    </div>
                </div>

            <div class="staff-header-bottom mt-4">
                <div class="flex flex-wrap items-center gap-2.5 text-sm">
                    <div class="staff-store-title">
                        @if($store->profile_photo_url)
                            <img src="{{ $store->profile_photo_url }}" alt="{{ $store->name }} logo" class="staff-store-title-icon border border-orange-100 bg-white p-1.5 object-contain">
                        @else
                            <span class="staff-store-title-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5M6 9V5.625c0-.621.504-1.125 1.125-1.125h9.75c.621 0 1.125.504 1.125 1.125V9M6 9h12M6 9v9.375c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9M9.75 12.75h4.5" />
                                </svg>
                            </span>
                        @endif
                        <div class="staff-store-title-copy">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Store Name</p>
                            <p class="staff-store-title-name text-[17px] sm:text-[18px]">{{ $store->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">Questionnaire logo for this branch follows this store profile image.</p>
                        </div>
                    </div>
                    <span class="staff-total-label">
                        <span class="font-medium uppercase tracking-[0.12em] text-slate-400">Total</span>
                        <span class="font-semibold text-slate-700">{{ $staffCount }}</span>
                    </span>
                    <button type="button" id="staffActiveFilter" class="staff-status-chip is-active-filter">
                        Active {{ $activeStaffCount }}
                    </button>
                    <button type="button" id="staffInactiveFilter" class="staff-status-chip is-inactive-filter">
                        Inactive {{ $inactiveStaffCount }}
                    </button>
                </div>

                <div class="staff-bottom-controls">
                    <label class="staff-showing-shell">
                        <span class="text-[12px] font-medium text-slate-500">Showing</span>
                        <select id="staffShowingSelect" class="staff-showing-select" aria-label="Showing staff count">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="all">All</option>
                        </select>
                    </label>

                    <label class="staff-inline-filter">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6h15m-12 6h9m-6 6h3" />
                        </svg>
                        <select id="staffRoleFilter" class="staff-inline-select" aria-label="Filter by role">
                            <option value="">All</option>
                            <option value="staff">Staff</option>
                            <option value="manager">Manager</option>
                        </select>
                    </label>

                    <button type="button" id="staffExportButton" class="staff-inline-filter transition hover:bg-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v10.5m0 0 3.75-3.75M12 14.25 8.25 10.5M4.5 15.75v1.125A2.625 2.625 0 0 0 7.125 19.5h9.75A2.625 2.625 0 0 0 19.5 16.875V15.75" />
                        </svg>
                        <span class="text-[13px] font-medium text-slate-700">Export</span>
                    </button>

                    <div class="ml-0.5 inline-flex items-center gap-1.5">
                        <button type="button" id="staffGridToggle" class="staff-view-toggle inline-flex items-center justify-center" aria-label="Grid view">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h3.75v3.75H6.75V6.75Zm6.75 0h3.75v3.75H13.5V6.75Zm-6.75 6.75h3.75v3.75H6.75V13.5Zm6.75 0h3.75v3.75H13.5V13.5Z" />
                            </svg>
                        </button>
                        <button type="button" id="staffTableToggle" class="staff-view-toggle inline-flex items-center justify-center is-active" aria-label="Table view">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="staffTableView" class="mt-5 overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-slate-500">
                            <th class="px-5 py-4 font-semibold">Photo</th>
                            <th class="px-5 py-4 font-semibold">Name</th>
                            <th class="px-5 py-4 font-semibold">Email</th>
                            <th class="px-5 py-4 font-semibold">Number</th>
                            <th class="px-5 py-4 font-semibold">Role</th>
                            <th class="px-5 py-4 font-semibold">Status</th>
                            <th class="px-5 py-4 font-semibold">Date Added</th>
                            <th class="px-5 py-4 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($staffMembers as $staff)
                            <tr class="staff-record-row text-slate-700" data-staff-search="{{ strtolower(trim($staff->name . ' ' . ($staff->email ?? '') . ' ' . ($staff->phone ?? '') . ' ' . ($staff->role ?? '') . ' ' . ($staff->status ?? ''))) }}" data-staff-role="{{ strtolower(trim($staff->role ?? '')) }}" data-staff-status="{{ strtolower(trim($staff->status ?? '')) }}">
                                <td class="px-5 py-4">
                                    <div class="relative h-12 w-12">
                                        @if($staff->profile_photo_path)
                                            <img
                                                src="{{ asset('storage/' . $staff->profile_photo_path) }}"
                                                alt="{{ $staff->name }}"
                                                class="h-12 w-12 rounded-full object-cover"
                                                onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
                                            >
                                            <span class="hidden h-12 w-12 rounded-full bg-slate-100"></span>
                                        @else
                                            <span class="inline-flex h-12 w-12 rounded-full bg-slate-100"></span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ $staff->name }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ $staff->email ?: '-' }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ $staff->phone ?: '-' }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ $staff->role ?: 'Store Employee' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold {{ $staff->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ strtoupper($staff->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-500">{{ optional($staff->created_at)->format('M d, Y') ?? '-' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" onclick="openModal('editEmployeeModal{{ $staff->staff_id }}')" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                            Edit
                                        </button>
                                        <button type="button" onclick="openModal('deleteEmployeeModal{{ $staff->staff_id }}')" class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>

                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div id="staffGridView" class="mt-5 hidden">
            @if($staffMembers->isEmpty())
                <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                    <div class="px-5 py-10"></div>
                </div>
            @else
                <div class="staff-grid">
                    @foreach($staffMembers as $staff)
                        <article class="staff-grid-card staff-record-card p-3.5 pt-4" data-staff-search="{{ strtolower(trim($staff->name . ' ' . ($staff->email ?? '') . ' ' . ($staff->phone ?? '') . ' ' . ($staff->role ?? '') . ' ' . ($staff->status ?? ''))) }}" data-staff-role="{{ strtolower(trim($staff->role ?? '')) }}" data-staff-status="{{ strtolower(trim($staff->status ?? '')) }}">
                            <div class="flex flex-col items-center text-center">
                                @if($staff->profile_photo_path)
                                    <img
                                        src="{{ asset('storage/' . $staff->profile_photo_path) }}"
                                        alt="{{ $staff->name }}"
                                        class="staff-grid-photo"
                                        onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
                                    >
                                    <!-- <span class="staff-grid-photo-fallback hidden"></span> -->
                                @else
                                    <span class="staff-grid-photo-fallback"></span>
                                @endif
                                <h3 class="mt-3 text-[15px] font-semibold leading-tight text-slate-900">{{ $staff->name }}</h3>
                                <p class="mt-1 text-[12px] text-slate-400">{{ $staff->role ?: 'Store Employee' }}</p>
                            </div>

                            <div class="mt-4 space-y-2.5 text-sm">
                                <div class="staff-card-contact space-y-2.5">
                                    <div class="flex items-center gap-2.5 text-slate-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5A2.25 2.25 0 0 1 19.5 19.5H4.5a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5H4.5a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.915l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.913A2.25 2.25 0 0 1 2.25 6.993V6.75" />
                                        </svg>
                                        <span class="truncate text-[12px] font-medium">{{ $staff->email ?: 'No email provided' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 text-slate-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 7.318 5.932 13.25 13.25 13.25h1.5a2.25 2.25 0 0 0 2.244-2.077l.255-2.55a2.25 2.25 0 0 0-1.345-2.249l-2.868-1.23a2.25 2.25 0 0 0-2.715.72l-.665.887a18.66 18.66 0 0 1-6.6-6.6l.887-.665a2.25 2.25 0 0 0 .72-2.715L5.6 2.601A2.25 2.25 0 0 0 3.35 1.256L.8 1.511A2.25 2.25 0 0 0-1.277 3.755v1.5" />
                                        </svg>
                                        <span class="text-[12px] font-medium">{{ $staff->phone ?: 'No number provided' }}</span>
                                    </div>
                                </div>

                                <div class="staff-card-meta grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-[10px] font-medium uppercase tracking-[0.08em] text-slate-400">Role</p>
                                        <p class="mt-1 text-[12px] font-semibold text-slate-700">{{ $staff->role ?: 'Staff' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] font-medium uppercase tracking-[0.08em] text-slate-400">Date Added</p>
                                        <p class="mt-1 text-[12px] font-semibold text-slate-700">{{ optional($staff->created_at)->format('d M, Y') ?? '-' }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" onclick="openModal('editEmployeeModal{{ $staff->staff_id }}')" class="staff-card-action border border-slate-300 text-slate-700 transition hover:bg-slate-50">
                                        Edit
                                    </button>
                                    <button type="button" onclick="openModal('deleteEmployeeModal{{ $staff->staff_id }}')" class="staff-card-action border border-rose-200 text-rose-600 transition hover:bg-rose-50">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div id="addEmployeeModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 p-4 backdrop-blur-sm">
        <div class="staff-modal-shell bg-white p-5 shadow-[0_28px_70px_rgba(15,23,42,0.18)] sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-semibold text-slate-900">Add Employee</h3>
                    <p class="mt-1 text-sm text-slate-500">Create a manual employee record for {{ $store->name }}.</p>
                </div>
                <button onclick="closeModal('addEmployeeModal')" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('stores.staff.store', $store) }}" method="POST" enctype="multipart/form-data" class="mt-6">
                @csrf
                <div class="staff-form-grid">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Employee Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="staff-form-control mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-orange-300" required>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Contact Number</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="staff-form-control mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-orange-300">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="staff-form-control mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-orange-300">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Profile Picture</label>
                        <input type="file" name="profile_photo" accept="image/*" class="staff-form-control mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition file:mr-4 file:rounded-full file:border-0 file:bg-[#e8f0fb] file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-[#214d84] hover:file:bg-[#d7e5f8]">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Status</label>
                        <select name="status" class="staff-form-control mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-orange-300">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Role</label>
                        <select name="role" class="staff-form-control mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-orange-300">
                            <option value="">Select role</option>
                            <option value="Staff" {{ old('role') === 'Staff' ? 'selected' : '' }}>Staff</option>
                            <option value="Manager" {{ old('role') === 'Manager' ? 'selected' : '' }}>Manager</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('addEmployeeModal')" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Save Employee</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($staffMembers as $staff)
        <div id="editEmployeeModal{{ $staff->staff_id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 p-4 backdrop-blur-sm">
            <div class="staff-modal-shell bg-white p-5 shadow-[0_28px_70px_rgba(15,23,42,0.18)] sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-900">Edit Employee</h3>
                        <p class="mt-1 text-sm text-slate-500">Update employee details for {{ $store->name }}.</p>
                    </div>
                    <button onclick="closeModal('editEmployeeModal{{ $staff->staff_id }}')" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('stores.staff.update', [$store, $staff]) }}" method="POST" enctype="multipart/form-data" class="mt-6">
                    @csrf
                    @method('PUT')
                    <div class="staff-form-grid">
                        <div>
                            <label class="text-sm font-medium text-slate-700">Employee Name</label>
                            <input type="text" name="name" value="{{ $staff->name }}" class="staff-form-control mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-orange-300" required>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700">Contact Number</label>
                            <input type="text" name="phone" value="{{ $staff->phone }}" class="staff-form-control mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-orange-300">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700">Email Address</label>
                            <input type="email" name="email" value="{{ $staff->email }}" class="staff-form-control mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-orange-300">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700">Profile Picture</label>
                            <input type="file" name="profile_photo" accept="image/*" class="staff-form-control mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition file:mr-4 file:rounded-full file:border-0 file:bg-[#e8f0fb] file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-[#214d84] hover:file:bg-[#d7e5f8]">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700">Status</label>
                            <select name="status" class="staff-form-control mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-orange-300">
                                <option value="active" {{ $staff->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $staff->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700">Role</label>
                            <select name="role" class="staff-form-control mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-orange-300">
                                <option value="">Select role</option>
                                <option value="Staff" {{ $staff->role === 'Staff' ? 'selected' : '' }}>Staff</option>
                                <option value="Manager" {{ $staff->role === 'Manager' ? 'selected' : '' }}>Manager</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeModal('editEmployeeModal{{ $staff->staff_id }}')" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="deleteEmployeeModal{{ $staff->staff_id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-[28px] bg-white p-6 shadow-[0_28px_70px_rgba(15,23,42,0.18)]">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-slate-900">Remove Employee</h3>
                    <button onclick="closeModal('deleteEmployeeModal{{ $staff->staff_id }}')" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p class="mt-3 text-sm text-slate-500">Delete {{ $staff->name }} from this branch employee list?</p>
                <form action="{{ route('stores.staff.destroy', [$store, $staff]) }}" method="POST" class="mt-6 flex justify-end gap-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" onclick="closeModal('deleteEmployeeModal{{ $staff->staff_id }}')" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-2xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">Delete</button>
                </form>
            </div>
        </div>
    @endforeach

    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        const staffGridToggle = document.getElementById('staffGridToggle');
        const staffTableToggle = document.getElementById('staffTableToggle');
        const staffGridView = document.getElementById('staffGridView');
        const staffTableView = document.getElementById('staffTableView');
        const staffSearchInput = document.getElementById('staffSearchInput');
        const staffSearchClear = document.getElementById('staffSearchClear');
        const staffShowingSelect = document.getElementById('staffShowingSelect');
        const staffRoleFilter = document.getElementById('staffRoleFilter');
        const staffExportButton = document.getElementById('staffExportButton');
        const staffActiveFilter = document.getElementById('staffActiveFilter');
        const staffInactiveFilter = document.getElementById('staffInactiveFilter');
        let selectedStatusFilter = '';

        const filterStaffRecords = () => {
            const keyword = staffSearchInput?.value || '';
            const normalizedKeyword = keyword.trim().toLowerCase();
            const selectedLimit = staffShowingSelect?.value || '25';
            const selectedRole = (staffRoleFilter?.value || '').trim().toLowerCase();
            const matchedCount = {
                row: 0,
                card: 0,
            };

            document.querySelectorAll('.staff-record-row, .staff-record-card').forEach((element) => {
                const searchableText = element.dataset.staffSearch || '';
                const roleText = (element.dataset.staffRole || '').trim().toLowerCase();
                const statusText = (element.dataset.staffStatus || '').trim().toLowerCase();
                const keywordMatches = normalizedKeyword === '' || searchableText.includes(normalizedKeyword);
                const roleMatches = selectedRole === '' || roleText === selectedRole;
                const statusMatches = selectedStatusFilter === '' || statusText === selectedStatusFilter;
                const matches = keywordMatches && roleMatches && statusMatches;
                const isRow = element.classList.contains('staff-record-row');
                const groupKey = isRow ? 'row' : 'card';
                const withinLimit = selectedLimit === 'all' || matchedCount[groupKey] < Number(selectedLimit);

                if (matches) {
                    matchedCount[groupKey] += 1;
                }

                element.classList.toggle('hidden', !(matches && withinLimit));
            });

            if (staffSearchClear) {
                staffSearchClear.classList.toggle('invisible', normalizedKeyword === '');
            }

            staffActiveFilter?.classList.toggle('shadow-sm', selectedStatusFilter === 'active');
            staffInactiveFilter?.classList.toggle('shadow-sm', selectedStatusFilter === 'inactive');
        };

        const setStaffView = (view) => {
            const showGrid = view === 'grid';

            staffGridView?.classList.toggle('hidden', !showGrid);
            staffTableView?.classList.toggle('hidden', showGrid);
            staffGridToggle?.classList.toggle('is-active', showGrid);
            staffTableToggle?.classList.toggle('is-active', !showGrid);
        };

        staffGridToggle?.addEventListener('click', () => setStaffView('grid'));
        staffTableToggle?.addEventListener('click', () => setStaffView('table'));
        staffSearchInput?.addEventListener('input', filterStaffRecords);
        staffShowingSelect?.addEventListener('change', filterStaffRecords);
        staffRoleFilter?.addEventListener('change', filterStaffRecords);
        staffActiveFilter?.addEventListener('click', () => {
            selectedStatusFilter = selectedStatusFilter === 'active' ? '' : 'active';
            filterStaffRecords();
        });
        staffInactiveFilter?.addEventListener('click', () => {
            selectedStatusFilter = selectedStatusFilter === 'inactive' ? '' : 'inactive';
            filterStaffRecords();
        });
        staffSearchClear?.addEventListener('click', () => {
            if (staffSearchInput) {
                staffSearchInput.value = '';
            }

            filterStaffRecords();
        });

        staffExportButton?.addEventListener('click', () => {
            const params = new URLSearchParams({
                search: staffSearchInput?.value || '',
                role: staffRoleFilter?.value || '',
                showing: staffShowingSelect?.value || '25',
            });

            window.location.href = `{{ route('stores.staff.export.pdf', $store) }}?${params.toString()}`;
        });

        if (staffSearchInput) {
            staffSearchInput.value = '';
        }

        if (staffRoleFilter) {
            staffRoleFilter.value = '';
        }

        filterStaffRecords();
    </script>
@endsection
