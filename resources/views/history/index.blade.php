@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_18px_38px_rgba(15,23,42,0.06)]">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">System History</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">User activity log</h2>
                    <p class="mt-2 text-sm text-slate-500">Track the important actions performed inside the system by admins, super admins, and dev accounts.</p>
                </div>
                <div class="rounded-2xl border border-[#d7e5f8] bg-[#e8f0fb] px-4 py-3 text-sm font-medium text-[#214d84]">
                    {{ $logs->total() }} total {{ \Illuminate\Support\Str::plural('activity', $logs->total()) }}
                </div>
            </div>

            <form method="GET" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search activity or user"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-[#4f81c7] focus:bg-white focus:ring-4 focus:ring-[#e8f0fb] xl:col-span-2">

                <select name="action" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-[#4f81c7] focus:bg-white focus:ring-4 focus:ring-[#e8f0fb]">
                    <option value="">All actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucwords(str_replace('.', ' ', $action)) }}</option>
                    @endforeach
                </select>

                <select name="role" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-[#4f81c7] focus:bg-white focus:ring-4 focus:ring-[#e8f0fb]">
                    <option value="">All roles</option>
                    @foreach($roleLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="store_id" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-[#4f81c7] focus:bg-white focus:ring-4 focus:ring-[#e8f0fb]">
                    <option value="">All stores</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->store_id }}" @selected((string) request('store_id') === (string) $store->store_id)>
                            {{ $store->store_number }} - {{ $store->name }}
                        </option>
                    @endforeach
                </select>

                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-[#4f81c7] focus:bg-white focus:ring-4 focus:ring-[#e8f0fb]">

                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-[#4f81c7] focus:bg-white focus:ring-4 focus:ring-[#e8f0fb]">

                <div class="flex gap-3 xl:col-span-6">
                    <button type="submit" class="rounded-2xl bg-[#4f81c7] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#3d6aaa]">
                        Apply Filters
                    </button>
                    <a href="{{ route('history.index') }}" class="rounded-2xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_18px_38px_rgba(15,23,42,0.06)]">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] uppercase tracking-[0.18em] text-slate-500">
                        <tr>
                            <th class="px-5 py-4 font-semibold">User</th>
                            <th class="px-5 py-4 font-semibold">Action</th>
                            <th class="px-5 py-4 font-semibold">Description</th>
                            <th class="px-5 py-4 font-semibold">Store</th>
                            <th class="px-5 py-4 font-semibold">Route</th>
                            <th class="px-5 py-4 font-semibold">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($logs as $log)
                            <tr class="align-top hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-900">{{ $log->user_name ?: 'System' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $roleLabels[$log->user_role] ?? ucfirst(str_replace('_', ' ', (string) $log->user_role)) }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border border-[#d7e5f8] bg-[#e8f0fb] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-[#214d84]">
                                        {{ str_replace('.', ' ', $log->action) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="max-w-xl leading-6 text-slate-700">{{ $log->description }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    {{ $log->store?->store_number ? $log->store->store_number . ' - ' . $log->store->name : 'System-wide' }}
                                </td>
                                <td class="px-5 py-4 text-slate-500">
                                    <p>{{ $log->route_name ?: '-' }}</p>
                                    <p class="mt-1 text-xs">{{ $log->method ?: '' }}{{ $log->ip_address ? ' | ' . $log->ip_address : '' }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-500">
                                    <p>{{ $log->created_at?->format('M d, Y') }}</p>
                                    <p class="mt-1 text-xs">{{ $log->created_at?->format('h:i A') }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">No activity logs yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-4">
                {{ $logs->links() }}
            </div>
        </section>
    </div>
@endsection
