@extends('layouts.admin')

@section('content')
    @php
        $isRestrictedToAssignedStore = auth()->user()?->isAdmin();
        $dateFrom = request('date_from', '');
        $dateTo = request('date_to', '');
        $dateSummary = 'Select date range';

        if ($dateFrom && $dateTo) {
            $dateSummary = \Carbon\Carbon::parse($dateFrom)->format('M d, Y') . ' - ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y');
        } elseif ($dateFrom) {
            $dateSummary = 'From ' . \Carbon\Carbon::parse($dateFrom)->format('M d, Y');
        } elseif ($dateTo) {
            $dateSummary = 'Until ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y');
        }
    @endphp

    <style>
        .analytics-toolbar-control,
        .analytics-toolbar-button,
        .analytics-date-trigger {
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            min-height: 44px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #f8fafc;
            padding: 0.72rem 0.9rem;
            color: #0f172a;
        }

        .analytics-toolbar-control.is-search {
            width: min(100%, 520px);
        }

        .analytics-toolbar-field,
        .analytics-toolbar-select-control {
            width: 100%;
            border: 0;
            background: transparent;
            font-size: 13px;
            color: #0f172a;
            outline: none;
        }

        .analytics-toolbar-grid {
            display: grid;
            gap: 0.7rem;
        }

        .analytics-toolbar-main,
        .analytics-toolbar-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            align-items: center;
        }

        .analytics-toolbar-main {
            justify-content: space-between;
        }

        .analytics-toolbar-main-left,
        .analytics-toolbar-main-right,
        .analytics-toolbar-meta-left,
        .analytics-toolbar-meta-right {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

        .analytics-toolbar-main-left {
            flex: 1 1 420px;
            min-width: 0;
        }

        .analytics-toolbar-main-right,
        .analytics-toolbar-meta-right {
            justify-content: flex-end;
        }

        .analytics-toolbar-meta {
            justify-content: flex-start;
            padding-top: 0;
        }

        .analytics-toolbar-main-right {
            gap: 0.65rem;
        }

        .analytics-store-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            min-height: 40px;
            border-radius: 13px;
            border: 1px solid #e2e8f0;
            background: #fff;
            padding: 0.4rem 0.72rem;
        }

        .analytics-store-icon {
            display: inline-flex;
            height: 34px;
            width: 34px;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #fff4ed;
            color: #c2410c;
        }

        .analytics-store-label {
            font-size: 0.66rem;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .analytics-store-name {
            margin-top: 0.1rem;
            font-size: 0.92rem;
            font-weight: 600;
            line-height: 1.15;
            color: #0f172a;
        }

        .analytics-toolbar-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            align-items: center;
        }

        .analytics-toolbar-search-shell {
            display: flex;
            flex: 1 1 420px;
            min-width: 0;
            overflow: visible;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #f8fafc;
            position: relative;
        }

        .analytics-toolbar-search-shell .analytics-toolbar-control.is-search {
            width: 100%;
            min-width: 0;
            border: 0;
            border-radius: 14px 0 0 14px;
            background: transparent;
            min-height: 42px;
            padding: 0.62rem 0.8rem;
            padding-right: 0.7rem;
        }

        .analytics-toolbar-search-date {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            min-height: 42px;
            border-left: 1px solid #e2e8f0;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            border-radius: 0 14px 14px 0;
        }

        .analytics-toolbar-block {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            min-height: 42px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #f8fafc;
            padding: 0.62rem 0.82rem;
            color: #0f172a;
            font-size: 13px;
        }

        .analytics-toolbar-block.is-select {
            min-width: 120px;
        }

        .analytics-toolbar-block.is-export {
            min-width: 112px;
            justify-content: center;
        }

        .analytics-toolbar-button.is-primary {
            min-height: 42px;
            border-radius: 14px;
            padding-inline: 1rem;
            font-size: 13px;
        }

        .analytics-date-filter {
            position: relative;
        }

        .analytics-date-filter[open] {
            z-index: 20;
        }

        .analytics-date-filter summary {
            list-style: none;
        }

        .analytics-date-filter summary::-webkit-details-marker {
            display: none;
        }

        .analytics-date-trigger {
            cursor: pointer;
            user-select: none;
            background: #fff;
            justify-content: center;
            min-width: 44px;
            width: 44px;
            padding: 0.72rem;
        }

        .analytics-date-trigger-label {
            display: flex;
            min-width: 0;
            flex-direction: column;
            line-height: 1.1;
        }

        .analytics-date-trigger-label span:first-child {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .analytics-date-trigger-label span:last-child {
            margin-top: 0.18rem;
            font-size: 0.92rem;
            font-weight: 600;
            color: #0f172a;
        }

        .analytics-date-trigger.is-compact .analytics-date-trigger-label {
            display: none;
        }

        .analytics-date-panel {
            position: absolute;
            right: 0;
            left: auto;
            top: calc(100% + 0.55rem);
            width: min(340px, 92vw);
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: #fff;
            padding: 0.95rem;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.12);
        }

        .analytics-date-panel-grid {
            display: grid;
            gap: 0.75rem;
        }

        .analytics-date-input-wrap {
            display: grid;
            gap: 0.4rem;
        }

        .analytics-date-input-wrap label {
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }

        .analytics-date-input {
            min-height: 42px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            padding: 0.7rem 0.85rem;
            font-size: 0.92rem;
            color: #0f172a;
            outline: none;
        }

        .analytics-date-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.55rem;
            padding-top: 0.25rem;
        }

        .analytics-date-action {
            min-height: 38px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #fff;
            padding: 0.55rem 0.85rem;
            font-size: 0.87rem;
            font-weight: 600;
            color: #334155;
        }

        .analytics-date-action.is-apply {
            border-color: #a52c2a;
            background: #a52c2a;
            color: #fff;
        }

        .analytics-toolbar-field::placeholder {
            color: #94a3b8;
        }

        .analytics-toolbar-button.is-primary {
            border-color: #a52c2a;
            background: #a52c2a;
            color: #ffffff;
        }

        .analytics-scoreboard {
            display: grid;
            gap: 0.9rem;
        }

        .analytics-total-card,
        .analytics-rank-card {
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            background: #fbfbfc;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
        }

        .analytics-total-card {
            display: flex;
            align-items: center;
            gap: 1.1rem;
            padding: 1.4rem 1.5rem;
        }

        .analytics-total-icon {
            display: inline-flex;
            height: 82px;
            width: 82px;
            align-items: center;
            justify-content: center;
            border-radius: 22px;
            background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
            color: #c2410c;
            box-shadow: inset 0 0 0 1px rgba(251, 146, 60, 0.18);
        }

        .analytics-total-value {
            font-size: 3.1rem;
            font-weight: 700;
            line-height: 1;
            color: #0f172a;
        }

        .analytics-total-label {
            margin-top: 0.45rem;
            font-size: 0.92rem;
            color: #334155;
        }

        .analytics-rank-grid {
            display: grid;
            gap: 0.9rem;
        }

        .analytics-rank-card {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.9rem;
            padding: 1.15rem 1.2rem;
        }

        .analytics-rank-icon {
            display: inline-flex;
            height: 56px;
            width: 56px;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
            color: #c2410c;
            box-shadow: inset 0 0 0 1px rgba(251, 146, 60, 0.18);
        }

        .analytics-rank-icon.is-down {
            background: linear-gradient(180deg, #fff1f2 0%, #ffe4e6 100%);
            color: #be123c;
            box-shadow: inset 0 0 0 1px rgba(244, 63, 94, 0.14);
        }

        .analytics-rank-name {
            font-size: 1.02rem;
            font-weight: 650;
            line-height: 1.1;
            color: #0f172a;
        }

        .analytics-rank-role {
            margin-top: 0.3rem;
            font-size: 0.84rem;
            color: #334155;
        }

        .analytics-rank-score {
            text-align: right;
        }

        .analytics-rank-score-label {
            font-size: 0.82rem;
            color: #334155;
        }

        .analytics-rank-score-value {
            margin-top: 0.2rem;
            font-size: 0.96rem;
            font-weight: 600;
            color: #0f172a;
        }

        .analytics-rank-card.is-empty {
            opacity: 0.86;
        }

        .analytics-rank-name.is-empty {
            font-size: 0.96rem;
            font-weight: 600;
            color: #334155;
        }

        .analytics-rank-role.is-empty {
            color: #64748b;
        }

        .analytics-rank-score-value.is-empty {
            color: #64748b;
        }

        @media (min-width: 960px) {
            .analytics-scoreboard {
                grid-template-columns: minmax(320px, 1.05fr) minmax(0, 1.95fr);
                align-items: stretch;
            }
        }

        @media (max-width: 959px) {
            .analytics-toolbar-main-right,
            .analytics-toolbar-meta-right {
                justify-content: flex-start;
            }

            .analytics-date-panel {
                left: 0;
                right: auto;
            }
        }

        @media (min-width: 1280px) {
            .analytics-rank-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <div class="space-y-5">
        <div class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="analytics-toolbar-grid">
                <div class="analytics-toolbar-meta">
                    <div class="analytics-store-badge">
                        <span class="analytics-store-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5M6 9V5.625c0-.621.504-1.125 1.125-1.125h9.75c.621 0 1.125.504 1.125 1.125V9M6 9h12M6 9v9.375c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9M9.75 12.75h4.5" />
                            </svg>
                        </span>
                        <div>
                            <div class="analytics-store-label">Current Store</div>
                            <div class="analytics-store-name">{{ $selectedStoreLabel }}</div>
                        </div>
                    </div>
                </div>

                <div class="analytics-toolbar-row">
                    <div class="analytics-toolbar-search-shell">
                        <label class="analytics-toolbar-control is-search">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                            </svg>
                            <input type="search" id="analyticsSearchInput" class="analytics-toolbar-field" placeholder="search">
                        </label>

                        <details class="analytics-date-filter" id="analyticsDateFilter">
                            <summary class="analytics-toolbar-search-date" title="{{ $dateSummary }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3.75V6m7.5-2.25V6M3.75 8.25h16.5M5.25 5.25h13.5A1.5 1.5 0 0 1 20.25 6.75v11.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z" />
                                </svg>
                            </summary>

                            <div class="analytics-date-panel">
                                <div class="analytics-date-panel-grid">
                                    <div class="analytics-date-input-wrap">
                                        <label for="analyticsDateFromInput">Date From</label>
                                        <input type="date" id="analyticsDateFromInput" class="analytics-date-input" value="{{ request('date_from', '') }}" aria-label="Date from">
                                    </div>

                                    <div class="analytics-date-input-wrap">
                                        <label for="analyticsDateToInput">Date To</label>
                                        <input type="date" id="analyticsDateToInput" class="analytics-date-input" value="{{ request('date_to', '') }}" aria-label="Date to">
                                    </div>

                                    <div class="analytics-date-actions">
                                        <button type="button" id="analyticsDateClearButton" class="analytics-date-action">Clear</button>
                                        <button type="button" id="analyticsDateApplyButton" class="analytics-date-action is-apply">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>

                    <label class="analytics-toolbar-block is-select">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5M6 9V5.625c0-.621.504-1.125 1.125-1.125h9.75c.621 0 1.125.504 1.125 1.125V9M6 9h12M6 9v9.375c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9M9.75 12.75h4.5" />
                        </svg>
                        <select id="analyticsStoreSelect" class="analytics-toolbar-select-control" aria-label="Filter by store">
                            @unless($isRestrictedToAssignedStore)
                                <option value="{{ route('stores.staff.analytics', [$store, 'store_filter' => 'all']) }}" {{ $selectedStoreId === 'all' ? 'selected' : '' }}>Store</option>
                            @endunless
                            @foreach($stores as $branchStore)
                                <option
                                    value="{{ route('stores.staff.analytics', [$store, 'store_filter' => $branchStore->store_id]) }}"
                                    {{ (string) $selectedStoreId === (string) $branchStore->store_id ? 'selected' : '' }}
                                >
                                    {{ $branchStore->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <button type="button" id="analyticsExportButton" class="analytics-toolbar-block is-export text-sm font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v10.5m0 0 3.75-3.75M12 14.25 8.25 10.5M4.5 15.75v1.125A2.625 2.625 0 0 0 7.125 19.5h9.75A2.625 2.625 0 0 0 19.5 16.875V15.75" />
                        </svg>
                        Export
                    </button>

                    <a href="{{ route('stores.staff.index', $store) }}" class="analytics-toolbar-button is-primary justify-center text-sm font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h15v10.5h-15V6.75Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75v10.5M15.75 6.75v10.5" />
                        </svg>
                        Manage Staff
                    </a>
                </div>
            </div>
        </div>

        <div class="analytics-scoreboard">
            <div class="analytics-total-card">
                <span class="analytics-total-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 2a6 6 0 0 0-4.472 10.001c.29.326.522.73.522 1.249v.478c0 .266.105.52.293.707A.996.996 0 0 0 5.05 15h5.9a.996.996 0 0 0 .707-.293.996.996 0 0 0 .293-.707v-.478c0-.52.231-.923.522-1.249A6 6 0 0 0 8 2Zm-2.5 4.75a.75.75 0 0 1 .75-.75h3.5a.75.75 0 0 1 0 1.5h-3.5a.75.75 0 0 1-.75-.75Zm0 2.5a.75.75 0 0 1 .75-.75h5a.75.75 0 0 1 0 1.5h-5a.75.75 0 0 1-.75-.75Z"/>
                    </svg>
                </span>
                <div>
                    <div class="analytics-total-value">{{ $totalResponses }}</div>
                    <div class="analytics-total-label">Total Feedback</div>
                </div>
            </div>

            <div class="analytics-rank-grid">
                <div class="analytics-rank-card {{ $topManager ? '' : 'is-empty' }}">
                    <span class="analytics-rank-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75 14.394 8.6l5.356.779-3.875 3.778.915 5.343L12 16.03 7.21 18.5l.915-5.343L4.25 9.379 9.606 8.6 12 3.75Z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <div class="analytics-rank-name {{ $topManager ? '' : 'is-empty' }}">{{ $topManager['name'] ?? 'No manager data yet' }}</div>
                        <div class="analytics-rank-role {{ $topManager ? '' : 'is-empty' }}">Top Manager</div>
                    </div>
                    <div class="analytics-rank-score">
                        <div class="analytics-rank-score-label">Rating</div>
                        <div class="analytics-rank-score-value {{ $topManager ? '' : 'is-empty' }}">{{ isset($topManager['average_rating']) ? number_format($topManager['average_rating'], 1) : 'No score' }}</div>
                    </div>
                </div>

                <div class="analytics-rank-card {{ $leastManager ? '' : 'is-empty' }}">
                    <span class="analytics-rank-icon is-down">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5h13.5M5.25 12h9.75M5.25 16.5h6" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <div class="analytics-rank-name {{ $leastManager ? '' : 'is-empty' }}">{{ $leastManager['name'] ?? 'No manager data yet' }}</div>
                        <div class="analytics-rank-role {{ $leastManager ? '' : 'is-empty' }}">Least Manager</div>
                    </div>
                    <div class="analytics-rank-score">
                        <div class="analytics-rank-score-label">Rating</div>
                        <div class="analytics-rank-score-value {{ $leastManager ? '' : 'is-empty' }}">{{ isset($leastManager['average_rating']) ? number_format($leastManager['average_rating'], 1) : 'No score' }}</div>
                    </div>
                </div>

                <div class="analytics-rank-card {{ $topStaff ? '' : 'is-empty' }}">
                    <span class="analytics-rank-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75 14.394 8.6l5.356.779-3.875 3.778.915 5.343L12 16.03 7.21 18.5l.915-5.343L4.25 9.379 9.606 8.6 12 3.75Z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <div class="analytics-rank-name {{ $topStaff ? '' : 'is-empty' }}">{{ $topStaff['name'] ?? 'No staff data yet' }}</div>
                        <div class="analytics-rank-role {{ $topStaff ? '' : 'is-empty' }}">Top Staff</div>
                    </div>
                    <div class="analytics-rank-score">
                        <div class="analytics-rank-score-label">Rating</div>
                        <div class="analytics-rank-score-value {{ $topStaff ? '' : 'is-empty' }}">{{ isset($topStaff['average_rating']) ? number_format($topStaff['average_rating'], 1) : 'No score' }}</div>
                    </div>
                </div>

                <div class="analytics-rank-card {{ $leastStaff ? '' : 'is-empty' }}">
                    <span class="analytics-rank-icon is-down">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5h13.5M5.25 12h9.75M5.25 16.5h6" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <div class="analytics-rank-name {{ $leastStaff ? '' : 'is-empty' }}">{{ $leastStaff['name'] ?? 'No staff data yet' }}</div>
                        <div class="analytics-rank-role {{ $leastStaff ? '' : 'is-empty' }}">Least Staff</div>
                    </div>
                    <div class="analytics-rank-score">
                        <div class="analytics-rank-score-label">Rating</div>
                        <div class="analytics-rank-score-value {{ $leastStaff ? '' : 'is-empty' }}">{{ isset($leastStaff['average_rating']) ? number_format($leastStaff['average_rating'], 1) : 'No score' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Staff Analytics</h3>
                    </div>
                    <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{{ $staffCount }} names</span>
                </div>

                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Name</th>
                                <th class="px-4 py-3 text-right font-semibold">Mentions</th>
                                <th class="px-4 py-3 text-right font-semibold">Comments</th>
                                <th class="px-4 py-3 text-right font-semibold">Avg Rating</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($staffAnalytics as $item)
                                <tr class="analytics-record-row" data-analytics-search="{{ strtolower($item['name']) }}">
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $item['name'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-700">{{ $item['mention_count'] }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600">{{ $item['comment_count'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ !is_null($item['average_rating']) ? number_format($item['average_rating'], 1) : '-' }}</td>
                                </tr>
                            @empty
                                <tr class="analytics-empty-row">
                                    <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">No staff analytics yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Manager Analytics</h3>
                    </div>
                    <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ $managerCount }} names</span>
                </div>

                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Name</th>
                                <th class="px-4 py-3 text-right font-semibold">Mentions</th>
                                <th class="px-4 py-3 text-right font-semibold">Comments</th>
                                <th class="px-4 py-3 text-right font-semibold">Avg Rating</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($managerAnalytics as $item)
                                <tr class="analytics-record-row" data-analytics-search="{{ strtolower($item['name']) }}">
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $item['name'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-700">{{ $item['mention_count'] }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600">{{ $item['comment_count'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ !is_null($item['average_rating']) ? number_format($item['average_rating'], 1) : '-' }}</td>
                                </tr>
                            @empty
                                <tr class="analytics-empty-row">
                                    <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">No manager analytics yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <script>
        const analyticsSearchInput = document.getElementById('analyticsSearchInput');
        const analyticsStoreSelect = document.getElementById('analyticsStoreSelect');
        const analyticsDateFilter = document.getElementById('analyticsDateFilter');
        const analyticsDateTrigger = analyticsDateFilter?.querySelector('summary') || null;
        const analyticsDateFromInput = document.getElementById('analyticsDateFromInput');
        const analyticsDateToInput = document.getElementById('analyticsDateToInput');
        const analyticsDateApplyButton = document.getElementById('analyticsDateApplyButton');
        const analyticsDateClearButton = document.getElementById('analyticsDateClearButton');
        const analyticsExportButton = document.getElementById('analyticsExportButton');

        const formatAnalyticsDate = (value) => {
            if (!value) {
                return '';
            }

            const parsed = new Date(`${value}T00:00:00`);

            if (Number.isNaN(parsed.getTime())) {
                return '';
            }

            return parsed.toLocaleDateString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
            });
        };

        const refreshDateSummary = () => {
            const fromText = formatAnalyticsDate(analyticsDateFromInput?.value || '');
            const toText = formatAnalyticsDate(analyticsDateToInput?.value || '');
            let label = 'Select date range';

            if (fromText && toText) {
                label = `${fromText} - ${toText}`;
            } else if (fromText) {
                label = `From ${fromText}`;
            } else if (toText) {
                label = `Until ${toText}`;
            }

            if (analyticsDateTrigger) {
                analyticsDateTrigger.setAttribute('title', label);
            }
        };

        const buildAnalyticsUrl = (baseUrl) => {
            const url = new URL(baseUrl, window.location.origin);
            const selectedDateFrom = analyticsDateFromInput?.value || '';
            const selectedDateTo = analyticsDateToInput?.value || '';

            if (selectedDateFrom !== '') {
                url.searchParams.set('date_from', selectedDateFrom);
            }

            if (selectedDateTo !== '') {
                url.searchParams.set('date_to', selectedDateTo);
            }

            return url.toString();
        };

        const filterAnalyticsRows = () => {
            const keyword = (analyticsSearchInput?.value || '').trim().toLowerCase();

            document.querySelectorAll('.analytics-record-row').forEach((row) => {
                const searchableText = row.dataset.analyticsSearch || '';
                row.classList.toggle('hidden', keyword !== '' && !searchableText.includes(keyword));
            });
        };

        analyticsSearchInput?.addEventListener('input', filterAnalyticsRows);
        analyticsStoreSelect?.addEventListener('change', (event) => {
            if (event.target.value) {
                window.location.href = buildAnalyticsUrl(event.target.value);
            }
        });
        const refreshAnalyticsByDate = () => {
            const baseUrl = analyticsStoreSelect?.value || window.location.href;
            window.location.href = buildAnalyticsUrl(baseUrl);
        };

        analyticsDateFromInput?.addEventListener('input', refreshDateSummary);
        analyticsDateToInput?.addEventListener('input', refreshDateSummary);
        analyticsDateApplyButton?.addEventListener('click', () => {
            refreshAnalyticsByDate();
        });
        analyticsDateClearButton?.addEventListener('click', () => {
            if (analyticsDateFromInput) {
                analyticsDateFromInput.value = '';
            }

            if (analyticsDateToInput) {
                analyticsDateToInput.value = '';
            }

            refreshDateSummary();
            refreshAnalyticsByDate();
        });
        analyticsExportButton?.addEventListener('click', () => {
            const params = new URLSearchParams({
                store_filter: @json($selectedStoreId),
                search: analyticsSearchInput?.value || '',
            });

            if ((analyticsDateFromInput?.value || '') !== '') {
                params.set('date_from', analyticsDateFromInput.value);
            }

            if ((analyticsDateToInput?.value || '') !== '') {
                params.set('date_to', analyticsDateToInput.value);
            }

            window.location.href = `{{ route('stores.staff.analytics.export.pdf', $store) }}?${params.toString()}`;
        });

        document.addEventListener('click', (event) => {
            if (!analyticsDateFilter?.open) {
                return;
            }

            if (!analyticsDateFilter.contains(event.target)) {
                analyticsDateFilter.open = false;
            }
        });

        refreshDateSummary();
    </script>
@endsection
