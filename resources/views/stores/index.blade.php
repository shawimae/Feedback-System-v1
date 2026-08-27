@extends('layouts.admin')

@section('content')
    @php
        $storeTypeOptions = ['Retail', 'Restaurant', 'Service Center', 'Corporate Office', 'Warehouse', 'Other'];
        $canManageStoreCreation = auth()->user()?->isDev() || auth()->user()?->isSuperAdmin();
        $canAddStoreUnderLicense = $licenseSummary['can_create_store'] ?? true;
    @endphp
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-bold text-slate-800">Stores</h2>
            <p class="text-slate-500 mt-2">Manage store details, status, and QR survey access.</p>
        </div>

        <div class="flex flex-wrap gap-3">
            @if($canManageStoreCreation)
                <button
                    type="button"
                    id="bulkSelectToggle"
                    class="inline-flex items-center gap-2 rounded-[14px] border border-slate-200 bg-white px-3.5 py-2 text-[13px] font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Select Stores
                </button>
            @endif

            <a
                href="{{ route('stores.ranks') }}"
                class="inline-flex items-center gap-2 rounded-[18px] border border-sky-200 bg-gradient-to-b from-white to-sky-50 px-4 py-2.5 text-[15px] font-semibold text-sky-800 shadow-[0_8px_20px_-16px_rgba(14,116,144,0.45)] transition hover:-translate-y-0.5 hover:border-sky-300 hover:from-sky-50 hover:to-cyan-50 hover:text-sky-900"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9a2.25 2.25 0 0 1-2.25-2.25v-9A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9A2.25 2.25 0 0 1 16.5 18.75Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15.75 10.5 13.5l1.5 1.5 3.75-3.75" />
                </svg>
                Store Ranks
            </a>

            @if($canManageStoreCreation)
                <button
                    onclick="{{ $canAddStoreUnderLicense ? "openModal('addStoreModal')" : 'return false;' }}"
                    class="inline-flex items-center gap-2 rounded-[18px] bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 px-4 py-2.5 text-[15px] font-semibold text-white shadow-[0_14px_28px_-18px_rgba(15,23,42,0.75)] transition hover:-translate-y-0.5 hover:from-slate-800 hover:via-slate-700 hover:to-slate-800"
                    @if(! $canAddStoreUnderLicense) disabled title="{{ $licenseSummary['message'] ?? 'Blocked by license settings.' }}" @endif
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Store
                </button>
            @endif
        </div>
    </div>

    @if($canManageStoreCreation && !($licenseSummary['can_create_store'] ?? true))
        <div class="mb-5 rounded-[18px] border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ $licenseSummary['message'] ?? 'Store creation is currently blocked by the software license settings.' }}
            @if(($licenseSummary['max_stores'] ?? 0) > 0)
                Current usage: {{ $licenseSummary['current_store_count'] ?? 0 }} / {{ $licenseSummary['max_stores'] }} stores.
            @endif
        </div>
    @endif

    <div id="bulkActionBar" class="mb-4 hidden items-center justify-between gap-3 rounded-[18px] border border-slate-200 bg-white px-3.5 py-3 shadow-sm">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Select Mode</p>
            <p class="mt-1 text-sm font-medium text-slate-700"><span id="bulkSelectedCount">0</span> selected</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button
                type="button"
                id="bulkDeleteButton"
                class="inline-flex items-center gap-2 rounded-[14px] border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-50"
                disabled
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5h10.5M9.75 7.5v-1.5A.75.75 0 0 1 10.5 5.25h3a.75.75 0 0 1 .75.75v1.5m-6 0 .6 9.15a.75.75 0 0 0 .75.7h4.8a.75.75 0 0 0 .75-.7l.6-9.15" />
                </svg>
                Delete Selected
            </button>

            <button
                type="button"
                id="bulkCancelButton"
                class="rounded-[14px] border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Cancel
            </button>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @forelse($stores as $store)
            <div
                class="store-card group relative overflow-hidden rounded-[18px] border border-slate-200 bg-white p-3.5 shadow-[0_10px_24px_-18px_rgba(15,23,42,0.16)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_34px_-18px_rgba(15,23,42,0.2)] min-h-[150px]"
                data-store-card
                data-store-id="{{ $store->store_id }}"
            >
                <label class="bulk-select-toggle absolute left-3 top-3 z-20 hidden h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-slate-200 bg-white/95 shadow-sm">
                    <input
                        type="checkbox"
                        name="store_ids[]"
                        value="{{ $store->store_id }}"
                        form="bulkDeleteForm"
                        class="bulk-store-checkbox sr-only"
                        data-store-checkbox
                    >
                    <span class="pointer-events-none inline-flex h-4.5 w-4.5 rounded-full border-2 border-slate-300 bg-white transition"></span>
                </label>

                <div class="flex h-full flex-col gap-3">
                    <div class="flex items-start justify-between gap-3">
                        @if($store->profile_photo_url)
                            <img src="{{ $store->profile_photo_url }}" alt="{{ $store->name }} logo" class="h-10 w-16 shrink-0 rounded-xl border border-orange-100 bg-white p-1 object-contain">
                        @else
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-50 text-orange-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.25h15" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 10.25V7.75A1.75 1.75 0 0 1 7.75 6h8.5A1.75 1.75 0 0 1 18 7.75v2.5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 10.25v7A1.75 1.75 0 0 0 7 19h10a1.75 1.75 0 0 0 1.75-1.75v-7" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.25 19v-4.25A1.25 1.25 0 0 1 10.5 13.5h3a1.25 1.25 0 0 1 1.25 1.25V19" />
                                </svg>
                            </span>
                        @endif

                        <div class="flex items-center gap-1.5">
                            <button
                                onclick="openModal('viewStoreModal{{ $store->store_id }}')"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-[9px] border border-slate-300 bg-white text-slate-600 transition hover:border-orange-300 hover:bg-orange-50 hover:text-orange-500"
                                aria-label="Edit store"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 5.25 18.75 9m-10.5 9.75 3.25-.75L19.5 10a1.768 1.768 0 0 0 0-2.5l-1-1a1.768 1.768 0 0 0-2.5 0L8 14.5l-.75 3.25Z" />
                                </svg>
                            </button>

                            <button
                                onclick="openModal('viewStoreModal{{ $store->store_id }}')"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-[9px] border border-sky-300 bg-white text-sky-500 transition hover:bg-sky-50"
                                aria-label="View details"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z" />
                                </svg>
                            </button>

                            @if($canManageStoreCreation)
                                <button
                                    type="button"
                                    onclick="openModal('deleteStoreModal{{ $store->store_id }}')"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-[9px] border border-rose-200 bg-white text-rose-400 transition hover:bg-rose-50"
                                    aria-label="Delete store"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5h10.5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 7.5v-1.5A.75.75 0 0 1 10.5 5.25h3a.75.75 0 0 1 .75.75v1.5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 7.5.6 9.15a.75.75 0 0 0 .75.7h4.8a.75.75 0 0 0 .75-.7l.6-9.15" />
                                    </svg>
                                </button>
                            @endif

                            <details class="relative">
                                <summary class="inline-flex h-7 w-7 cursor-pointer list-none items-center justify-center rounded-[9px] border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="12" cy="5" r="1.75" />
                                        <circle cx="12" cy="12" r="1.75" />
                                        <circle cx="12" cy="19" r="1.75" />
                                    </svg>
                                </summary>
                                <div class="absolute right-0 top-10 z-20 w-44 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_16px_32px_rgba(15,23,42,0.12)]">
                                    @if(auth()->user()?->hasFeature(\App\Models\User::FEATURE_STAFF))
                                        <a href="{{ route('stores.staff.index', $store) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Manage Staff</a>
                                    @endif
                                </div>
                            </details>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <h3 class="line-clamp-1 text-[18px] font-semibold leading-tight text-slate-900">{{ $store->name }}</h3>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $store->store_number }}</p>
                    </div>
                    <div class="mt-auto flex items-center justify-between pt-2">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-[10px] font-semibold {{ $store->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                            {{ ucfirst($store->status) }}
                        </span>

                        <div class="flex items-center gap-2 text-[12px] text-slate-500">
                            <span>ID: {{ $store->store_id }}</span>
                            <a
                                href="{{ route('feedbacks.index', ['store_id' => $store->store_id]) }}"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-white px-3 py-1.5 text-[12px] font-semibold text-emerald-600 transition hover:bg-emerald-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 5.75h14.5a1 1 0 0 1 1 1v8.5a1 1 0 0 1-1 1H9l-4.25 3v-12.5a1 1 0 0 1 1-1Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.25h8.5M8.25 12.5h5" />
                                </svg>
                                Feedback
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div id="viewStoreModal{{ $store->store_id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 p-4 backdrop-blur-sm">
                <div class="w-full max-w-4xl max-h-[92vh] overflow-y-auto rounded-[30px] border border-white/70 bg-white/95 shadow-[0_28px_70px_rgba(15,23,42,0.18)] backdrop-blur-xl">
                    <div class="flex items-center justify-between border-b border-slate-200/80 bg-gradient-to-b from-slate-50 to-white px-5 py-3.5">
                        <div>
                            <h3 class="text-[22px] font-semibold tracking-tight text-slate-800">{{ $store->name }}</h3>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $store->store_number }}</p>
                        </div>

                        <button onclick="closeModal('viewStoreModal{{ $store->store_id }}')" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid gap-4 p-4 xl:grid-cols-[280px,1fr]">
                        <div class="space-y-4">
                            <div class="rounded-[24px] border border-white/80 bg-slate-50/90 p-3.5 shadow-[0_12px_28px_rgba(15,23,42,0.05)]">
                                <h4 class="mb-2 text-[11px] font-medium uppercase tracking-[0.18em] text-slate-500">Store Logo</h4>
                                <p class="mb-3 text-[11px] leading-5 text-slate-500">
                                    This branch logo is used on this store's questionnaire and customer-facing survey pages.
                                </p>

                                @if($store->profile_photo_url)
                                    <div class="mb-4 rounded-2xl border border-slate-200 bg-white p-2.5 text-center">
                                        <img src="{{ $store->profile_photo_url }}" class="mx-auto h-28 w-full max-w-[220px] rounded-2xl bg-white p-3 object-contain" alt="{{ $store->name }} logo">
                                    </div>
                                @else
                                    <div class="mb-4 rounded-2xl border border-dashed border-slate-300 bg-white p-4 text-center text-[11px] text-slate-400">
                                        No store logo uploaded yet.
                                    </div>
                                @endif

                                <h4 class="mb-2 text-[11px] font-medium uppercase tracking-[0.18em] text-slate-500">QR Code</h4>

                                @if($store->qr_code_path)
                                    <div class="rounded-2xl border border-slate-200 bg-white p-2.5 text-center">
                                        <img src="{{ asset('storage/' . $store->qr_code_path) }}" class="mx-auto h-28 w-28" alt="QR">
                                    </div>
                                @else
                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-4 text-center text-[11px] text-slate-400">
                                        No QR code generated yet.
                                    </div>
                                @endif

                                <div class="mt-2.5 space-y-2">
                                    @if($store->qr_code_path)
                                        <a href="{{ asset('storage/' . $store->qr_code_path) }}" target="_blank" class="block w-full rounded-2xl bg-slate-900 px-4 py-2 text-center text-[11px] font-medium text-white transition hover:bg-slate-800">
                                            View QR Image
                                        </a>
                                    @endif

                                    @if($store->qr_pdf_path)
                                        <a href="{{ asset('storage/' . $store->qr_pdf_path) }}" target="_blank" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-2 text-center text-[11px] font-medium text-slate-700 transition hover:bg-slate-50">
                                            View QR PDF
                                        </a>
                                    @endif

                                    @if($store->qr_url)
                                        <div class="rounded-2xl border border-slate-200 bg-white p-2.5">
                                            <p class="text-[10px] font-medium uppercase tracking-[0.18em] text-slate-400">Survey Link</p>
                                            <div class="mt-1.5 flex items-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                                <a href="{{ $store->qr_url }}" target="_blank" class="inline-flex h-10 w-10 shrink-0 items-center justify-center border-r border-slate-200 bg-white text-slate-400 transition hover:text-slate-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 0 0-5.656 0l-2 2a4 4 0 0 0 5.656 5.656l1-1M10.172 13.828a4 4 0 0 0 5.656 0l2-2a4 4 0 1 0-5.656-5.656l-1 1" />
                                                    </svg>
                                                </a>
                                                <a href="{{ $store->qr_url }}" target="_blank" class="min-w-0 flex-1 truncate px-3 text-[11px] font-medium text-slate-500 hover:text-slate-700">
                                                    {{ $store->qr_url }}
                                                </a>
                                                <button
                                                    type="button"
                                                    id="copyStoreLinkBtn{{ $store->store_id }}"
                                                    onclick="copyStoreLink('copyStoreLinkBtn{{ $store->store_id }}', @js($store->qr_url))"
                                                    class="shrink-0 self-stretch bg-emerald-500 px-4 text-[11px] font-medium text-white transition hover:bg-emerald-600"
                                                >
                                                    Copy
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[24px] border border-white/80 bg-white/88 p-4 shadow-[0_12px_28px_rgba(15,23,42,0.05)]">
                            <div class="mb-3 flex items-center justify-between">
                                <h4 class="text-[11px] font-medium uppercase tracking-[0.18em] text-slate-500">Store Details</h4>

                                <div id="storeActions{{ $store->store_id }}" class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onclick="toggleEditForm('storeForm{{ $store->store_id }}', 'viewOnly{{ $store->store_id }}', 'storeActions{{ $store->store_id }}')"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-[14px] border border-slate-300 bg-white text-slate-600 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-600"
                                        title="Edit store"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[17px] w-[17px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.55">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.1 2.1 0 1 1 2.97 2.97L8.75 18.54 4 20l1.46-4.75 11.402-10.763Z" />
                                        </svg>
                                    </button>

                                    @if($canManageStoreCreation)
                                        <button
                                            type="button"
                                            onclick="openModal('deleteStoreModal{{ $store->store_id }}')"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-[14px] border border-rose-200 bg-white text-rose-500 transition hover:bg-rose-50 hover:text-rose-600"
                                            title="Delete store"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[17px] w-[17px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.55">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6m4-6v6M4 7h16m-3 0-.867 12.142A2 2 0 0 1 14.138 21H9.862a2 2 0 0 1-1.995-1.858L7 7m3-3h4a1 1 0 0 1 1 1v2H9V5a1 1 0 0 1 1-1Z" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div id="viewOnly{{ $store->store_id }}" class="grid gap-2.5 text-xs md:grid-cols-2">
                                <div class="rounded-2xl bg-slate-50/80 p-3">
                                    <p class="text-slate-400 text-[11px] font-medium uppercase tracking-wide">Store Number</p>
                                    <p class="mt-1 text-sm font-medium text-slate-800">{{ $store->store_number }}</p>
                                </div>

                                <div class="rounded-2xl bg-slate-50/80 p-3">
                                    <p class="text-slate-400 text-[11px] font-medium uppercase tracking-wide">Store Name</p>
                                    <p class="mt-1 text-sm font-medium text-slate-800">{{ $store->name }}</p>
                                </div>

                                <div class="rounded-2xl bg-slate-50/80 p-3">
                                    <p class="text-slate-400 text-[11px] font-medium uppercase tracking-wide">Store Manager</p>
                                    <p class="mt-1 text-sm font-medium text-slate-800">{{ $store->store_manager ?: '—' }}</p>
                                </div>

                                <div class="rounded-2xl bg-slate-50/80 p-3">
                                    <p class="text-slate-400 text-[11px] font-medium uppercase tracking-wide">Email</p>
                                    <p class="mt-1 text-sm font-medium text-slate-800">{{ $store->email ?: '—' }}</p>
                                </div>

                                <div class="rounded-2xl bg-slate-50/80 p-3">
                                    <p class="text-slate-400 text-[11px] font-medium uppercase tracking-wide">Phone</p>
                                    <p class="mt-1 text-sm font-medium text-slate-800">{{ $store->phone ?: '—' }}</p>
                                </div>

                                <div class="rounded-2xl bg-slate-50/80 p-3">
                                    <p class="text-slate-400 text-[11px] font-medium uppercase tracking-wide">Status</p>
                                    <p class="mt-1 text-sm font-medium {{ $store->status === 'active' ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ ucfirst($store->status) }}
                                    </p>
                                </div>

                                <div class="rounded-2xl bg-slate-50/80 p-3 md:col-span-2">
                                    <p class="text-slate-400 text-[11px] font-medium uppercase tracking-wide">Address</p>
                                    <p class="mt-1 text-sm font-medium text-slate-800">{{ $store->address ?: '—' }}</p>
                                </div>

                            </div>

                            <form id="storeForm{{ $store->store_id }}" action="{{ route('stores.update', $store) }}" method="POST" enctype="multipart/form-data" class="hidden grid gap-4 rounded-[22px] border border-emerald-200 bg-emerald-50/40 p-3 md:grid-cols-2">
                                @csrf
                                @method('PUT')

                                <div>
                                    <label class="mb-2 block text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Store Number</label>
                                    <input type="text" value="{{ $store->store_number }}" class="w-full rounded-2xl border border-emerald-300 bg-white px-4 py-3 ring-2 ring-emerald-100" readonly>
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Store Name</label>
                                    <input type="text" name="name" value="{{ $store->name }}" class="w-full rounded-2xl border border-emerald-300 bg-white px-4 py-3 outline-none ring-2 ring-emerald-100 transition focus:border-emerald-400 focus:ring-emerald-200">
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Store Manager</label>
                                    <input type="text" name="store_manager" value="{{ $store->store_manager }}" class="w-full rounded-2xl border border-emerald-300 bg-white px-4 py-3 outline-none ring-2 ring-emerald-100 transition focus:border-emerald-400 focus:ring-emerald-200">
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Store Type</label>
                                    <select name="store_type" data-store-type-select class="w-full rounded-2xl border border-emerald-300 bg-white px-4 py-3 outline-none ring-2 ring-emerald-100 transition focus:border-emerald-400 focus:ring-emerald-200">
                                        <option value="">Select type...</option>
                                        @foreach($storeTypeOptions as $storeTypeOption)
                                            <option value="{{ $storeTypeOption }}" {{ ($store->store_type ?? '') === $storeTypeOption ? 'selected' : '' }}>{{ $storeTypeOption }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="{{ ($store->store_type ?? '') === 'Other' ? '' : 'hidden' }}" data-store-type-other-wrap>
                                    <label class="mb-2 block text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Other Business Type</label>
                                    <input type="text" name="store_type_other" value="{{ $store->store_type_other }}" data-store-type-other-input class="w-full rounded-2xl border border-emerald-300 bg-white px-4 py-3 outline-none ring-2 ring-emerald-100 transition focus:border-emerald-400 focus:ring-emerald-200" placeholder="Enter company/business type">
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Email</label>
                                    <input type="email" name="email" value="{{ $store->email }}" class="w-full rounded-2xl border border-emerald-300 bg-white px-4 py-3 outline-none ring-2 ring-emerald-100 transition focus:border-emerald-400 focus:ring-emerald-200">
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Phone</label>
                                    <input type="text" name="phone" value="{{ $store->phone }}" class="w-full rounded-2xl border border-emerald-300 bg-white px-4 py-3 outline-none ring-2 ring-emerald-100 transition focus:border-emerald-400 focus:ring-emerald-200">
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Status</label>
                                    <select name="status" class="w-full rounded-2xl border border-emerald-300 bg-white px-4 py-3 outline-none ring-2 ring-emerald-100 transition focus:border-emerald-400 focus:ring-emerald-200">
                                        <option value="active" {{ $store->status === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ $store->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Address</label>
                                <input type="text" name="address" value="{{ $store->address }}" class="w-full rounded-2xl border border-emerald-300 bg-white px-4 py-3 outline-none ring-2 ring-emerald-100 transition focus:border-emerald-400 focus:ring-emerald-200">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Store Logo</label>
                                    @if($store->profile_photo_url)
                                        <div class="mb-3 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-white px-3 py-3">
                                            <img src="{{ $store->profile_photo_url }}" alt="{{ $store->name }} logo" class="h-14 w-20 rounded-2xl bg-white p-1.5 object-contain">
                                            <div>
                                                <p class="text-sm font-medium text-slate-800">Current uploaded logo</p>
                                                <p class="text-xs text-slate-500">Upload a new image to replace this store logo for this branch only.</p>
                                            </div>
                                        </div>
                                    @endif
                                    <input type="file" name="profile_photo" accept="image/*" class="w-full rounded-2xl border border-emerald-300 bg-white px-4 py-3 text-sm outline-none ring-2 ring-emerald-100 transition file:mr-4 file:rounded-full file:border-0 file:bg-emerald-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-emerald-700 hover:file:bg-emerald-200 focus:border-emerald-400 focus:ring-emerald-200">
                                    <p class="mt-2 text-xs text-slate-500">
                                        The questionnaire logo is based on the selected store, so each branch can have a different updated logo.
                                    </p>
                                </div>

                                <div class="md:col-span-2 flex items-center gap-3 pt-2">
                                    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 font-medium text-white hover:bg-slate-800">
                                        Save Changes
                                    </button>

                                    <button
                                        type="button"
                                        onclick="cancelEditForm('storeForm{{ $store->store_id }}', 'viewOnly{{ $store->store_id }}', 'storeActions{{ $store->store_id }}')"
                                        class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if($canManageStoreCreation)
                <div id="deleteStoreModal{{ $store->store_id }}" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/35 p-4 backdrop-blur-[2px]">
                    <div class="w-full max-w-sm rounded-[20px] border border-slate-200 bg-white p-4 shadow-[0_18px_40px_-24px_rgba(15,23,42,0.28)]">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-[0.16em] text-slate-400">Delete Store</p>
                                <h4 class="mt-1 text-base font-semibold text-slate-800">Remove {{ $store->name }}?</h4>
                                <p class="mt-2 text-sm text-slate-500">This will permanently delete this store.</p>
                            </div>

                            <button type="button" onclick="closeModal('deleteStoreModal{{ $store->store_id }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-4 flex items-center justify-end gap-2.5">
                            <button type="button" onclick="closeModal('deleteStoreModal{{ $store->store_id }}')" class="rounded-[12px] border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                Cancel
                            </button>

                            <form action="{{ route('stores.destroy', $store) }}" method="POST">
                                @csrf
                                @method('DELETE')

                                <button class="rounded-[12px] bg-rose-500 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-rose-600">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="col-span-full rounded-[28px] bg-white border border-dashed border-slate-300 p-12 text-center text-slate-500">
                {{ $canManageStoreCreation ? 'No stores yet. Click Add Store to create your first branch.' : 'No accessible stores found for this account.' }}
            </div>
        @endforelse
    </div>

    @if($canManageStoreCreation)
        <form id="bulkDeleteForm" action="{{ route('stores.bulk-destroy') }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif

    <div id="bulkDeleteConfirmModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-900/35 p-4 backdrop-blur-[2px]">
        <div class="w-full max-w-sm rounded-[20px] border border-slate-200 bg-white p-4 shadow-[0_18px_40px_-24px_rgba(15,23,42,0.28)]">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[10px] font-medium uppercase tracking-[0.16em] text-slate-400">Bulk Delete</p>
                    <h4 class="mt-1 text-base font-semibold text-slate-800">Delete selected stores?</h4>
                    <p class="mt-2 text-sm text-slate-500">This will remove <span id="bulkDeleteCountLabel">0</span> selected store(s).</p>
                </div>

                <button type="button" onclick="closeModal('bulkDeleteConfirmModal')" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-4 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeModal('bulkDeleteConfirmModal')" class="rounded-[12px] border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Cancel
                </button>

                <button type="button" id="confirmBulkDeleteButton" class="rounded-[12px] bg-rose-500 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-rose-600">
                    Delete All Selected
                </button>
            </div>
        </div>
    </div>

    @if($canManageStoreCreation)
    <div id="addStoreModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 p-4 backdrop-blur-sm">
        <div class="w-full max-w-xl overflow-hidden rounded-[30px] border border-white/70 bg-white/95 shadow-[0_28px_70px_rgba(15,23,42,0.18)] backdrop-blur-xl">
            <div class="flex items-center justify-between border-b border-slate-200/80 bg-gradient-to-b from-slate-50 to-white px-5 py-4">
                <div>
                    <h3 class="text-[26px] font-bold tracking-tight text-slate-800">Add Store</h3>
                    <p class="mt-1 text-sm text-slate-500">Create a new branch. Store number will be generated automatically.</p>
                </div>

                <button onclick="closeModal('addStoreModal')" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('stores.store') }}" method="POST" enctype="multipart/form-data" class="grid gap-4 p-5 md:grid-cols-2">
                @csrf

                <div class="md:col-span-2">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Store Name</label>
                    <input type="text" name="name" class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-300 focus:bg-white focus:ring-4 focus:ring-blue-100/70" placeholder="JDMT BGC">
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Store Manager</label>
                    <input type="text" name="store_manager" class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-300 focus:bg-white focus:ring-4 focus:ring-blue-100/70" placeholder="Manager name">
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Store Type</label>
                    <select name="store_type" data-store-type-select class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-300 focus:bg-white focus:ring-4 focus:ring-blue-100/70">
                        <option value="">Select type...</option>
                        @foreach($storeTypeOptions as $storeTypeOption)
                            <option value="{{ $storeTypeOption }}" {{ old('store_type') === $storeTypeOption ? 'selected' : '' }}>{{ $storeTypeOption }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2 {{ old('store_type') === 'Other' ? '' : 'hidden' }}" data-store-type-other-wrap>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Other Business Type</label>
                    <input type="text" name="store_type_other" value="{{ old('store_type_other') }}" data-store-type-other-input class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-300 focus:bg-white focus:ring-4 focus:ring-blue-100/70" placeholder="Enter company/business type">
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Email</label>
                    <input type="email" name="email" class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-300 focus:bg-white focus:ring-4 focus:ring-blue-100/70" placeholder="store@email.com">
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Phone</label>
                    <input type="text" name="phone" class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-300 focus:bg-white focus:ring-4 focus:ring-blue-100/70" placeholder="09xxxxxxxxx">
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Status</label>
                    <select name="status" class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-300 focus:bg-white focus:ring-4 focus:ring-blue-100/70">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Address</label>
                    <input type="text" name="address" class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-300 focus:bg-white focus:ring-4 focus:ring-blue-100/70" placeholder="Store address">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Store Logo / Profile Picture</label>
                    <input type="file" name="profile_photo" accept="image/*" class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-800 outline-none transition file:mr-4 file:rounded-full file:border-0 file:bg-sky-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-sky-700 hover:file:bg-sky-200 focus:border-blue-300 focus:bg-white focus:ring-4 focus:ring-blue-100/70">
                    <p class="mt-2 text-xs text-slate-500">Ito ang gagamitin bilang logo ng questionnaire at survey pages ng specific na store na ito.</p>
                </div>

                <div class="md:col-span-2 flex justify-end gap-3 pt-1">
                    <button type="button" onclick="closeModal('addStoreModal')" class="rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Save Store
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <script>
        let bulkSelectMode = false;

        function openModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function toggleEditForm(formId, viewId, actionsId) {
            const form = document.getElementById(formId);
            const view = document.getElementById(viewId);
            const actions = document.getElementById(actionsId);

            form.classList.remove('hidden');
            view.classList.add('hidden');

            if (actions) {
                actions.classList.add('hidden');
            }
        }

        function cancelEditForm(formId, viewId, actionsId) {
            const form = document.getElementById(formId);
            const view = document.getElementById(viewId);
            const actions = document.getElementById(actionsId);

            form.classList.add('hidden');
            view.classList.remove('hidden');

            if (actions) {
                actions.classList.remove('hidden');
            }
        }

        async function copyStoreLink(buttonId, url) {
            const button = document.getElementById(buttonId);
            const originalText = button ? button.textContent.trim() : 'Copy';

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(url);
                } else {
                    const tempInput = document.createElement('textarea');
                    tempInput.value = url;
                    tempInput.setAttribute('readonly', '');
                    tempInput.style.position = 'absolute';
                    tempInput.style.left = '-9999px';
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    tempInput.setSelectionRange(0, tempInput.value.length);

                    const copied = document.execCommand('copy');
                    document.body.removeChild(tempInput);

                    if (!copied) {
                        throw new Error('Copy failed');
                    }
                }

                if (button) {
                    button.textContent = 'Copied';
                    button.classList.remove('bg-emerald-500', 'hover:bg-emerald-600');
                    button.classList.add('bg-emerald-400');
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.classList.remove('bg-emerald-400');
                        button.classList.add('bg-emerald-500', 'hover:bg-emerald-600');
                    }, 1500);
                }
            } catch (error) {
                if (button) {
                    button.textContent = 'Failed';
                    button.classList.remove('bg-emerald-500', 'hover:bg-emerald-600');
                    button.classList.add('bg-rose-500');
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.classList.remove('bg-rose-500');
                        button.classList.add('bg-emerald-500', 'hover:bg-emerald-600');
                    }, 1500);
                }
            }
        }

        function syncStoreTypeOtherField(select) {
            const wrapper = select.closest('form')?.querySelector('[data-store-type-other-wrap]');
            const input = wrapper?.querySelector('[data-store-type-other-input]');
            const isOther = select.value === 'Other';

            wrapper?.classList.toggle('hidden', !isOther);

            if (!isOther && input) {
                input.value = '';
            }
        }

        const bulkSelectToggle = document.getElementById('bulkSelectToggle');
        const bulkActionBar = document.getElementById('bulkActionBar');
        const bulkDeleteButton = document.getElementById('bulkDeleteButton');
        const bulkCancelButton = document.getElementById('bulkCancelButton');
        const confirmBulkDeleteButton = document.getElementById('confirmBulkDeleteButton');
        const bulkSelectedCount = document.getElementById('bulkSelectedCount');
        const bulkDeleteCountLabel = document.getElementById('bulkDeleteCountLabel');
        const bulkDeleteForm = document.getElementById('bulkDeleteForm');
        const storeCards = Array.from(document.querySelectorAll('[data-store-card]'));
        const storeCheckboxes = Array.from(document.querySelectorAll('[data-store-checkbox]'));

        function getSelectedStoreCount() {
            return storeCheckboxes.filter((checkbox) => checkbox.checked).length;
        }

        function updateBulkSelectionUI() {
            const selectedCount = getSelectedStoreCount();

            bulkSelectedCount.textContent = String(selectedCount);
            bulkDeleteCountLabel.textContent = String(selectedCount);
            bulkDeleteButton.disabled = selectedCount === 0;

            storeCards.forEach((card) => {
                const checkbox = card.querySelector('[data-store-checkbox]');
                const indicator = card.querySelector('.bulk-select-toggle span');
                const isChecked = !!checkbox?.checked;

                card.classList.toggle('ring-2', bulkSelectMode && isChecked);
                card.classList.toggle('ring-rose-300', bulkSelectMode && isChecked);
                card.classList.toggle('bg-rose-50/40', bulkSelectMode && isChecked);

                if (indicator) {
                    indicator.classList.toggle('bg-rose-500', isChecked);
                    indicator.classList.toggle('border-rose-500', isChecked);
                    indicator.classList.toggle('border-slate-300', !isChecked);
                }
            });
        }

        function toggleBulkSelectMode(forceState = null) {
            bulkSelectMode = forceState ?? !bulkSelectMode;

            bulkActionBar.classList.toggle('hidden', !bulkSelectMode);
            bulkActionBar.classList.toggle('flex', bulkSelectMode);

            bulkSelectToggle.innerHTML = bulkSelectMode
                ? `<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>Exit Select`
                : `<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>Select Stores`;

            storeCards.forEach((card) => {
                const toggle = card.querySelector('.bulk-select-toggle');
                toggle?.classList.toggle('hidden', !bulkSelectMode);
                toggle?.classList.toggle('inline-flex', bulkSelectMode);
                card.classList.toggle('cursor-pointer', bulkSelectMode);

                if (!bulkSelectMode) {
                    const checkbox = card.querySelector('[data-store-checkbox]');
                    if (checkbox) {
                        checkbox.checked = false;
                    }
                }
            });

            updateBulkSelectionUI();
        }

        bulkSelectToggle?.addEventListener('click', () => toggleBulkSelectMode());
        bulkCancelButton?.addEventListener('click', () => toggleBulkSelectMode(false));
        bulkDeleteButton?.addEventListener('click', () => {
            if (getSelectedStoreCount() === 0) return;
            openModal('bulkDeleteConfirmModal');
        });
        confirmBulkDeleteButton?.addEventListener('click', () => {
            bulkDeleteForm?.submit();
        });

        storeCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', updateBulkSelectionUI);
        });

        storeCards.forEach((card) => {
            card.addEventListener('click', (event) => {
                if (!bulkSelectMode) {
                    return;
                }

                const interactiveTarget = event.target.closest('button, a, summary, details, form, input, select, textarea');

                if (interactiveTarget && !interactiveTarget.hasAttribute('data-store-checkbox')) {
                    return;
                }

                const checkbox = card.querySelector('[data-store-checkbox]');
                if (!checkbox) {
                    return;
                }

                if (event.target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        });

        document.querySelectorAll('[data-store-type-select]').forEach((select) => {
            select.addEventListener('change', () => syncStoreTypeOtherField(select));
            syncStoreTypeOtherField(select);
        });
    </script>
@endsection
