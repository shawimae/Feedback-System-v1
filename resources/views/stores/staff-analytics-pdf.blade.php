<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Analytics PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        .title { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .subtitle { font-size: 12px; color: #475569; margin-bottom: 12px; }
        .meta { font-size: 11px; color: #64748b; margin-bottom: 16px; }
        .cards { margin-bottom: 16px; }
        .card { display: inline-block; width: 31%; margin-right: 2%; vertical-align: top; border: 1px solid #dbe3ee; padding: 10px; box-sizing: border-box; }
        .card:last-child { margin-right: 0; }
        .card-label { font-size: 10px; text-transform: uppercase; color: #64748b; }
        .card-value { font-size: 18px; font-weight: bold; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #dbe3ee; padding: 8px 10px; text-align: left; }
        th { background: #f8fafc; color: #334155; }
        h3 { margin: 20px 0 8px; font-size: 15px; }
        .empty { margin-top: 10px; color: #64748b; }
    </style>
</head>
<body>
    <div class="title">Staff Analytics</div>
    <div class="subtitle">{{ $selectedStoreLabel }}</div>
    <div class="meta">
        Generated: {{ $generatedAt->format('M d, Y h:i A') }}<br>
        Search: {{ $search !== '' ? $search : 'All' }}
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-label">Scope</div>
            <div class="card-value">{{ $selectedStoreLabel }}</div>
        </div>
        <div class="card">
            <div class="card-label">Top Staff</div>
            <div class="card-value">{{ $topStaff['name'] ?? 'No staff yet' }}</div>
        </div>
        <div class="card">
            <div class="card-label">Top Manager</div>
            <div class="card-value">{{ $topManager['name'] ?? 'No manager yet' }}</div>
        </div>
    </div>

    <h3>Staff Analytics</h3>
    @if($staffAnalytics->isEmpty())
        <div class="empty">No staff analytics found.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Mentions</th>
                    <th>Comments</th>
                    <th>Avg Rating</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staffAnalytics as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['mention_count'] }}</td>
                        <td>{{ $item['comment_count'] }}</td>
                        <td>{{ !is_null($item['average_rating']) ? number_format($item['average_rating'], 1) : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3>Manager Analytics</h3>
    @if($managerAnalytics->isEmpty())
        <div class="empty">No manager analytics found.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Mentions</th>
                    <th>Comments</th>
                    <th>Avg Rating</th>
                </tr>
            </thead>
            <tbody>
                @foreach($managerAnalytics as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['mention_count'] }}</td>
                        <td>{{ $item['comment_count'] }}</td>
                        <td>{{ !is_null($item['average_rating']) ? number_format($item['average_rating'], 1) : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
