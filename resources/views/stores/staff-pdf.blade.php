<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee List PDF</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #0f172a;
        }

        .header {
            margin-bottom: 18px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 4px;
        }

        .subtitle {
            font-size: 12px;
            color: #475569;
            margin: 0;
        }

        .meta {
            margin-top: 10px;
            font-size: 11px;
            color: #64748b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        th, td {
            border: 1px solid #dbe3ee;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f8fafc;
            font-weight: bold;
            color: #334155;
        }

        .empty {
            margin-top: 24px;
            padding: 16px;
            border: 1px solid #dbe3ee;
            background: #f8fafc;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Employee List</p>
        <p class="subtitle">{{ $store->name }}</p>
        <div class="meta">
            Generated: {{ $generatedAt->format('M d, Y h:i A') }}<br>
            Search: {{ $search !== '' ? $search : 'All' }} | Role: {{ $role !== '' ? ucfirst($role) : 'All' }} | Showing: {{ $showing }}
        </div>
    </div>

    @if($staffMembers->isEmpty())
        <div class="empty">No employees found for the selected filters.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Number</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staffMembers as $staff)
                    <tr>
                        <td>{{ $staff->name }}</td>
                        <td>{{ $staff->email ?: '-' }}</td>
                        <td>{{ $staff->phone ?: '-' }}</td>
                        <td>{{ $staff->role ?: '-' }}</td>
                        <td>{{ ucfirst($staff->status ?: '-') }}</td>
                        <td>{{ optional($staff->created_at)->format('M d, Y') ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
