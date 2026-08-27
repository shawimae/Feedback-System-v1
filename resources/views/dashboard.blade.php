@extends('layouts.admin')

@section('content')
@php
    $isRestrictedToAssignedStore = auth()->user()?->isAdmin();
    $selectedStore = $stores->firstWhere('store_id', $selectedStoreId);
    $isStoreFiltered = filled($selectedStoreId);
    $topBranchLabel = $topBranch['name'] ?? 'No branch yet';
    $topManagerLabel = $topManager['name'] ?? 'No manager yet';
    $serviceCompletion = max(0, min(100, $positiveRate + $neutralRate));
    $storeRatingLabels = collect($branchRankings ?? [])
        ->filter(fn ($store) => !is_null($store['average_rating'] ?? null))
        ->pluck('name')
        ->take(6)
        ->values();
    $storeRatingData = collect($branchRankings ?? [])
        ->filter(fn ($store) => !is_null($store['average_rating'] ?? null))
        ->pluck('average_rating')
        ->map(fn ($rating) => round((float) $rating, 1))
        ->take(6)
        ->values();
@endphp

<style>
    .dashboard-shell {
        border: 1px solid #ececf4;
        background: linear-gradient(180deg, #f6f7ff 0%, #f3f4fb 100%);
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.08);
    }

    .dashboard-filter-card,
    .dashboard-card,
    .dashboard-chart-card,
    .dashboard-table-card {
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 18px 34px rgba(148, 163, 184, 0.12);
    }

    .dashboard-card {
        min-width: 0;
    }

    .dashboard-title {
        font-family: "Arial Black", "Trebuchet MS", sans-serif;
        letter-spacing: 0.04em;
    }

    .dashboard-filter {
        min-height: 42px;
        border: 1px solid #dbe2ee;
        background: #ffffff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }

    .dashboard-filter-search {
        min-width: 220px;
    }

    .dashboard-date-trigger {
        min-width: 248px;
        border: 1px solid #dbe2ee;
        background: linear-gradient(180deg, #ffffff 0%, #f8f9ff 100%);
        box-shadow: 0 10px 18px rgba(88, 18, 42, 0.05);
    }

    .dashboard-date-popover {
        position: absolute;
        right: 0;
        top: calc(100% + 12px);
        z-index: 40;
        width: min(540px, calc(100vw - 24px));
        border-radius: 18px;
        border: 1px solid #dde3ef;
        background: #ffffff;
        box-shadow: 0 20px 36px rgba(15, 23, 42, 0.16);
        overflow: hidden;
    }

    .dashboard-date-popover[hidden] {
        display: none;
    }

    .dashboard-date-sidebar {
        background: linear-gradient(180deg, #fbfbff 0%, #f8f9ff 100%);
        border-right: 1px solid #e4e7ec;
    }

    .dashboard-date-input-shell {
        border: 2px solid #ea580c;
        border-radius: 10px;
        background: #ffffff;
    }

    .dashboard-date-input-shell.is-secondary {
        border-color: #e4e7ec;
    }

    .dashboard-date-input-shell.is-active {
        border-color: #ea580c;
        box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
    }

    .dashboard-apply-button {
        background: linear-gradient(180deg, #f97316 0%, #ea580c 100%);
        box-shadow: 0 10px 18px rgba(234, 88, 12, 0.2);
    }

    .dashboard-calendar-cell {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        font-size: 12px;
        color: #5b6472;
        transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
    }

    .dashboard-calendar-cell:hover {
        background: #fff1e8;
        color: #ea580c;
        transform: translateY(-1px);
    }

    .dashboard-calendar-cell.is-muted {
        color: #c0c6cf;
    }

    .dashboard-calendar-cell.is-in-range {
        background: #fff4ed;
        color: #9a3412;
        border-radius: 0;
    }

    .dashboard-calendar-cell.is-start,
    .dashboard-calendar-cell.is-end {
        background: #ea580c;
        color: #ffffff;
        font-weight: 700;
    }

    .dashboard-calendar-cell.is-start {
        border-radius: 999px 0 0 999px;
    }

    .dashboard-calendar-cell.is-end {
        border-radius: 0 999px 999px 0;
    }

    .dashboard-calendar-cell.is-start.is-end {
        border-radius: 999px;
    }

    .metric-icon {
        background: linear-gradient(180deg, #f97316 0%, #dc2626 100%);
        box-shadow: 0 14px 24px rgba(220, 38, 38, 0.22);
    }

    .metric-content {
        min-width: 0;
        flex: 1;
    }

    .metric-value {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dashboard-tab {
        position: relative;
        color: #7c879b;
    }

    .dashboard-tab.is-active {
        color: #dc2626;
        font-weight: 700;
    }

    .dashboard-tab.is-active::after {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        bottom: -13px;
        height: 3px;
        border-radius: 999px;
        background: linear-gradient(90deg, #f97316 0%, #dc2626 100%);
    }

    .dashboard-badge {
        border-radius: 999px;
        padding: 0.42rem 0.9rem;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }

    .dashboard-badge.good {
        background: #dcfce7;
        color: #15803d;
    }

    .dashboard-badge.mid {
        background: #fef3c7;
        color: #b45309;
    }

    .dashboard-badge.low {
        background: #fee2e2;
        color: #b91c1c;
    }

    .dashboard-table thead th {
        color: #667085;
        font-size: 12px;
        font-weight: 700;
        border-bottom: 1px solid #eef2f7;
    }

    .dashboard-table tbody tr + tr td {
        border-top: 1px solid #f1f5f9;
    }

    .dashboard-table tbody tr:hover {
        background: #fafaff;
    }

    @media (max-width: 767px) {
        .dashboard-date-trigger {
            min-width: 100%;
        }

        .dashboard-date-popover {
            left: 0;
            right: auto;
            width: min(100%, calc(100vw - 24px));
        }
    }
</style>

<div class="dashboard-shell rounded-[28px] p-4 sm:p-5">
    <div class="dashboard-filter-card rounded-[24px] p-4 sm:p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-[#dc2626]">Feedback Overview</p>
                <h1 class="dashboard-title mt-2 text-[22px] uppercase leading-none text-slate-800 sm:text-[26px]">
                    Customer Feedback Dashboard
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    {{ $selectedStore?->name ?? 'All branches' }} | {{ $dateRangeLabel }}
                </p>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-2.5 xl:justify-end" id="dashboardFiltersForm">
                <div class="flex flex-col gap-1">
                    <label for="dashboardStoreSearch" class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Branch</label>
                    <input type="hidden" id="dashboardStore" name="store_id" value="{{ $selectedStoreId }}">
                    @if($isRestrictedToAssignedStore)
                        <div class="dashboard-filter dashboard-filter-search flex items-center rounded-2xl px-3 py-2.5 text-[11px] font-semibold text-slate-700 shadow-sm">
                            {{ $selectedStore?->name ?? 'Assigned Branch' }}
                        </div>
                    @else
                        <input
                            id="dashboardStoreSearch"
                            type="text"
                            list="dashboardStoreOptions"
                            value="{{ $selectedStore?->name ?? 'All Branches' }}"
                            placeholder="Type branch name..."
                            class="dashboard-filter dashboard-filter-search rounded-2xl px-3 py-2.5 text-[11px] text-slate-700 shadow-sm outline-none transition focus:border-[#ea580c]"
                            autocomplete="off"
                        >
                        <datalist id="dashboardStoreOptions">
                            <option value="All Branches" data-store-id=""></option>
                            @foreach($stores as $store)
                                <option value="{{ $store->name }}" data-store-id="{{ $store->store_id }}"></option>
                            @endforeach
                        </datalist>
                    @endif
                </div>

                <div class="relative flex flex-col gap-1">
                    <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Date Filter</label>
                    <input id="dashboardDateFrom" type="hidden" name="date_from" value="{{ $dateFrom }}">
                    <input id="dashboardDateTo" type="hidden" name="date_to" value="{{ $dateTo }}">

                    <button
                        type="button"
                        id="dashboardDateTrigger"
                        class="dashboard-date-trigger inline-flex min-h-[42px] items-center gap-2 rounded-2xl px-3 py-2.5 text-left text-[12px] text-slate-700"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                    >
                        <span class="text-[#ea580c]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M8 2v4"/>
                                <path d="M16 2v4"/>
                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                <path d="M3 10h18"/>
                            </svg>
                        </span>
                        <span id="dashboardDateTriggerLabel" class="flex-1 truncate">{{ $dateRangeLabel }}</span>
                        <span class="text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="m7 10 5 5 5-5z"/>
                            </svg>
                        </span>
                    </button>

                    <div id="dashboardDatePopover" class="dashboard-date-popover" hidden>
                        <div class="grid md:grid-cols-[215px_minmax(0,1fr)]">
                            <div class="dashboard-date-sidebar p-4">
                                <p class="text-[12px] text-slate-400">Select Date Range:</p>

                                <select id="dashboardPresetSelect" class="mt-3 w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-[12px] text-slate-700 outline-none">
                                    <option value="custom">Custom</option>
                                    <option value="today">Today</option>
                                    <option value="this_week">This Week</option>
                                    <option value="this_month">This Month</option>
                                </select>

                                <label id="dashboardDateFromShell" class="dashboard-date-input-shell is-active mt-6 flex cursor-pointer items-center gap-2 px-3 py-2.5">
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-[#ea580c]">Start</span>
                                    <input id="dashboardDateFromNative" type="date" value="{{ $dateFrom }}" class="w-full border-0 bg-transparent p-0 text-[13px] text-slate-500 outline-none">
                                </label>

                                <label id="dashboardDateToShell" class="dashboard-date-input-shell is-secondary mt-3 flex cursor-pointer items-center gap-2 px-3 py-2.5">
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">End</span>
                                    <input id="dashboardDateToNative" type="date" value="{{ $dateTo }}" class="w-full border-0 bg-transparent p-0 text-[13px] text-slate-500 outline-none">
                                </label>

                                <button type="button" id="dashboardDateApply" class="dashboard-apply-button mt-6 inline-flex w-full items-center justify-center rounded-full px-4 py-2.5 text-sm font-semibold text-white">
                                    Apply
                                </button>
                            </div>

                            <div class="p-4">
                                <div class="flex items-center justify-between">
                                    <button type="button" id="dashboardMonthPrev" class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Previous month">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m15 18-6-6 6-6"/>
                                        </svg>
                                    </button>
                                    <h3 id="dashboardMonthLabel" class="text-[16px] font-semibold text-slate-800"></h3>
                                    <button type="button" id="dashboardMonthNext" class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Next month">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m9 18 6-6-6-6"/>
                                        </svg>
                                    </button>
                                </div>

                                <div class="mt-5 grid grid-cols-7 gap-y-2 text-center text-[12px] text-slate-400">
                                    <span>S</span>
                                    <span>M</span>
                                    <span>T</span>
                                    <span>W</span>
                                    <span>T</span>
                                    <span>F</span>
                                    <span>S</span>
                                </div>

                                <div id="dashboardCalendarGrid" class="mt-4 grid grid-cols-7 gap-y-2 text-center"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <a
                    href="{{ route('dashboard.export', ['store_id' => $selectedStoreId, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                    class="rounded-2xl border border-orange-200 bg-orange-50 px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wide text-orange-700 transition hover:bg-orange-100"
                >
                    Export PDF
                </a>
            </form>
        </div>
    </div>

    @if($isStoreFiltered)
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <article class="dashboard-card rounded-[18px] p-3">
                <div class="flex items-center gap-3">
                    <span class="metric-icon inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v10H7l-3 3V5Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h8M8 13h5" />
                        </svg>
                    </span>
                    <div class="metric-content">
                        <p class="metric-value text-[22px] font-bold leading-none text-slate-800">{{ $totalFeedback }}</p>
                        <p class="mt-1 text-[11px] font-medium leading-tight text-slate-500">Total Response</p>
                    </div>
                </div>
            </article>

            <article class="dashboard-card rounded-[18px] p-3">
                <div class="flex items-center gap-3">
                    <span class="metric-icon inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="m12 2.75 2.83 5.73 6.32.92-4.57 4.45 1.08 6.3L12 17.17l-5.66 2.98 1.08-6.3L2.85 9.4l6.32-.92L12 2.75Z"/>
                        </svg>
                    </span>
                    <div class="metric-content">
                        <p class="metric-value text-[22px] font-bold leading-none text-slate-800">{{ !is_null($storeFocusedAverageRating) ? number_format($storeFocusedAverageRating, 1) : '0.0' }}</p>
                        <p class="mt-1 text-[11px] font-medium leading-tight text-slate-500">Ave Rating (Q1-Q3)</p>
                    </div>
                </div>
            </article>

            <article class="dashboard-card rounded-[18px] p-3">
                <div class="flex items-center gap-3">
                    <span class="metric-icon inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20a8 8 0 0 1 16 0"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 12v8"/>
                        </svg>
                    </span>
                    <div class="metric-content">
                        <p class="metric-value text-[15px] font-bold leading-tight text-slate-800 sm:text-[16px]" title="{{ $topStaff['name'] ?? 'N/A' }}">{{ $topStaff['name'] ?? 'N/A' }}</p>
                        <p class="mt-1 text-[11px] font-medium leading-tight text-slate-500">Top Staff</p>
                    </div>
                </div>
            </article>
        </div>
    @else
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <article class="dashboard-card rounded-[18px] p-3">
                <div class="flex items-center gap-3">
                    <span class="metric-icon inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v10H7l-3 3V5Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h8M8 13h5" />
                        </svg>
                    </span>
                    <div class="metric-content">
                        <p class="metric-value text-[22px] font-bold leading-none text-slate-800">{{ $totalFeedback }}</p>
                        <p class="mt-1 text-[11px] font-medium leading-tight text-slate-500">Total Response</p>
                    </div>
                </div>
            </article>

            <article class="dashboard-card rounded-[18px] p-3">
                <div class="flex items-center gap-3">
                    <span class="metric-icon inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="m12 2.75 2.83 5.73 6.32.92-4.57 4.45 1.08 6.3L12 17.17l-5.66 2.98 1.08-6.3L2.85 9.4l6.32-.92L12 2.75Z"/>
                        </svg>
                    </span>
                    <div class="metric-content">
                        <p class="metric-value text-[22px] font-bold leading-none text-slate-800">{{ !is_null($averageRating) ? number_format($averageRating, 1) : '0.0' }}</p>
                        <p class="mt-1 text-[11px] font-medium leading-tight text-slate-500">Ave Rating</p>
                    </div>
                </div>
            </article>

            <article class="dashboard-card rounded-[18px] p-3">
                <div class="flex items-center gap-3">
                    <span class="metric-icon inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 4.5 5h15l1.5 5.5"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h16v9a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-9Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 20v-5h6v5"/>
                        </svg>
                    </span>
                    <div class="metric-content">
                        <p class="metric-value text-[15px] font-bold leading-tight text-slate-800 sm:text-[16px]" title="{{ $topBranchLabel }}">{{ $topBranchLabel }}</p>
                        <p class="mt-1 text-[11px] font-medium leading-tight text-slate-500">Top Branch</p>
                    </div>
                </div>
            </article>

            <article class="dashboard-card rounded-[18px] p-3">
                <div class="flex items-center gap-3">
                    <span class="metric-icon inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 20a6 6 0 0 1 12 0"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.5 8.5 17 10l2.5-3"/>
                        </svg>
                    </span>
                    <div class="metric-content">
                        <p class="metric-value text-[15px] font-bold leading-tight text-slate-800 sm:text-[16px]" title="{{ $topManagerLabel }}">{{ $topManagerLabel }}</p>
                        <p class="mt-1 text-[11px] font-medium leading-tight text-slate-500">Top Manager</p>
                    </div>
                </div>
            </article>

            <article class="dashboard-card rounded-[18px] p-3">
                <div class="flex items-center gap-3">
                    <span class="metric-icon inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20a8 8 0 0 1 16 0"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 12v8"/>
                        </svg>
                    </span>
                    <div class="metric-content">
                        <p class="metric-value text-[15px] font-bold leading-tight text-slate-800 sm:text-[16px]" title="{{ $topStaff['name'] ?? 'N/A' }}">{{ $topStaff['name'] ?? 'N/A' }}</p>
                        <p class="mt-1 text-[11px] font-medium leading-tight text-slate-500">Top Staff</p>
                    </div>
                </div>
            </article>
        </div>
    @endif

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <section class="dashboard-chart-card rounded-[24px] p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Feedback By</p>
                    <h2 class="mt-1 text-[22px] font-semibold text-slate-800">Store Ratings</h2>
                </div>
                <span class="rounded-full bg-orange-50 px-3 py-1 text-[11px] font-semibold text-orange-700">
                    {{ $isStoreFiltered ? 'Selected Store' : 'Per Store' }}
                </span>
            </div>
            <div class="mt-4 h-[230px]">
                <canvas id="serviceMixChart"></canvas>
            </div>
        </section>

        <section class="dashboard-chart-card rounded-[24px] p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Feedback By</p>
                    <h2 class="mt-1 text-[22px] font-semibold text-slate-800">Rating Trends</h2>
                </div>
                <span class="rounded-full bg-orange-50 px-3 py-1 text-[11px] font-semibold text-orange-700">Last 7 Days</span>
            </div>
            <div class="mt-4 h-[230px]">
                <canvas id="ratingTrendChart"></canvas>
            </div>
        </section>
    </div>

    <section class="dashboard-table-card mt-4 rounded-[24px] p-4 sm:p-5">
        <div class="flex flex-wrap items-center gap-6 border-b border-slate-100 px-1 pb-4 text-[13px] font-medium">
            <span class="dashboard-tab is-active pb-2">Recent Feedback</span>
            <span class="dashboard-tab pb-2">Ratings Overview</span>
            <span class="dashboard-tab pb-2">{{ $isStoreFiltered ? 'Team Insights' : 'Branch Insights' }}</span>
            <span class="dashboard-tab pb-2">System Summary</span>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="dashboard-table min-w-full border-separate border-spacing-0">
                <thead>
                    <tr class="text-left">
                        <th class="px-3 py-3">Feedback ID</th>
                        <th class="px-3 py-3">Date</th>
                        <th class="px-3 py-3">Branch</th>
                        <th class="px-3 py-3">Staff</th>
                        <th class="px-3 py-3">Feedback Comment</th>
                        <th class="px-3 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="text-[13px] text-slate-700">
                    @forelse($recentFeedbacks as $item)
                        @php
                            $ratingValue = $item['average_rating'];
                            $statusClass = is_null($ratingValue)
                                ? 'mid'
                                : ($ratingValue >= 4 ? 'good' : ($ratingValue >= 3 ? 'mid' : 'low'));
                            $statusLabel = is_null($ratingValue)
                                ? 'Pending'
                                : ($ratingValue >= 4 ? 'Positive' : ($ratingValue >= 3 ? 'Neutral' : 'Needs Action'));
                        @endphp
                        <tr>
                            <td class="px-3 py-3 font-semibold text-slate-800">#{{ $item['feedback_id'] ?? $loop->iteration }}</td>
                            <td class="px-3 py-3 whitespace-nowrap">{{ $item['date'] ?? '-' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap">{{ $item['branch'] ?? '-' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap">{{ $item['staff_name'] ?? 'N/A' }}</td>
                            <td class="px-3 py-3 text-slate-500">{{ $item['feedback'] ?? 'No written feedback' }}</td>
                            <td class="px-3 py-3">
                                <span class="dashboard-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-sm text-slate-500">
                                No feedback records found for the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const dashboardFiltersForm = document.getElementById('dashboardFiltersForm');
    const dashboardStore = document.getElementById('dashboardStore');
    const dashboardStoreSearch = document.getElementById('dashboardStoreSearch');
    const dashboardStoreOptions = document.getElementById('dashboardStoreOptions');
    const dashboardDateTrigger = document.getElementById('dashboardDateTrigger');
    const dashboardDateTriggerLabel = document.getElementById('dashboardDateTriggerLabel');
    const dashboardDatePopover = document.getElementById('dashboardDatePopover');
    const dashboardDateFrom = document.getElementById('dashboardDateFrom');
    const dashboardDateTo = document.getElementById('dashboardDateTo');
    const dashboardDateFromShell = document.getElementById('dashboardDateFromShell');
    const dashboardDateToShell = document.getElementById('dashboardDateToShell');
    const dashboardDateFromNative = document.getElementById('dashboardDateFromNative');
    const dashboardDateToNative = document.getElementById('dashboardDateToNative');
    const dashboardMonthLabel = document.getElementById('dashboardMonthLabel');
    const dashboardCalendarGrid = document.getElementById('dashboardCalendarGrid');
    const dashboardMonthPrev = document.getElementById('dashboardMonthPrev');
    const dashboardMonthNext = document.getElementById('dashboardMonthNext');
    const dashboardDateApply = document.getElementById('dashboardDateApply');
    const dashboardPresetSelect = document.getElementById('dashboardPresetSelect');

    if (
        dashboardFiltersForm &&
        dashboardStore &&
        dashboardDateTrigger &&
        dashboardDatePopover &&
        dashboardDateFrom &&
        dashboardDateTo &&
        dashboardDateFromShell &&
        dashboardDateToShell &&
        dashboardDateFromNative &&
        dashboardDateToNative &&
        dashboardMonthLabel &&
        dashboardCalendarGrid &&
        dashboardMonthPrev &&
        dashboardMonthNext &&
        dashboardDateApply &&
        dashboardPresetSelect
    ) {
        const parseDateInput = (value) => {
            const [year, month, day] = value.split('-').map(Number);
            return new Date(year, month - 1, day);
        };

        const formatDateInput = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const formatDateLabel = (date) => {
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const year = date.getFullYear();
            return `${month}/${day}/${year}`;
        };

        const sameDate = (left, right) =>
            left.getFullYear() === right.getFullYear()
            && left.getMonth() === right.getMonth()
            && left.getDate() === right.getDate();

        const buildTriggerLabel = (fromDate, toDate) => `${formatDateLabel(fromDate)} - ${formatDateLabel(toDate)}`;
        const isSameCalendarDay = (left, right) => sameDate(left, right);

        const getPresetValue = (fromDate, toDate) => {
            const today = new Date();
            const normalizedToday = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            const weekStart = new Date(normalizedToday);
            weekStart.setDate(normalizedToday.getDate() - normalizedToday.getDay());
            const monthStart = new Date(normalizedToday.getFullYear(), normalizedToday.getMonth(), 1);

            if (isSameCalendarDay(fromDate, normalizedToday) && isSameCalendarDay(toDate, normalizedToday)) {
                return 'today';
            }

            if (isSameCalendarDay(fromDate, weekStart) && isSameCalendarDay(toDate, normalizedToday)) {
                return 'this_week';
            }

            if (isSameCalendarDay(fromDate, monthStart) && isSameCalendarDay(toDate, normalizedToday)) {
                return 'this_month';
            }

            return 'custom';
        };

        let activeField = 'start';
        let selectedStart = parseDateInput(dashboardDateFrom.value);
        let selectedEnd = parseDateInput(dashboardDateTo.value);
        let visibleMonth = new Date(selectedEnd.getFullYear(), selectedEnd.getMonth(), 1);

        const syncFieldState = () => {
            dashboardDateFromShell.classList.toggle('is-active', activeField === 'start');
            dashboardDateToShell.classList.toggle('is-active', activeField === 'end');
        };

        const syncNativeInputs = () => {
            dashboardDateFromNative.value = formatDateInput(selectedStart);
            dashboardDateToNative.value = formatDateInput(selectedEnd);
            dashboardDateFromNative.max = formatDateInput(selectedEnd);
            dashboardDateToNative.min = formatDateInput(selectedStart);
            dashboardDateTriggerLabel.textContent = buildTriggerLabel(selectedStart, selectedEnd);
            dashboardPresetSelect.value = getPresetValue(selectedStart, selectedEnd);
            syncFieldState();
        };

        const renderCalendar = () => {
            dashboardMonthLabel.textContent = visibleMonth.toLocaleDateString('en-US', {
                month: 'long',
                year: 'numeric',
            });

            dashboardCalendarGrid.innerHTML = '';

            const firstVisible = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth(), 1);
            const firstWeekday = firstVisible.getDay();
            const gridStart = new Date(firstVisible);
            gridStart.setDate(firstVisible.getDate() - firstWeekday);

            for (let index = 0; index < 42; index += 1) {
                const cellDate = new Date(gridStart);
                cellDate.setDate(gridStart.getDate() + index);

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'dashboard-calendar-cell mx-auto flex items-center justify-center';
                button.textContent = String(cellDate.getDate());

                const inVisibleMonth = cellDate.getMonth() === visibleMonth.getMonth();
                const inRange = cellDate >= selectedStart && cellDate <= selectedEnd;
                const isStart = sameDate(cellDate, selectedStart);
                const isEnd = sameDate(cellDate, selectedEnd);

                if (!inVisibleMonth) {
                    button.classList.add('is-muted');
                }

                if (inRange) {
                    button.classList.add('is-in-range');
                }

                if (isStart) {
                    button.classList.add('is-start');
                }

                if (isEnd) {
                    button.classList.add('is-end');
                }

                button.addEventListener('click', () => {
                    if (activeField === 'start') {
                        selectedStart = new Date(cellDate);
                        if (selectedStart > selectedEnd) {
                            selectedEnd = new Date(cellDate);
                        }
                        activeField = 'end';
                    } else {
                        selectedEnd = new Date(cellDate);
                        if (selectedEnd < selectedStart) {
                            selectedStart = new Date(cellDate);
                        }
                        activeField = 'start';
                    }

                    syncNativeInputs();
                    renderCalendar();
                });

                dashboardCalendarGrid.appendChild(button);
            }
        };

        const openPopover = () => {
            dashboardDatePopover.hidden = false;
            dashboardDateTrigger.setAttribute('aria-expanded', 'true');
            syncNativeInputs();
            renderCalendar();
        };

        const closePopover = () => {
            dashboardDatePopover.hidden = true;
            dashboardDateTrigger.setAttribute('aria-expanded', 'false');
        };

        const storeNameToId = new Map();
        const normalizedStoreNames = new Map();

        if (dashboardStoreSearch && dashboardStoreOptions) {
            Array.from(dashboardStoreOptions.options).forEach((option) => {
                const optionValue = option.value ?? '';
                const optionId = option.dataset.storeId ?? '';
                storeNameToId.set(optionValue, optionId);
                normalizedStoreNames.set(optionValue.trim().toLowerCase(), {
                    id: optionId,
                    label: optionValue,
                });
            });

            const applyStoreFilter = () => {
                const enteredValue = dashboardStoreSearch.value.trim();
                const normalizedValue = enteredValue.toLowerCase();

                if (enteredValue === '') {
                    dashboardStore.value = '';
                    dashboardStoreSearch.value = 'All Branches';
                    dashboardFiltersForm.submit();
                    return;
                }

                if (storeNameToId.has(enteredValue)) {
                    dashboardStore.value = storeNameToId.get(enteredValue) ?? '';
                    dashboardFiltersForm.submit();
                    return;
                }

                if (normalizedStoreNames.has(normalizedValue)) {
                    const matchedStore = normalizedStoreNames.get(normalizedValue);
                    dashboardStore.value = matchedStore.id ?? '';
                    dashboardStoreSearch.value = matchedStore.label ?? enteredValue;
                    dashboardFiltersForm.submit();
                    return;
                }

                dashboardStoreSearch.value = dashboardStore.value
                    ? (Array.from(storeNameToId.entries()).find(([, id]) => id === dashboardStore.value)?.[0] ?? enteredValue)
                    : 'All Branches';
            };

            dashboardStoreSearch.addEventListener('change', applyStoreFilter);
            dashboardStoreSearch.addEventListener('blur', applyStoreFilter);
            dashboardStoreSearch.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyStoreFilter();
                }
            });
        }

        dashboardDateTrigger.addEventListener('click', () => {
            if (dashboardDatePopover.hidden) {
                openPopover();
            } else {
                closePopover();
            }
        });

        dashboardMonthPrev.addEventListener('click', () => {
            visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() - 1, 1);
            renderCalendar();
        });

        dashboardMonthNext.addEventListener('click', () => {
            visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 1);
            renderCalendar();
        });

        dashboardDateFromShell.addEventListener('click', () => {
            activeField = 'start';
            visibleMonth = new Date(selectedStart.getFullYear(), selectedStart.getMonth(), 1);
            syncFieldState();
            renderCalendar();
        });

        dashboardDateToShell.addEventListener('click', () => {
            activeField = 'end';
            visibleMonth = new Date(selectedEnd.getFullYear(), selectedEnd.getMonth(), 1);
            syncFieldState();
            renderCalendar();
        });

        dashboardDateFromNative.addEventListener('change', () => {
            selectedStart = parseDateInput(dashboardDateFromNative.value);
            if (selectedStart > selectedEnd) {
                selectedEnd = new Date(selectedStart);
            }
            activeField = 'end';
            visibleMonth = new Date(selectedStart.getFullYear(), selectedStart.getMonth(), 1);
            syncNativeInputs();
            renderCalendar();
        });

        dashboardDateToNative.addEventListener('change', () => {
            selectedEnd = parseDateInput(dashboardDateToNative.value);
            if (selectedEnd < selectedStart) {
                selectedStart = new Date(selectedEnd);
            }
            activeField = 'start';
            visibleMonth = new Date(selectedEnd.getFullYear(), selectedEnd.getMonth(), 1);
            syncNativeInputs();
            renderCalendar();
        });

        dashboardPresetSelect.addEventListener('change', () => {
            const today = new Date();
            const normalizedToday = new Date(today.getFullYear(), today.getMonth(), today.getDate());

            if (dashboardPresetSelect.value === 'today') {
                selectedStart = new Date(normalizedToday);
                selectedEnd = new Date(normalizedToday);
            } else if (dashboardPresetSelect.value === 'this_week') {
                const weekStart = new Date(normalizedToday);
                weekStart.setDate(normalizedToday.getDate() - normalizedToday.getDay());
                selectedStart = new Date(weekStart.getFullYear(), weekStart.getMonth(), weekStart.getDate());
                selectedEnd = new Date(normalizedToday);
            } else if (dashboardPresetSelect.value === 'this_month') {
                selectedStart = new Date(normalizedToday.getFullYear(), normalizedToday.getMonth(), 1);
                selectedEnd = new Date(normalizedToday);
            } else {
                activeField = 'start';
            }

            if (dashboardPresetSelect.value !== 'custom') {
                activeField = 'start';
            }

            visibleMonth = new Date(selectedEnd.getFullYear(), selectedEnd.getMonth(), 1);
            syncNativeInputs();
            renderCalendar();
        });

        dashboardDateApply.addEventListener('click', () => {
            dashboardDateFrom.value = formatDateInput(selectedStart);
            dashboardDateTo.value = formatDateInput(selectedEnd);
            closePopover();
            dashboardFiltersForm.submit();
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('#dashboardDatePopover') && !event.target.closest('#dashboardDateTrigger')) {
                closePopover();
            }
        });

        syncNativeInputs();
        renderCalendar();
    }

    const ratingTrendCanvas = document.getElementById('ratingTrendChart');
    const serviceMixCanvas = document.getElementById('serviceMixChart');
    

    if (serviceMixCanvas) {
        new Chart(serviceMixCanvas, {
            type: 'doughnut',
            data: {
                labels: @json($storeRatingLabels),
                datasets: [{
                    data: @json($storeRatingData),
                    backgroundColor: ['#dc2626', '#ea580c', '#f97316', '#fb923c', '#fdba74', '#fed7aa'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#667085',
                            boxWidth: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 18,
                            font: { size: 11, weight: '600' }
                        }
                    }
                }
            }
        });
    }

    if (ratingTrendCanvas) {
        new Chart(ratingTrendCanvas, {
            type: 'bar',
            data: {
                labels: @json($ratingTrendLabels),
                datasets: [{
                    label: 'Average Rating',
                    data: @json($ratingTrendData),
                    backgroundColor: '#dc2626',
                    borderRadius: 999,
                    borderSkipped: false,
                    barThickness: 8
                }, {
                    label: 'Responses',
                    data: @json($responseTrendData),
                    type: 'line',
                    borderColor: '#fb923c',
                    backgroundColor: 'rgba(251, 146, 60, 0.2)',
                    fill: false,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 4,
                    pointBackgroundColor: '#fb923c',
                    pointBorderColor: '#ffffff',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#667085',
                            boxWidth: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 18,
                            font: { size: 11, weight: '600' }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#98a2b3',
                            font: { size: 11 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 5,
                        ticks: {
                            stepSize: 1,
                            color: '#98a2b3',
                            font: { size: 11 }
                        },
                        grid: {
                            color: 'rgba(226, 232, 240, 0.8)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: {
                            color: '#c0c6d4',
                            precision: 0,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }

</script>
@endsection
