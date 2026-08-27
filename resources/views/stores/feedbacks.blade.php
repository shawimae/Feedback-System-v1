@extends('layouts.admin')

@section('content')
    @php
        $isRestrictedToAssignedStore = auth()->user()?->isAdmin();
        $total = $feedbacks->count();
        $resolvedFeedbacks = $feedbacks->filter(fn ($feedback) => $feedback->is_resolved)->values();
        $unresolvedFeedbacks = $feedbacks->filter(fn ($feedback) => !$feedback->is_resolved)->values();
        $avg = $feedbacks->avg('overall_rating');
        $todayFeedbacks = $feedbacks->filter(fn ($feedback) => optional($feedback->created_at)->isToday())->count();
        $lowRatingCount = $feedbacks->filter(fn ($feedback) => (int) $feedback->overall_rating <= 3)->count();
        $repliedToday = $feedbacks->filter(fn ($feedback) => optional($feedback->admin_replied_at)->isToday())->count();
        $dateFrom = request('date_from')
            ? \Carbon\Carbon::parse(request('date_from'))->startOfDay()
            : match (request('timeframe', 'monthly')) {
                'daily' => now()->startOfDay(),
                'weekly' => now()->subDays(6)->startOfDay(),
                default => now()->subDays(29)->startOfDay(),
            };
        $dateTo = request('date_to')
            ? \Carbon\Carbon::parse(request('date_to'))->endOfDay()
            : now()->endOfDay();
        $dateRangeLabel = $dateFrom->isSameDay($dateTo)
            ? $dateFrom->format('M d, Y')
            : $dateFrom->format('M d, Y') . ' - ' . $dateTo->format('M d, Y');

        $activeTab = request('tab', 'unresolved');
        $tabMap = [
            'unresolved' => $unresolvedFeedbacks,
            'resolved' => $resolvedFeedbacks,
            'all' => $feedbacks,
        ];

        $visibleFeedbacks = $tabMap[$activeTab] ?? $feedbacks;
        $selectedFeedbackId = request('feedback');
        $selectedFeedback = $visibleFeedbacks->firstWhere('feedback_id', $selectedFeedbackId) ?? $visibleFeedbacks->first();

        $buildQuery = function (array $overrides = []) {
            return array_merge(
                request()->only(['store_id', 'timeframe', 'date_from', 'date_to', 'search', 'tab', 'feedback']),
                $overrides
            );
        };

        $tabClasses = function ($tab) use ($activeTab) {
            return $activeTab === $tab
                ? 'border-sky-200 bg-sky-50 text-sky-700'
                : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700';
        };

        $formatStars = function ($rating, $size = 'h-3.5 w-3.5') {
            $rounded = (int) round($rating ?? 0);
            $html = '<div class="flex items-center gap-0.5">';

            for ($i = 1; $i <= 5; $i++) {
                $color = $i <= $rounded ? 'text-amber-400' : 'text-slate-300';
                $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="' . $size . ' ' . $color . '" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 0 0 .95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.37 2.449a1 1 0 0 0-.364 1.118l1.287 3.957c.3.921-.755 1.688-1.538 1.118l-3.37-2.449a1 1 0 0 0-1.176 0l-3.37 2.449c-.783.57-1.838-.197-1.538-1.118l1.287-3.957a1 1 0 0 0-.364-1.118L2.073 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 0 0 .95-.69l1.276-3.957Z"/></svg>';
            }

            return $html . '</div>';
        };

        $feedbackPriority = function ($feedback) {
            if ($feedback->is_resolved) {
                return ['Resolved', 'bg-slate-100 text-slate-600 border-slate-200'];
            }

            if ((int) $feedback->overall_rating <= 2) {
                return ['Urgent', 'bg-rose-50 text-rose-600 border-rose-100'];
            }

            if ((int) $feedback->overall_rating === 3 || filled($feedback->overall_comment)) {
                return ['Review', 'bg-amber-50 text-amber-600 border-amber-100'];
            }

            return ['Stable', 'bg-emerald-50 text-emerald-600 border-emerald-100'];
        };

        $initials = function ($name) {
            $name = trim((string) $name);

            if ($name === '') {
                return 'AN';
            }

            $parts = preg_split('/\s+/', $name);
            $first = strtoupper(substr($parts[0] ?? 'A', 0, 1));
            $second = strtoupper(substr($parts[1] ?? ($parts[0] ?? 'N'), 0, 1));

            return $first . $second;
        };
    @endphp

    <style>
        .feedback-shell {
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 28px;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.95), transparent 34%),
                linear-gradient(180deg, rgba(250, 252, 255, 0.96) 0%, rgba(243, 247, 252, 0.92) 100%);
            box-shadow:
                0 24px 60px rgba(15, 23, 42, 0.10),
                inset 0 1px 0 rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(18px);
            overflow: hidden;
        }

        .feedback-aside {
            background: linear-gradient(180deg, rgba(248, 251, 255, 0.88) 0%, rgba(241, 246, 252, 0.82) 100%);
            border-right: 1px solid rgba(214, 224, 236, 0.9);
        }

        .feedback-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .feedback-scrollbar::-webkit-scrollbar-thumb {
            background: #c9d8ea;
            border-radius: 9999px;
        }

        .feedback-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .resolve-switch {
            position: relative;
            display: inline-flex;
            align-items: center;
            width: 42px;
            height: 24px;
            border-radius: 999px;
            background: linear-gradient(180deg, #e5ebf3 0%, #d7dee8 100%);
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.10);
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
        }

        .resolve-switch::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: linear-gradient(180deg, #ffffff 0%, #f6f8fb 100%);
            box-shadow:
                0 1px 3px rgba(15, 23, 42, 0.18),
                inset 0 1px 0 rgba(255, 255, 255, 0.95);
            transition: transform 0.2s ease;
        }

        input:checked + .resolve-switch {
            background: linear-gradient(180deg, #34c759 0%, #22c55e 100%);
            box-shadow: inset 0 1px 2px rgba(22, 101, 52, 0.18);
        }

        input:checked + .resolve-switch::after {
            transform: translateX(18px);
        }

        .inbox-tab {
            position: relative;
            border-radius: 0;
            border: 0;
            background: transparent;
            padding: 12px 10px 14px;
            letter-spacing: -0.01em;
        }

        .inbox-tab.active::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -1px;
            height: 2px;
            background: linear-gradient(90deg, #60a5fa 0%, #38bdf8 100%);
            border-radius: 999px;
        }

        .filter-control {
            height: 40px;
            border-radius: 13px;
            border: 1px solid rgba(207, 217, 229, 0.95);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.94) 0%, rgba(248, 250, 252, 0.94) 100%);
            color: #334155;
            font-size: 12px;
            outline: none;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.95),
                0 1px 2px rgba(15, 23, 42, 0.03);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .filter-control:focus {
            border-color: rgba(125, 166, 216, 0.95);
            box-shadow:
                0 0 0 4px rgba(147, 197, 253, 0.14),
                0 4px 18px rgba(148, 163, 184, 0.10);
        }

        .feedback-card {
            border: 1px solid rgba(215, 226, 238, 0.95);
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.92) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.95),
                0 8px 24px rgba(15, 23, 42, 0.04);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
        }

        .feedback-card.is-selected {
            border-color: rgba(169, 205, 242, 0.95);
            background: linear-gradient(180deg, rgba(232, 243, 255, 0.98) 0%, rgba(221, 238, 255, 0.94) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.92),
                0 14px 30px rgba(96, 165, 250, 0.12);
        }

        .feedback-card:hover {
            transform: translateY(-1px);
            border-color: rgba(191, 215, 238, 0.95);
            box-shadow: 0 14px 28px rgba(148, 163, 184, 0.14);
        }

        .ghost-button {
            border-radius: 12px;
            border: 1px solid rgba(207, 217, 229, 0.95);
            background: linear-gradient(180deg, #ffffff 0%, #f6f8fb 100%);
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.95),
                0 1px 2px rgba(15, 23, 42, 0.04);
            transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }

        .ghost-button:hover {
            transform: translateY(-1px);
            background: linear-gradient(180deg, #ffffff 0%, #f2f5f9 100%);
            border-color: #c5d4e5;
        }

        .primary-button {
            border-radius: 12px;
            background: linear-gradient(180deg, #2f5d92 0%, #244b79 100%);
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.18),
                0 10px 24px rgba(36, 75, 121, 0.18);
            transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .primary-button:hover {
            transform: translateY(-1px);
            background: linear-gradient(180deg, #3568a2 0%, #295584 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.18),
                0 14px 28px rgba(36, 75, 121, 0.22);
        }

        .top-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 18px;
            border-bottom: 1px solid rgba(214, 224, 236, 0.9);
            background: linear-gradient(180deg, #fdfefe 0%, #f6f9fc 100%);
        }

        .toolbar-filters {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
            margin-left: auto;
        }

        .filter-menu {
            position: relative;
            flex: 0 0 auto;
        }

        .filter-menu summary {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-height: 42px;
            min-width: 156px;
            border-radius: 22px;
            border: 1px solid rgba(191, 204, 222, 0.95);
            background: #ffffff;
            padding: 0 18px;
            font-size: 12px;
            font-weight: 600;
            color: #27456b;
            box-shadow:
                0 1px 2px rgba(15, 23, 42, 0.04);
            cursor: pointer;
            list-style: none;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .filter-menu summary::-webkit-details-marker {
            display: none;
        }

        .filter-menu summary:hover {
            transform: translateY(-1px);
            border-color: rgba(160, 181, 208, 0.95);
        }

        .filter-menu[open] summary {
            border-color: rgba(125, 166, 216, 0.95);
            box-shadow:
                0 0 0 4px rgba(147, 197, 253, 0.12);
        }

        .filter-menu-list {
            position: absolute;
            right: 0;
            z-index: 20;
            margin-top: 10px;
            min-width: 190px;
            overflow: hidden;
            border-radius: 18px;
            border: 1px solid rgba(207, 217, 229, 0.95);
            background: #ffffff;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
        }

        .filter-menu-item {
            display: block;
            width: 100%;
            border: 0;
            background: transparent;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            text-align: left;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .filter-menu-item:hover {
            background: #f8fafc;
            color: #1e293b;
        }

        .filter-menu-item.is-active {
            background: #eaf4ff;
            color: #1d4d7b;
            font-weight: 700;
        }

        .filter-menu-icon {
            color: #27456b;
        }

        .filter-menu-label {
            flex: 1;
            text-align: left;
            white-space: nowrap;
        }

        .filter-menu-chevron {
            color: #64748b;
        }

        .message-search {
            height: 46px;
            border-radius: 16px;
            border: 1px solid rgba(207, 217, 229, 0.95);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.96) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.95),
                0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .feedback-date-trigger {
            min-width: 240px;
            min-height: 42px;
            border-radius: 22px;
            border: 1px solid rgba(191, 204, 222, 0.95);
            background: #ffffff;
            padding: 0 16px;
            font-size: 12px;
            font-weight: 600;
            color: #27456b;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .feedback-date-popover {
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            z-index: 25;
            width: min(540px, calc(100vw - 24px));
            overflow: hidden;
            border-radius: 18px;
            border: 1px solid #dde3ef;
            background: #ffffff;
            box-shadow: 0 20px 36px rgba(15, 23, 42, 0.16);
        }

        .feedback-date-popover[hidden] {
            display: none;
        }

        .feedback-date-sidebar {
            background: linear-gradient(180deg, #fbfbff 0%, #f8f9ff 100%);
            border-right: 1px solid #e4e7ec;
        }

        .feedback-date-input-shell {
            border: 2px solid #ea580c;
            border-radius: 10px;
            background: #ffffff;
        }

        .feedback-date-input-shell.is-secondary {
            border-color: #e4e7ec;
        }

        .feedback-date-input-shell.is-active {
            border-color: #ea580c;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
        }

        .feedback-apply-button {
            background: linear-gradient(180deg, #f97316 0%, #ea580c 100%);
            box-shadow: 0 10px 18px rgba(234, 88, 12, 0.2);
        }

        .feedback-calendar-cell {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            font-size: 12px;
            color: #5b6472;
            transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
        }

        .feedback-calendar-cell:hover {
            background: #fff1e8;
            color: #ea580c;
            transform: translateY(-1px);
        }

        .feedback-calendar-cell.is-muted {
            color: #c0c6cf;
        }

        .feedback-calendar-cell.is-in-range {
            background: #fff4ed;
            color: #9a3412;
            border-radius: 0;
        }

        .feedback-calendar-cell.is-start,
        .feedback-calendar-cell.is-end {
            background: #ea580c;
            color: #ffffff;
            font-weight: 700;
        }

        .feedback-calendar-cell.is-start {
            border-radius: 999px 0 0 999px;
        }

        .feedback-calendar-cell.is-end {
            border-radius: 0 999px 999px 0;
        }

        .feedback-calendar-cell.is-start.is-end {
            border-radius: 999px;
        }

        @media (max-width: 1024px) {
            .top-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .toolbar-filters {
                width: 100%;
                justify-content: flex-start;
                margin-left: 0;
            }
        }

        @media (max-width: 767px) {
            .feedback-date-trigger {
                min-width: 100%;
            }

            .feedback-date-popover {
                left: 0;
                right: auto;
                width: min(100%, calc(100vw - 24px));
            }
        }
    </style>

    <!-- <div class="space-y-3">
        <div class="grid gap-2 lg:grid-cols-[1.3fr,1fr]">
            <div class="rounded-[24px] border border-slate-200 bg-white px-4 py-4 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-[0.32em] text-slate-400">Feedback Management</p>
                <h2 class="mt-1 text-[23px] font-semibold tracking-tight text-slate-900">Guest Response Inbox</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">A compact workspace for restaurant owners to review service issues, compliments, and guest follow-ups.</p>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="rounded-[22px] border border-slate-200 bg-white px-3 py-3 shadow-sm">
                    <p class="text-[10px] uppercase tracking-[0.24em] text-slate-400">Open Queue</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $unresolvedFeedbacks->count() }}</p>
                    <p class="mt-1 text-[11px] text-slate-500">Waiting for reply</p>
                </div>
                <div class="rounded-[22px] border border-slate-200 bg-white px-3 py-3 shadow-sm">
                    <p class="text-[10px] uppercase tracking-[0.24em] text-slate-400">Average Score</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $avg ? number_format($avg, 1) : '-' }}</p>
                    <p class="mt-1 text-[11px] text-slate-500">Filtered feedback only</p>
                </div>
            </div>
        </div>

        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[20px] border border-slate-200 bg-white px-3 py-3 shadow-sm">
                <p class="text-[10px] uppercase tracking-[0.24em] text-slate-400">Today</p>
                <p class="mt-2 text-xl font-semibold text-slate-900">{{ $todayFeedbacks }}</p>
                <p class="mt-1 text-[11px] text-slate-500">New submissions</p>
            </div>
            <div class="rounded-[20px] border border-slate-200 bg-white px-3 py-3 shadow-sm">
                <p class="text-[10px] uppercase tracking-[0.24em] text-slate-400">Low Ratings</p>
                <p class="mt-2 text-xl font-semibold text-slate-900">{{ $lowRatingCount }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Need recovery action</p>
            </div>
            <div class="rounded-[20px] border border-slate-200 bg-white px-3 py-3 shadow-sm">
                <p class="text-[10px] uppercase tracking-[0.24em] text-slate-400">Resolved</p>
                <p class="mt-2 text-xl font-semibold text-slate-900">{{ $resolvedFeedbacks->count() }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Already answered</p>
            </div>
            <div class="rounded-[20px] border border-slate-200 bg-white px-3 py-3 shadow-sm">
                <p class="text-[10px] uppercase tracking-[0.24em] text-slate-400">Replies Today</p>
                <p class="mt-2 text-xl font-semibold text-slate-900">{{ $repliedToday }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Owner activity</p>
            </div>
        </div> -->

        <div class="feedback-shell">
            <div class="top-toolbar">
                <div class="grid w-full max-w-[460px] grid-cols-3 border-b border-slate-200 text-[12px] font-medium">
                    <a href="{{ route('feedbacks.index', $buildQuery(['tab' => 'unresolved', 'feedback' => null])) }}" class="inbox-tab text-center transition {{ $activeTab === 'unresolved' ? 'active text-sky-600' : 'text-slate-500 hover:text-slate-700' }}">
                        Unresolved
                        <span class="ml-1 rounded-full bg-orange-100/90 px-1.5 py-0.5 text-[10px] text-orange-600">{{ $unresolvedFeedbacks->count() }}</span>
                    </a>
                    <a href="{{ route('feedbacks.index', $buildQuery(['tab' => 'resolved', 'feedback' => null])) }}" class="inbox-tab text-center transition {{ $activeTab === 'resolved' ? 'active text-sky-600' : 'text-slate-500 hover:text-slate-700' }}">
                        Resolved
                        <span class="ml-1 text-[10px] text-slate-400">{{ $resolvedFeedbacks->count() }}</span>
                    </a>
                    <a href="{{ route('feedbacks.index', $buildQuery(['tab' => 'all', 'feedback' => null])) }}" class="inbox-tab text-center transition {{ $activeTab === 'all' ? 'active text-sky-600' : 'text-slate-500 hover:text-slate-700' }}">
                        All
                        <span class="ml-1 text-[10px] text-sky-400">{{ $total }}</span>
                    </a>
                </div>

                <div class="toolbar-filters">
                    <div class="relative flex flex-col gap-1">
                        <input id="feedbackDateFrom" type="hidden" value="{{ $dateFrom->toDateString() }}">
                        <input id="feedbackDateTo" type="hidden" value="{{ $dateTo->toDateString() }}">

                        <button
                            type="button"
                            id="feedbackDateTrigger"
                            class="feedback-date-trigger inline-flex items-center gap-2 text-left"
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
                            <span id="feedbackDateTriggerLabel" class="flex-1 truncate">{{ $dateRangeLabel }}</span>
                            <span class="text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="m7 10 5 5 5-5z"/>
                                </svg>
                            </span>
                        </button>

                        <div id="feedbackDatePopover" class="feedback-date-popover" hidden>
                            <div class="grid md:grid-cols-[215px_minmax(0,1fr)]">
                                <div class="feedback-date-sidebar p-4">
                                    <p class="text-[12px] text-slate-400">Select Date Range:</p>

                                    <select id="feedbackPresetSelect" class="mt-3 w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-[12px] text-slate-700 outline-none">
                                        <option value="custom">Custom</option>
                                        <option value="today">Today</option>
                                        <option value="this_week">This Week</option>
                                        <option value="this_month">This Month</option>
                                    </select>

                                    <label id="feedbackDateFromShell" class="feedback-date-input-shell is-active mt-6 flex cursor-pointer items-center gap-2 px-3 py-2.5">
                                        <span class="text-[11px] font-bold uppercase tracking-wide text-[#ea580c]">Start</span>
                                        <input id="feedbackDateFromNative" type="date" value="{{ $dateFrom->toDateString() }}" class="w-full border-0 bg-transparent p-0 text-[13px] text-slate-500 outline-none">
                                    </label>

                                    <label id="feedbackDateToShell" class="feedback-date-input-shell is-secondary mt-3 flex cursor-pointer items-center gap-2 px-3 py-2.5">
                                        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">End</span>
                                        <input id="feedbackDateToNative" type="date" value="{{ $dateTo->toDateString() }}" class="w-full border-0 bg-transparent p-0 text-[13px] text-slate-500 outline-none">
                                    </label>

                                    <button type="button" id="feedbackDateApply" class="feedback-apply-button mt-6 inline-flex w-full items-center justify-center rounded-full px-4 py-2.5 text-sm font-semibold text-white">
                                        Apply
                                    </button>
                                </div>

                                <div class="p-4">
                                    <div class="flex items-center justify-between">
                                        <button type="button" id="feedbackMonthPrev" class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Previous month">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="m15 18-6-6 6-6"/>
                                            </svg>
                                        </button>
                                        <h3 id="feedbackMonthLabel" class="text-[16px] font-semibold text-slate-800"></h3>
                                        <button type="button" id="feedbackMonthNext" class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Next month">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="m9 18 6-6-6-6"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="mt-5 grid grid-cols-7 gap-y-2 text-center text-[12px] text-slate-400">
                                        <span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span>
                                    </div>

                                    <div id="feedbackCalendarGrid" class="mt-4 grid grid-cols-7 gap-y-2 text-center"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <details class="filter-menu" data-filter-menu="timeframe">
                        <summary>
                            <svg xmlns="http://www.w3.org/2000/svg" class="filter-menu-icon h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M7 12h10m-7 6h4" />
                            </svg>
                            <span class="filter-menu-label" data-filter-label="timeframe">{{ ucfirst($timeframe ?? 'monthly') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="filter-menu-chevron h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </summary>
                        <div class="filter-menu-list">
                            <button type="button" class="filter-menu-item {{ ($timeframe ?? 'monthly') === 'daily' ? 'is-active' : '' }}" data-filter-option="timeframe" data-value="daily" data-label="Daily">Daily</button>
                            <button type="button" class="filter-menu-item {{ ($timeframe ?? 'monthly') === 'weekly' ? 'is-active' : '' }}" data-filter-option="timeframe" data-value="weekly" data-label="Weekly">Weekly</button>
                            <button type="button" class="filter-menu-item {{ ($timeframe ?? 'monthly') === 'monthly' ? 'is-active' : '' }}" data-filter-option="timeframe" data-value="monthly" data-label="Monthly">Monthly</button>
                        </div>
                    </details>

                    <details class="filter-menu" data-filter-menu="store">
                        <summary>
                            <svg xmlns="http://www.w3.org/2000/svg" class="filter-menu-icon h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5.25 21V8.25m13.5 12.75V8.25M9 21V3.75h6V21M4.5 8.25h15" />
                            </svg>
                            <span class="filter-menu-label" data-filter-label="store">{{ $stores->firstWhere('store_id', request('store_id'))?->name ?? ($isRestrictedToAssignedStore ? ($stores->first()?->name ?? 'Assigned store') : 'All stores') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="filter-menu-chevron h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </summary>
                        <div class="filter-menu-list">
                            @unless($isRestrictedToAssignedStore)
                                <button type="button" class="filter-menu-item {{ request('store_id') ? '' : 'is-active' }}" data-filter-option="store" data-value="" data-label="All stores">All stores</button>
                            @endunless
                            @foreach($stores as $store)
                                <button type="button" class="filter-menu-item {{ (string) request('store_id') === (string) $store->store_id ? 'is-active' : '' }}" data-filter-option="store" data-value="{{ $store->store_id }}" data-label="{{ $store->name }}">
                                    {{ $store->name }}
                                </button>
                            @endforeach
                        </div>
                    </details>
                </div>
            </div>

            <div class="grid min-h-[680px] xl:grid-cols-[400px,minmax(0,1fr)]">
                <aside class="feedback-aside p-4">
                    <form method="GET" action="{{ route('feedbacks.index') }}" class="mb-3">
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                        <input type="hidden" name="store_id" value="{{ request('store_id') }}">
                        <input type="hidden" name="timeframe" value="{{ request('timeframe', 'monthly') }}">
                        <input type="hidden" name="date_from" value="{{ request('date_from', $dateFrom->toDateString()) }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to', $dateTo->toDateString()) }}">

                        <div class="relative">
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Search by guest or email"
                                class="message-search w-full py-2.5 pl-11 pr-4 text-sm text-slate-700 outline-none transition focus:border-sky-300 focus:shadow-[0_0_0_4px_rgba(147,197,253,0.14)]"
                            >
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                </svg>
                            </span>
                        </div>
                    </form>

                    <div class="feedback-scrollbar mt-3 max-h-[680px] space-y-2 overflow-y-auto pr-1">
                        @forelse($visibleFeedbacks as $feedback)
                            @php
                                $selected = $selectedFeedback && $feedback->feedback_id === $selectedFeedback->feedback_id;
                                $feedbackName = $feedback->customer_name ?: 'Anonymous Guest';
                                $feedbackContact = $feedback->customer_email ?: ($feedback->customer_phone ?: 'No contact details');
                                [$priorityLabel, $priorityClass] = $feedbackPriority($feedback);
                                $cardClasses = $selected
                                    ? 'border-slate-900 bg-white shadow-[0_10px_24px_rgba(15,23,42,0.08)]'
                                    : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm';
                            @endphp

                            <a href="{{ route('feedbacks.index', $buildQuery(['feedback' => $feedback->feedback_id])) }}" class="block border-b border-slate-200/80 px-2 py-3 transition last:border-b-0 {{ $selected ? 'rounded-[18px] bg-white/70 shadow-[0_8px_20px_rgba(15,23,42,0.05)]' : 'hover:rounded-[18px] hover:bg-white/45' }}">
                                <div class="min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <h3 class="truncate text-[13px] font-semibold text-slate-900">{{ $feedbackName }}</h3>
                                            <p class="truncate text-[10px] text-slate-400">{{ $feedbackContact }}</p>
                                        </div>

                                        <span class="rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $priorityClass }}">
                                            {{ $priorityLabel }}
                                        </span>
                                    </div>

                                    <div class="mt-2 flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5">
                                            {!! $formatStars($feedback->overall_rating, 'h-3 w-3') !!}
                                            <span class="text-[11px] font-semibold text-slate-700">{{ $feedback->overall_rating ? number_format($feedback->overall_rating, 1) : '-' }}</span>
                                        </div>
                                        <span class="text-[10px] text-slate-400">{{ optional($feedback->created_at)->format('M d, Y h:i A') }}</span>
                                    </div>

                                    <div class="mt-2 flex items-center justify-between gap-2 text-[10px] text-slate-500">
                                        <span class="truncate">{{ $feedback->store->name ?? 'Unknown Store' }}</span>
                                        <span class="{{ $feedback->is_resolved ? 'text-emerald-600' : 'text-orange-500' }}">
                                            {{ $feedback->is_resolved ? 'Resolved' : 'Unresolved' }}
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="rounded-[18px] border border-dashed border-slate-200 bg-white/85 px-4 py-10 text-center text-xs text-slate-500 backdrop-blur-sm">
                                No feedback matched the current filters.
                            </div>
                        @endforelse
                    </div>
                </aside>

                <section class="bg-[#fcfdff] p-3 sm:p-4">
                    @if($selectedFeedback)
                            @php
                                $selectedName = $selectedFeedback->customer_name ?: 'Anonymous Guest';
                                $textAnswers = $selectedFeedback->answers->filter(fn ($answer) => filled($answer->answer_text));
                                [$selectedPriorityLabel, $selectedPriorityClass] = $feedbackPriority($selectedFeedback);
                            @endphp

                        <div class="space-y-3">
                            <div class="rounded-[24px] border border-white/80 bg-white/88 px-4 py-4 shadow-[0_14px_34px_rgba(15,23,42,0.06)] backdrop-blur-xl">
                                <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-[22px] font-semibold tracking-tight text-slate-900">{{ $selectedName }}</h3>
                                            <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $selectedFeedback->is_resolved ? 'bg-emerald-50 text-emerald-600' : 'bg-orange-50 text-orange-500' }}">
                                                {{ $selectedFeedback->is_resolved ? 'Resolved' : 'Unresolved' }}
                                            </span>
                                        </div>

                                        <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-slate-500">
                                            <span class="rounded-full bg-slate-100 px-3 py-1.5">{{ $selectedFeedback->customer_email ?? 'No email provided' }}</span>
                                        </div>
                                    </div>

                                    <div class="grid min-w-full gap-2 sm:grid-cols-[1.35fr,1fr] xl:min-w-[420px] xl:grid-cols-[1.45fr,1fr,1fr]">
                                        <div class="rounded-[18px] border border-white/80 bg-slate-50/85 px-4 py-3 text-center">
                                            <p class="text-[10px] uppercase tracking-[0.22em] text-slate-400">Store</p>
                                            <p class="mt-2 text-xs font-semibold text-slate-800">{{ $selectedFeedback->store->name ?? '-' }}</p>
                                        </div>
                                        <div class="rounded-[18px] border border-white/80 bg-slate-50/85 px-3 py-3 text-center">
                                            <p class="text-[10px] uppercase tracking-[0.22em] text-slate-400">Rating</p>
                                            <div class="mt-2 flex items-center justify-center gap-1.5">
                                                <span class="text-sm font-semibold text-slate-900">{{ $selectedFeedback->overall_rating ? number_format($selectedFeedback->overall_rating, 1) : '-' }}</span>
                                                {!! $formatStars($selectedFeedback->overall_rating, 'h-3 w-3') !!}
                                            </div>
                                        </div>
                                        <div class="rounded-[18px] border border-white/80 bg-slate-50/85 px-3 py-3 text-center">
                                            <p class="text-[10px] uppercase tracking-[0.22em] text-slate-400">Status</p>
                                            <p class="mt-2 text-xs font-semibold {{ $selectedFeedback->is_resolved ? 'text-emerald-600' : 'text-orange-500' }}">
                                                {{ $selectedFeedback->is_resolved ? 'Resolved' : 'Open' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[22px] border border-white/80 bg-white/88 px-4 py-4 shadow-[0_14px_34px_rgba(15,23,42,0.06)] backdrop-blur-xl">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                        <span>{{ optional($selectedFeedback->created_at)->format('M d, Y h:i A') }}</span>
                                        <span class="text-slate-300">|</span>
                                        <span class="font-semibold text-slate-700">{{ $selectedFeedback->store->name ?? 'Unknown Store' }}</span>
                                    </div>

                                    <form id="resolveFeedbackForm{{ $selectedFeedback->feedback_id }}" action="{{ route('feedbacks.resolution', $selectedFeedback) }}" method="POST" class="flex items-center gap-3" data-loading-message="Updating feedback resolution status...">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="store_id" value="{{ request('store_id') }}">
                                        <input type="hidden" name="timeframe" value="{{ $timeframe ?? 'monthly' }}">
                                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                                        <input type="hidden" name="search" value="{{ request('search') }}">
                                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                                        <input
                                            type="hidden"
                                            id="resolveFeedbackValue{{ $selectedFeedback->feedback_id }}"
                                            name="is_resolved"
                                            value="{{ $selectedFeedback->is_resolved ? 0 : 1 }}"
                                        >

                                        <span class="text-[11px] font-medium {{ $selectedFeedback->is_resolved ? 'text-emerald-600' : 'text-green-600' }}">
                                            {{ $selectedFeedback->is_resolved ? 'Resolved' : 'Mark resolved' }}
                                        </span>
                                        <label
                                            class="inline-flex cursor-pointer items-center rounded-full px-2 py-1 transition {{ $selectedFeedback->is_resolved ? 'bg-emerald-50' : 'bg-green-50 ring-1 ring-green-200 hover:bg-green-100' }}"
                                            aria-label="Toggle resolved state"
                                        >
                                            <span class="sr-only">Toggle resolved state</span>
                                            <input
                                                type="checkbox"
                                                class="sr-only"
                                                {{ $selectedFeedback->is_resolved ? 'checked' : '' }}
                                                onchange="submitResolveToggle(this, 'resolveFeedbackForm{{ $selectedFeedback->feedback_id }}', 'resolveFeedbackValue{{ $selectedFeedback->feedback_id }}')"
                                            >
                                            <span class="resolve-switch"></span>
                                        </label>
                                    </form>
                                </div>

                                <div class="mt-4 rounded-[18px] border border-white/80 bg-slate-50/72">
                                    @forelse($selectedFeedback->answers as $answer)
                                        <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-3 py-3 last:border-b-0">
                                            <div class="flex min-w-0 flex-1 gap-3">
                                                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-slate-100 text-sm">
                                                    @if(!is_null($answer->answer_rating))
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 0 0 .95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.37 2.449a1 1 0 0 0-.364 1.118l1.287 3.957c.3.921-.755 1.688-1.538 1.118l-3.37-2.449a1 1 0 0 0-1.176 0l-3.37 2.449c-.783.57-1.838-.197-1.538-1.118l1.287-3.957a1 1 0 0 0-.364-1.118L2.073 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 0 0 .95-.69l1.276-3.957Z"/>
                                                        </svg>
                                                    @elseif(!empty($answer->answer_attachment))
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5h10.5A2.25 2.25 0 0 1 19.5 9.75v7.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 17.25v-7.5A2.25 2.25 0 0 1 6.75 7.5Z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m7.5 15 2.25-2.25a1.5 1.5 0 0 1 2.121 0L14.25 15l1.129-1.129a1.5 1.5 0 0 1 2.121 0L19.5 15.75" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5h.008v.008h-.008V10.5Z" />
                                                        </svg>
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6 2.52-2.52A2 2 0 0 0 7.93 17H19a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 1 1.732Z" />
                                                        </svg>
                                                    @endif
                                                </div>

                                                <div class="min-w-0">
                                                    @if(!is_null($answer->answer_rating))
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            {!! $formatStars($answer->answer_rating, 'h-3.5 w-3.5') !!}
                                                            <span class="text-xs font-semibold text-slate-700">{{ $answer->answer_rating }}/5</span>
                                                        </div>
                                                        <p class="mt-1 text-xs font-semibold text-slate-800">{{ $answer->display_question }}</p>
                                                        @if(filled($answer->answer_comment))
                                                            <p class="mt-1 text-xs leading-5 text-slate-600">{{ $answer->answer_comment }}</p>
                                                        @endif
                                                    @else
                                                        <p class="text-xs font-semibold text-slate-800">{{ $answer->display_question }}</p>
                                                        @if(!empty($answer->answer_attachment))
                                                            <div class="mt-2 space-y-2">
                                                                <a href="{{ Storage::url($answer->answer_attachment) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1.5 text-[11px] font-semibold text-sky-700 transition hover:bg-sky-100">
                                                                    <span>View attachment</span>
                                                                </a>
                                                                <img src="{{ Storage::url($answer->answer_attachment) }}" alt="Feedback attachment" class="max-h-40 rounded-xl border border-slate-200 object-cover">
                                                            </div>
                                                        @else
                                                            <p class="mt-1 text-xs leading-5 text-slate-600">{{ $answer->answer_text ?: '-' }}</p>
                                                        @endif
                                                        @if(filled($answer->answer_comment))
                                                            <p class="mt-1 text-[11px] leading-5 text-slate-500">Comment: {{ $answer->answer_comment }}</p>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="pt-1">
                                                @if(!is_null($answer->answer_rating))
                                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-600">
                                                        {{ $answer->answer_rating }}/5
                                                    </span>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8.25 9a3.75 3.75 0 1 1 7.06 1.78c-.34.68-.82 1.21-1.31 1.72-.69.72-1.39 1.45-1.39 2.5v.25" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" />
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-3 py-8 text-center text-xs text-slate-500">
                                            No answers found.
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-[22px] border border-white/80 bg-white/88 px-4 py-4 shadow-[0_14px_34px_rgba(15,23,42,0.06)] backdrop-blur-xl">
                                <form action="{{ route('feedbacks.reply', $selectedFeedback) }}" method="POST" class="space-y-3" data-loading-message="Saving admin reply...">
                                    @csrf
                                    @method('PUT')

                                    <input type="hidden" name="store_id" value="{{ request('store_id') }}">
                                    <input type="hidden" name="timeframe" value="{{ $timeframe ?? 'monthly' }}">
                                    <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                                    <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                                    <input type="hidden" name="search" value="{{ request('search') }}">
                                    <input type="hidden" name="tab" value="{{ $activeTab }}">

                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-[10px] uppercase tracking-[0.24em] text-slate-400">Admin Reply</p>
                                            <h4 class="mt-1 text-sm font-semibold text-slate-900">Respond to this guest</h4>
                                        </div>
                                        @if($selectedFeedback->admin_replied_at)
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-600">
                                                Updated {{ $selectedFeedback->admin_replied_at->format('M d') }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="rounded-[16px] border border-white/80 bg-slate-50/78 px-3 py-3">
                                        <p class="text-[11px] leading-5 text-slate-500">Suggested approach: thank the guest, acknowledge the issue, and mention the concrete action your team will take.</p>
                                    </div>

                                    <textarea
                                        name="admin_reply"
                                        rows="3"
                                        placeholder="Hi, thank you for sharing your experience with us..."
                                        class="h-28 w-full resize-y rounded-[16px] border border-slate-200/90 bg-white/92 px-3 py-2.5 text-xs leading-5 text-slate-700 outline-none transition focus:border-sky-300 focus:shadow-[0_0_0_4px_rgba(147,197,253,0.14)]"
                                    >{{ old('admin_reply', $selectedFeedback->admin_reply) }}</textarea>

                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-[11px] text-slate-400">
                                            {{ filled($selectedFeedback->admin_reply) ? 'Saving will update the current reply only.' : 'You can send a reply without changing the resolved toggle.' }}
                                        </p>
                                        <button class="primary-button px-4 py-2.5">
                                            {{ filled($selectedFeedback->admin_reply) ? 'Update Reply' : 'Send Reply' }}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="flex items-center justify-between gap-3 rounded-[22px] border border-rose-100/80 bg-white/88 px-4 py-4 shadow-[0_14px_34px_rgba(15,23,42,0.06)] backdrop-blur-xl">
                                <div>
                                    <p class="text-[10px] uppercase tracking-[0.24em] text-rose-300">Danger Zone</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">Delete feedback record</p>
                                    <p class="mt-1 text-[11px] text-slate-500">Remove this only if it is duplicated or invalid.</p>
                                </div>

                                <form action="{{ route('feedbacks.destroy', $selectedFeedback) }}" method="POST" data-confirm-message="Delete this feedback record permanently?" data-confirm-title="Delete feedback" data-confirm-label="Delete" data-loading-message="Deleting feedback record...">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-[10px] bg-rose-500 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-rose-600">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="flex min-h-[480px] items-center justify-center rounded-[18px] border border-dashed border-sky-200/90 bg-white/82 text-center backdrop-blur-sm">
                            <div class="max-w-sm px-6">
                                <div class="text-4xl text-sky-400">&#9993;</div>
                                <h3 class="mt-3 text-lg font-semibold text-slate-800">No feedback selected</h3>
                                <p class="mt-2 text-xs leading-5 text-slate-500">Try changing the store, timeframe, or search filter to load a feedback record.</p>
                            </div>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>

    <script>
        function submitResolveToggle(toggle, formId, valueId) {
            const form = document.getElementById(formId);
            const valueInput = document.getElementById(valueId);

            if (!form || !valueInput) {
                return;
            }

            valueInput.value = toggle.checked ? '1' : '0';
            form.submit();
        }

        (() => {
            const routeBase = @json(route('feedbacks.index'));
            const currentParams = new URLSearchParams(window.location.search);
            const pending = {
                store: currentParams.get('store_id') ?? '',
                timeframe: currentParams.get('timeframe') ?? @json($timeframe ?? 'monthly'),
            };

            const labels = {
                store: document.querySelector('[data-filter-label="store"]'),
                timeframe: document.querySelector('[data-filter-label="timeframe"]'),
            };

            const closeMenus = () => {
                document.querySelectorAll('[data-filter-menu]').forEach((menu) => {
                    menu.removeAttribute('open');
                });
            };

            document.querySelectorAll('[data-filter-option]').forEach((option) => {
                option.addEventListener('click', () => {
                    const type = option.dataset.filterOption;
                    const value = option.dataset.value ?? '';
                    const label = option.dataset.label ?? option.textContent.trim();

                    pending[type] = value;

                    if (labels[type]) {
                        labels[type].textContent = label;
                    }

                    document.querySelectorAll(`[data-filter-option="${type}"]`).forEach((item) => {
                        item.classList.remove('is-active');
                    });
                    option.classList.add('is-active');

                    closeMenus();

                    const params = new URLSearchParams(currentParams.toString());

                    params.set('timeframe', pending.timeframe);
                    params.set('tab', params.get('tab') || 'unresolved');
                    params.delete('feedback');

                    if (pending.store === '') {
                        params.delete('store_id');
                    } else {
                        params.set('store_id', pending.store);
                    }

                    window.location.href = `${routeBase}?${params.toString()}`;
                });
            });

            document.addEventListener('click', (event) => {
                if (!event.target.closest('[data-filter-menu]')) {
                    closeMenus();
                }
            });
        })();

        (() => {
            const routeBase = @json(route('feedbacks.index'));
            const currentParams = new URLSearchParams(window.location.search);
            const feedbackDateTrigger = document.getElementById('feedbackDateTrigger');
            const feedbackDateTriggerLabel = document.getElementById('feedbackDateTriggerLabel');
            const feedbackDatePopover = document.getElementById('feedbackDatePopover');
            const feedbackDateFrom = document.getElementById('feedbackDateFrom');
            const feedbackDateTo = document.getElementById('feedbackDateTo');
            const feedbackDateFromShell = document.getElementById('feedbackDateFromShell');
            const feedbackDateToShell = document.getElementById('feedbackDateToShell');
            const feedbackDateFromNative = document.getElementById('feedbackDateFromNative');
            const feedbackDateToNative = document.getElementById('feedbackDateToNative');
            const feedbackMonthLabel = document.getElementById('feedbackMonthLabel');
            const feedbackCalendarGrid = document.getElementById('feedbackCalendarGrid');
            const feedbackMonthPrev = document.getElementById('feedbackMonthPrev');
            const feedbackMonthNext = document.getElementById('feedbackMonthNext');
            const feedbackDateApply = document.getElementById('feedbackDateApply');
            const feedbackPresetSelect = document.getElementById('feedbackPresetSelect');

            if (!feedbackDateTrigger || !feedbackDatePopover || !feedbackDateFrom || !feedbackDateTo || !feedbackDateFromShell || !feedbackDateToShell || !feedbackDateFromNative || !feedbackDateToNative || !feedbackMonthLabel || !feedbackCalendarGrid || !feedbackMonthPrev || !feedbackMonthNext || !feedbackDateApply || !feedbackPresetSelect) {
                return;
            }

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

            const formatDateLabel = (date) => `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
            const sameDate = (left, right) => left.getFullYear() === right.getFullYear() && left.getMonth() === right.getMonth() && left.getDate() === right.getDate();
            const buildTriggerLabel = (fromDate, toDate) => `${formatDateLabel(fromDate)} - ${formatDateLabel(toDate)}`;

            const getPresetValue = (fromDate, toDate) => {
                const today = new Date();
                const normalizedToday = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                const weekStart = new Date(normalizedToday);
                weekStart.setDate(normalizedToday.getDate() - normalizedToday.getDay());
                const monthStart = new Date(normalizedToday.getFullYear(), normalizedToday.getMonth(), 1);

                if (sameDate(fromDate, normalizedToday) && sameDate(toDate, normalizedToday)) return 'today';
                if (sameDate(fromDate, weekStart) && sameDate(toDate, normalizedToday)) return 'this_week';
                if (sameDate(fromDate, monthStart) && sameDate(toDate, normalizedToday)) return 'this_month';
                return 'custom';
            };

            let activeField = 'start';
            let selectedStart = parseDateInput(feedbackDateFrom.value);
            let selectedEnd = parseDateInput(feedbackDateTo.value);
            let visibleMonth = new Date(selectedEnd.getFullYear(), selectedEnd.getMonth(), 1);

            const syncFieldState = () => {
                feedbackDateFromShell.classList.toggle('is-active', activeField === 'start');
                feedbackDateToShell.classList.toggle('is-active', activeField === 'end');
            };

            const syncNativeInputs = () => {
                feedbackDateFromNative.value = formatDateInput(selectedStart);
                feedbackDateToNative.value = formatDateInput(selectedEnd);
                feedbackDateFromNative.max = formatDateInput(selectedEnd);
                feedbackDateToNative.min = formatDateInput(selectedStart);
                feedbackDateTriggerLabel.textContent = buildTriggerLabel(selectedStart, selectedEnd);
                feedbackPresetSelect.value = getPresetValue(selectedStart, selectedEnd);
                syncFieldState();
            };

            const renderCalendar = () => {
                feedbackMonthLabel.textContent = visibleMonth.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                feedbackCalendarGrid.innerHTML = '';

                const firstVisible = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth(), 1);
                const firstWeekday = firstVisible.getDay();
                const gridStart = new Date(firstVisible);
                gridStart.setDate(firstVisible.getDate() - firstWeekday);

                for (let index = 0; index < 42; index += 1) {
                    const cellDate = new Date(gridStart);
                    cellDate.setDate(gridStart.getDate() + index);

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'feedback-calendar-cell mx-auto flex items-center justify-center';
                    button.textContent = String(cellDate.getDate());

                    const inVisibleMonth = cellDate.getMonth() === visibleMonth.getMonth();
                    const inRange = cellDate >= selectedStart && cellDate <= selectedEnd;
                    const isStart = sameDate(cellDate, selectedStart);
                    const isEnd = sameDate(cellDate, selectedEnd);

                    if (!inVisibleMonth) button.classList.add('is-muted');
                    if (inRange) button.classList.add('is-in-range');
                    if (isStart) button.classList.add('is-start');
                    if (isEnd) button.classList.add('is-end');

                    button.addEventListener('click', () => {
                        if (activeField === 'start') {
                            selectedStart = new Date(cellDate);
                            if (selectedStart > selectedEnd) selectedEnd = new Date(cellDate);
                            activeField = 'end';
                        } else {
                            selectedEnd = new Date(cellDate);
                            if (selectedEnd < selectedStart) selectedStart = new Date(cellDate);
                            activeField = 'start';
                        }

                        syncNativeInputs();
                        renderCalendar();
                    });

                    feedbackCalendarGrid.appendChild(button);
                }
            };

            const openPopover = () => {
                feedbackDatePopover.hidden = false;
                feedbackDateTrigger.setAttribute('aria-expanded', 'true');
                syncNativeInputs();
                renderCalendar();
            };

            const closePopover = () => {
                feedbackDatePopover.hidden = true;
                feedbackDateTrigger.setAttribute('aria-expanded', 'false');
            };

            feedbackDateTrigger.addEventListener('click', () => {
                if (feedbackDatePopover.hidden) {
                    openPopover();
                } else {
                    closePopover();
                }
            });

            feedbackMonthPrev.addEventListener('click', () => {
                visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() - 1, 1);
                renderCalendar();
            });

            feedbackMonthNext.addEventListener('click', () => {
                visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 1);
                renderCalendar();
            });

            feedbackDateFromShell.addEventListener('click', () => {
                activeField = 'start';
                visibleMonth = new Date(selectedStart.getFullYear(), selectedStart.getMonth(), 1);
                syncFieldState();
                renderCalendar();
            });

            feedbackDateToShell.addEventListener('click', () => {
                activeField = 'end';
                visibleMonth = new Date(selectedEnd.getFullYear(), selectedEnd.getMonth(), 1);
                syncFieldState();
                renderCalendar();
            });

            feedbackDateFromNative.addEventListener('change', () => {
                selectedStart = parseDateInput(feedbackDateFromNative.value);
                if (selectedStart > selectedEnd) selectedEnd = new Date(selectedStart);
                activeField = 'end';
                visibleMonth = new Date(selectedStart.getFullYear(), selectedStart.getMonth(), 1);
                syncNativeInputs();
                renderCalendar();
            });

            feedbackDateToNative.addEventListener('change', () => {
                selectedEnd = parseDateInput(feedbackDateToNative.value);
                if (selectedEnd < selectedStart) selectedStart = new Date(selectedEnd);
                activeField = 'start';
                visibleMonth = new Date(selectedEnd.getFullYear(), selectedEnd.getMonth(), 1);
                syncNativeInputs();
                renderCalendar();
            });

            feedbackPresetSelect.addEventListener('change', () => {
                const today = new Date();
                const normalizedToday = new Date(today.getFullYear(), today.getMonth(), today.getDate());

                if (feedbackPresetSelect.value === 'today') {
                    selectedStart = new Date(normalizedToday);
                    selectedEnd = new Date(normalizedToday);
                } else if (feedbackPresetSelect.value === 'this_week') {
                    const weekStart = new Date(normalizedToday);
                    weekStart.setDate(normalizedToday.getDate() - normalizedToday.getDay());
                    selectedStart = new Date(weekStart.getFullYear(), weekStart.getMonth(), weekStart.getDate());
                    selectedEnd = new Date(normalizedToday);
                } else if (feedbackPresetSelect.value === 'this_month') {
                    selectedStart = new Date(normalizedToday.getFullYear(), normalizedToday.getMonth(), 1);
                    selectedEnd = new Date(normalizedToday);
                } else {
                    activeField = 'start';
                }

                if (feedbackPresetSelect.value !== 'custom') activeField = 'start';
                visibleMonth = new Date(selectedEnd.getFullYear(), selectedEnd.getMonth(), 1);
                syncNativeInputs();
                renderCalendar();
            });

            feedbackDateApply.addEventListener('click', () => {
                const params = new URLSearchParams(currentParams.toString());
                params.set('date_from', formatDateInput(selectedStart));
                params.set('date_to', formatDateInput(selectedEnd));
                params.set('tab', params.get('tab') || 'unresolved');
                params.delete('feedback');
                closePopover();
                window.location.href = `${routeBase}?${params.toString()}`;
            });

            document.addEventListener('click', (event) => {
                if (!event.target.closest('#feedbackDatePopover') && !event.target.closest('#feedbackDateTrigger')) {
                    closePopover();
                }
            });

            syncNativeInputs();
            renderCalendar();
        })();
    </script>
@endsection
