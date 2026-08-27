@extends('layouts.admin')

@section('content')
    <div class="space-y-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-500">Overall Analytics</p>
                <h2 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ $store->name }}</h2>
                <p class="mt-1 text-sm text-slate-500">Summary view for top staff and top manager performance in this branch.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('stores.staff.index', $store) }}" class="inline-flex items-center rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Manage Staff
                </a>
                <a href="{{ route('stores.staff.analytics', $store) }}" class="inline-flex items-center rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-700 transition hover:bg-sky-100">
                    Staff Analytics
                </a>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Top Staff</p>
                <p class="mt-3 text-2xl font-bold text-slate-900">{{ $topStaff['name'] ?? 'N/A' }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ !is_null($topStaff['average_rating'] ?? null) ? number_format($topStaff['average_rating'], 1) . '/5 average' : 'No ratings yet' }}</p>
            </div>

            <div class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Top Manager</p>
                <p class="mt-3 text-2xl font-bold text-slate-900">{{ $topManager['name'] ?? 'N/A' }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ !is_null($topManager['average_rating'] ?? null) ? number_format($topManager['average_rating'], 1) . '/5 average' : 'No ratings yet' }}</p>
            </div>

            <div class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Average Staff Rating</p>
                <p class="mt-3 text-2xl font-bold text-slate-900">{{ !is_null($averageStaffRating) ? number_format($averageStaffRating, 1) : '-' }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $staffCount }} detected staff names</p>
            </div>

            <div class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Average Manager Rating</p>
                <p class="mt-3 text-2xl font-bold text-slate-900">{{ !is_null($averageManagerRating) ? number_format($averageManagerRating, 1) : '-' }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $managerCount }} detected manager names</p>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Top Staff List</h3>
                        <p class="mt-1 text-sm text-slate-500">Highest-performing detected staff names for this branch.</p>
                    </div>
                    <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-700">{{ $staffCount }} names</span>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($staffAnalytics->take(5) as $item)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $item['name'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $item['mention_count'] }} mentions | {{ $item['comment_count'] }} comments</p>
                            </div>
                            <span class="text-sm font-semibold text-slate-800">{{ !is_null($item['average_rating']) ? number_format($item['average_rating'], 1) . '/5' : '-' }}</span>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">No detected staff analytics yet.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Top Manager List</h3>
                        <p class="mt-1 text-sm text-slate-500">Highest-performing detected manager names for this branch.</p>
                    </div>
                    <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ $managerCount }} names</span>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($managerAnalytics->take(5) as $item)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $item['name'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $item['mention_count'] }} mentions | {{ $item['comment_count'] }} comments</p>
                            </div>
                            <span class="text-sm font-semibold text-slate-800">{{ !is_null($item['average_rating']) ? number_format($item['average_rating'], 1) . '/5' : '-' }}</span>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">No detected manager analytics yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
