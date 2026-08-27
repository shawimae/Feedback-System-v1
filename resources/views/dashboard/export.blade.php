<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback Dashboard Export</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; margin: 0; padding: 26px; background: #fff; }
        .muted { color: #64748b; }
        .header { margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 26px; }
        .header p { margin: 4px 0 0; font-size: 12px; }
        .cards { margin: 18px 0 24px; }
        .card-table { width: 100%; border-collapse: separate; border-spacing: 12px 0; margin: 0 -12px; }
        .card-cell { width: 25%; }
        .card { border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden; }
        .card-top { height: 5px; background: linear-gradient(90deg, #f97316, #fdba74); }
        .card-body { padding: 14px; }
        .pill { float: right; padding: 4px 8px; border-radius: 999px; font-size: 10px; font-weight: bold; }
        .pill-blue { background: #eff6ff; color: #2563eb; }
        .pill-amber { background: #fffbeb; color: #d97706; }
        .icon { display: inline-block; margin-bottom: 12px; padding: 8px 10px; border-radius: 12px; background: #fff7ed; color: #f97316; font-size: 12px; font-weight: bold; }
        .metric { font-size: 34px; font-weight: bold; line-height: 1; margin: 0; }
        .label { margin: 8px 0 0; font-size: 15px; color: #334155; }
        .section { margin-top: 18px; }
        .panel-title { font-size: 18px; font-weight: bold; margin: 0 0 4px; }
        .panel-subtitle { margin: 0 0 12px; color: #64748b; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px 12px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
        th { background: #f8fafc; color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; }
        td { font-size: 12px; }
        .rank { display: inline-block; min-width: 28px; text-align: center; padding: 6px 0; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; font-weight: bold; }
        .rank.top { border-color: #fdba74; background: #fff7ed; color: #c2410c; }
        .store-number { font-size: 14px; font-weight: bold; color: #0f172a; }
        .store-name { font-size: 10px; color: #64748b; margin-top: 2px; }
        .response-text { font-size: 10px; color: #64748b; margin-top: 4px; }
        .rating { text-align: right; }
        .rating-value { font-size: 20px; font-weight: bold; color: #f97316; }
        .badge { display: inline-block; margin-top: 4px; padding: 4px 8px; border-radius: 999px; font-size: 10px; font-weight: bold; border: 1px solid #fed7aa; background: #fff7ed; color: #c2410c; }
        .no-rating { color: #94a3b8; font-size: 12px; }
        .small { font-size: 10px; color: #64748b; }
    </style>
</head>
<body>
    @php
        use App\Models\Feedback;
        use App\Models\Store;

        $periodStart = \Illuminate\Support\Carbon::parse($dateFrom)->startOfDay();
        $periodEnd = \Illuminate\Support\Carbon::parse($dateTo)->endOfDay();

        $selectedStore = $stores->firstWhere('store_id', $selectedStoreId);
        $storeLabel = $selectedStore?->name ?? 'All stores';

        $rankedStores = Store::query()
            ->when($selectedStoreId, fn ($query) => $query->where('store_id', $selectedStoreId))
            ->withCount(['feedbacks' => fn ($query) => $query->whereBetween('created_at', [$periodStart, $periodEnd])])
            ->withAvg(['feedbacks' => fn ($query) => $query->whereBetween('created_at', [$periodStart, $periodEnd])], 'overall_rating')
            ->get()
            ->map(function ($store) {
                $store->average_rating = $store->feedbacks_avg_overall_rating
                    ? round((float) $store->feedbacks_avg_overall_rating, 1)
                    : null;

                return $store;
            })
            ->sortBy([
                fn ($store) => is_null($store->average_rating) ? 1 : 0,
                fn ($store) => -($store->average_rating ?? 0),
                fn ($store) => -($store->feedbacks_count ?? 0),
                fn ($store) => strtolower($store->name ?? ''),
            ])
            ->values()
            ->map(function ($store, $index) {
                $store->rank_position = !is_null($store->average_rating) ? $index + 1 : null;
                $store->rating_label = match (true) {
                    is_null($store->average_rating) => 'No rating',
                    $store->average_rating >= 4.5 => 'Excellent',
                    $store->average_rating >= 4.0 => 'Good',
                    $store->average_rating >= 3.0 => 'Average',
                    default => 'Needs work',
                };

                return $store;
            });

        $activityDays = collect(range(6, 0))->map(fn ($offset) => now()->copy()->subDays($offset))->push(now());
        $activitySummary = $activityDays->map(function ($date) use ($selectedStoreId) {
            $count = Feedback::query()
                ->when($selectedStoreId, fn ($query) => $query->where('store_id', $selectedStoreId))
                ->whereDate('created_at', $date->toDateString())
                ->count();

            return [
                'label' => $date->format('M d'),
                'count' => $count,
            ];
        });

        $uniqueUsers = Feedback::query()
            ->when($selectedStoreId, fn ($query) => $query->where('store_id', $selectedStoreId))
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->whereNotNull('customer_email')
            ->where('customer_email', '!=', '')
            ->distinct()
            ->count('customer_email');
    @endphp

    <div class="header">
        <h1>Dashboard Overview Report</h1>
        <p class="muted">Store: {{ $storeLabel }}</p>
        <p class="muted">Date Range: {{ $dateRangeLabel }}</p>
        <p class="muted">Generated: {{ now()->format('M d, Y h:i A') }}</p>
    </div>

    <div class="cards">
        <table class="card-table">
            <tr>
                <td class="card-cell">
                    <div class="card">
                        <div class="card-top"></div>
                        <div class="card-body">
                            <span class="pill pill-blue">Active</span>
                            <div class="icon">ST</div>
                            <p class="metric">{{ $selectedStoreId ? 1 : $stores->count() }}</p>
                            <p class="label">Total Stores</p>
                        </div>
                    </div>
                </td>
                <td class="card-cell">
                    <div class="card">
                        <div class="card-top"></div>
                        <div class="card-body">
                            <span class="pill pill-blue">{{ $dateRangeLabel }}</span>
                            <div class="icon">RS</div>
                            <p class="metric">{{ $totalFeedback }}</p>
                            <p class="label">Total Responses</p>
                        </div>
                    </div>
                </td>
                <td class="card-cell">
                    <div class="card">
                        <div class="card-top"></div>
                        <div class="card-body">
                            <span class="pill pill-blue">Unique</span>
                            <div class="icon">US</div>
                            <p class="metric">{{ $uniqueUsers }}</p>
                            <p class="label">Unique Users</p>
                        </div>
                    </div>
                </td>
                <td class="card-cell">
                    <div class="card">
                        <div class="card-top"></div>
                        <div class="card-body">
                            <span class="pill pill-amber">Average</span>
                            <div class="icon">RT</div>
                            <p class="metric">{{ $averageRating ? number_format($averageRating, 1) : '0.0' }}</p>
                            <p class="label">Average Rating</p>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <p class="panel-title">Top Performers</p>
        <p class="panel-subtitle">By average rating based on the active dashboard filter</p>
        <table>
            <thead>
                <tr>
                    <th style="width: 56px; text-align: center;">#</th>
                    <th>Store</th>
                    <th style="width: 120px; text-align: right;">Rating</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rankedStores as $store)
                    <tr>
                        <td style="text-align: center;">
                            <span class="rank {{ $store->rank_position === 1 ? 'top' : '' }}">{{ $store->rank_position ?? '-' }}</span>
                        </td>
                        <td>
                            <div class="store-number">{{ $store->store_number }}</div>
                            <div class="store-name">{{ $store->name }}</div>
                            <div class="response-text">{{ $store->feedbacks_count }} {{ \Illuminate\Support\Str::plural('response', $store->feedbacks_count) }}</div>
                        </td>
                        <td class="rating">
                            @if(!is_null($store->average_rating))
                                <div class="rating-value">{{ number_format($store->average_rating, 1) }}</div>
                                <div class="badge">{{ $store->rating_label }}</div>
                            @else
                                <div class="no-rating">No rating</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="small">No ranked store data available for this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <p class="panel-title">Recent Activity</p>
        <p class="panel-subtitle">7-day response counts under the same dashboard filter</p>
        <table>
            <thead>
                <tr>
                    @foreach($activitySummary as $day)
                        <th>{{ $day['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach($activitySummary as $day)
                        <td>{{ $day['count'] }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

</body>
</html>

