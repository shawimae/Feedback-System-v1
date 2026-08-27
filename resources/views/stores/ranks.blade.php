@extends('layouts.admin')

@section('content')
    @php
        $timeframeLabel = match ($timeframe ?? 'monthly') {
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            default => 'Monthly',
        };

        $renderStars = function ($rating) {
            $rounded = (int) round($rating ?? 0);
            $html = '<div class="inline-flex items-center gap-0.5">';

            for ($i = 1; $i <= 5; $i++) {
                $color = $i <= $rounded ? 'text-amber-400' : 'text-slate-300';
                $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ' . $color . '" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 0 0 .95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.37 2.449a1 1 0 0 0-.364 1.118l1.287 3.957c.3.921-.755 1.688-1.538 1.118l-3.37-2.449a1 1 0 0 0-1.176 0l-3.37 2.449c-.783.57-1.838-.197-1.538-1.118l1.287-3.957a1 1 0 0 0-.364-1.118L2.073 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 0 0 .95-.69l1.276-3.957Z"/></svg>';
            }

            return $html . '</div>';
        };
    @endphp

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-3xl font-bold text-slate-800">Store Ranks</h2>
            <p class="mt-2 text-slate-500">Rank stores by average feedback rating and jump straight to each store's feedback inbox.</p>
        </div>

        <div class="flex flex-wrap gap-3">
            <details class="relative">
                <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 font-semibold text-slate-700 hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M6.75 12h10.5m-7.5 5.25h4.5" />
                    </svg>
                    {{ $timeframeLabel }}
                </summary>

                <div class="absolute right-0 z-10 mt-2 w-44 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
                    <a href="{{ route('stores.ranks', ['timeframe' => 'daily']) }}" class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-50">Daily</a>
                    <a href="{{ route('stores.ranks', ['timeframe' => 'weekly']) }}" class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-50">Weekly</a>
                    <a href="{{ route('stores.ranks', ['timeframe' => 'monthly']) }}" class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-50">Monthly</a>
                </div>
            </details>

            <a href="{{ route('stores.ranks.export.pdf', ['timeframe' => $timeframe ?? 'monthly']) }}" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V4.5m0 12 4.5-4.5M12 16.5 7.5 12M4.5 19.5h15" />
                </svg>
                Export PDF
            </a>

            <a href="{{ route('stores.index') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back to Stores
            </a>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-3 text-sm font-medium text-slate-600">
            Showing {{ $timeframeLabel }} ranking results
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Rank</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Store Name</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Stars</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Feedback Count</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($stores as $store)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-6 py-4 text-center text-sm font-semibold text-slate-900">
                                {{ $store->rank_position ? '#' . $store->rank_position : '—' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm font-semibold text-slate-900">{{ $store->name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $store->store_number }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $store->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                    {{ ucfirst($store->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if(!is_null($store->average_rating))
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="text-sm font-semibold text-slate-900">{{ number_format($store->average_rating, 1) }}</span>
                                        {!! $renderStars($store->average_rating) !!}
                                    </div>
                                @else
                                    <span class="text-sm text-slate-400">No ratings yet</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-slate-700">{{ $store->feedbacks_count }}</td>
                            <td class="px-6 py-4 text-right">
                                <a
                                    href="{{ route('feedbacks.index', ['store_id' => $store->store_id, 'tab' => 'all']) }}"
                                    class="inline-flex items-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
                                >
                                    View Feedback
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">
                                No stores found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
