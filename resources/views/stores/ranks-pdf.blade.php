<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Store Ranks</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 12px; }
        .header { margin-bottom: 18px; }
        .title { font-size: 22px; font-weight: 700; margin-bottom: 6px; }
        .meta { font-size: 11px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dbe3ee; padding: 10px 12px; text-align: left; }
        th { background: #f8fafc; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; }
        td { font-size: 12px; }
    </style>
</head>
<body>
    @php
        $timeframeLabel = match ($timeframe ?? 'monthly') {
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            default => 'Monthly',
        };
    @endphp

    <div class="header">
        <div class="title">Store Ranks</div>
        <div class="meta">Timeframe: {{ $timeframeLabel }}</div>
        <div class="meta">Generated: {{ $generatedAt->format('M d, Y h:i A') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Store Name</th>
                <th>Store Number</th>
                <th>Status</th>
                <th>Stars</th>
                <th>Feedback Count</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stores as $store)
                <tr>
                    <td>{{ $store->rank_position ? '#' . $store->rank_position : '—' }}</td>
                    <td>{{ $store->name }}</td>
                    <td>{{ $store->store_number }}</td>
                    <td>{{ ucfirst($store->status) }}</td>
                    <td>{{ !is_null($store->average_rating) ? number_format($store->average_rating, 1) . '/5' : 'No ratings yet' }}</td>
                    <td>{{ $store->feedbacks_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No stores found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
