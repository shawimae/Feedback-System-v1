@extends('layouts.admin')

@section('content')
    @php
        $activeLicenseCount = $licenses->where('license_status', 'active')->count();
        $expiredLicenseCount = $licenses->where('license_status', 'expired')->count();
        $expiringSoonCount = $licenses->filter(function ($license) {
            return $license->ends_at && $license->ends_at->isFuture() && now()->diffInDays($license->ends_at, false) <= 7;
        })->count();
        $pendingRenewalCount = ($renewalRequests ?? collect())->where('status', 'pending')->count();
    @endphp

    <style>
        :root {
            --license-primary: #4f81c7;
            --license-primary-dark: #3d6aaa;
            --license-primary-soft: #e8f0fb;
            --license-primary-soft-strong: #d7e5f8;
            --license-primary-ink: #214d84;
        }

        .license-panel {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.85);
            border-radius: 30px;
            background:
                radial-gradient(circle at top right, rgba(79, 129, 199, 0.14), transparent 24%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.96) 100%);
            box-shadow:
                0 28px 64px rgba(15, 23, 42, 0.07),
                inset 0 1px 0 rgba(255, 255, 255, 0.86);
        }

        .license-panel::before {
            content: "";
            position: absolute;
            inset: 0 auto auto 0;
            height: 1px;
            width: 100%;
            background: linear-gradient(90deg, rgba(79, 129, 199, 0.3), rgba(79, 129, 199, 0), rgba(124, 167, 223, 0.32));
        }

        .license-hero {
            display: grid;
            gap: 1rem;
        }

        .license-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            width: fit-content;
            border-radius: 999px;
            border: 1px solid rgba(79, 129, 199, 0.2);
            background: rgba(232, 240, 251, 0.95);
            padding: 0.42rem 0.8rem;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--license-primary);
        }

        .license-analytics-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        .license-analytics-card {
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 26px;
            background: rgba(255, 255, 255, 0.92);
            padding: 1.15rem 1.2rem;
            box-shadow: 0 18px 34px rgba(148, 163, 184, 0.08);
        }

        .license-analytics-card.is-positive {
            border-color: rgba(79, 129, 199, 0.32);
            background: linear-gradient(180deg, rgba(238, 244, 252, 0.96) 0%, rgba(232, 240, 251, 0.92) 100%);
        }

        .license-analytics-card.is-negative {
            border-color: rgba(253, 164, 175, 0.62);
            background: linear-gradient(180deg, rgba(255, 241, 242, 0.96) 0%, rgba(255, 245, 245, 0.92) 100%);
        }

        .license-active-card {
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.94);
            padding: 1.25rem;
            box-shadow: 0 16px 32px rgba(148, 163, 184, 0.08);
        }

        .license-label {
            display: block;
            margin-bottom: 0.6rem;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: #334155;
        }

        .license-input,
        .license-select,
        .license-textarea {
            width: 100%;
            border: 1px solid rgba(203, 213, 225, 0.95);
            border-radius: 20px;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.94) 0%, rgba(255, 255, 255, 0.98) 100%);
            padding: 0.9rem 1rem;
            font-size: 14px;
            color: #0f172a;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .license-input:focus,
        .license-select:focus,
        .license-textarea:focus {
            border-color: rgba(79, 129, 199, 0.45);
            box-shadow: 0 0 0 5px rgba(79, 129, 199, 0.12);
            background: #ffffff;
        }

        .license-textarea {
            min-height: 140px;
            resize: vertical;
        }

        .license-registry-head {
            display: grid;
            gap: 0.8rem;
        }

        .license-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.98) 100%);
        }

        .license-table-shell {
            overflow: hidden;
            border-radius: 26px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #ffffff;
            box-shadow: 0 20px 38px rgba(148, 163, 184, 0.08);
        }

        .license-table {
            min-width: 1180px;
        }

        .license-table thead th {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.16em;
            color: #64748b;
            text-transform: uppercase;
        }

        .license-table tbody tr {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .license-table tbody tr:hover {
            background: #f8fbff;
        }

        .license-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            padding: 0.42rem 0.8rem;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .license-row-checkbox {
            height: 16px;
            width: 16px;
            border-radius: 5px;
            border: 1px solid #cbd5e1;
        }

        .license-client-cell {
            min-width: 0;
        }

        .license-client-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }

        .license-client-meta {
            margin-top: 0.35rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            font-size: 12px;
            color: #64748b;
        }

        .license-modal-card {
            border-radius: 30px;
            background:
                radial-gradient(circle at top right, rgba(79, 129, 199, 0.14), transparent 22%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.98) 100%);
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.20);
        }

        .license-modal-form {
            display: grid;
            gap: 1.35rem;
        }

        .license-field-label {
            margin-bottom: 0.55rem;
            display: block;
            font-size: 0.92rem;
            font-weight: 600;
            color: #334155;
        }

        .license-status-note {
            display: flex;
            min-height: 78px;
            align-items: center;
            border: 1px solid rgba(203, 213, 225, 0.95);
            border-radius: 20px;
            background: linear-gradient(180deg, #f8fbff 0%, #f1f6fd 100%);
            padding: 0.95rem 1rem;
        }

        .license-status-note-copy {
            font-size: 0.88rem;
            line-height: 1.6;
            color: #64748b;
        }

        .renewal-request-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        .renewal-request-card {
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 1.1rem;
            box-shadow: 0 16px 30px rgba(148, 163, 184, 0.08);
        }

        .renewal-request-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.35rem 0.75rem;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        @media (min-width: 900px) {
            .license-analytics-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .renewal-request-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <div class="mx-auto max-w-7xl space-y-6">
        <section class="license-panel p-6 lg:p-8">
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="license-hero">
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Software Licensing</h2>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        onclick="document.getElementById('addClientLicenseModal').classList.remove('hidden');document.getElementById('addClientLicenseModal').classList.add('flex');"
                        class="inline-flex items-center justify-center rounded-full bg-[var(--license-primary)] px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.16em] text-white transition hover:bg-[var(--license-primary-dark)]"
                    >
                        Add Client License
                    </button>
                </div>
            </div>

            <div class="license-analytics-grid mt-6">
                <div class="license-analytics-card">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Total Licenses</p>
                    <p class="mt-3 text-[24px] font-semibold tracking-tight text-slate-900">{{ $licenses->count() }}</p>
                    <p class="mt-2 text-xs leading-5 text-slate-500">All saved client license records.</p>
                </div>

                <div class="license-analytics-card is-positive">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--license-primary)]">Active Licenses</p>
                    <p class="mt-3 text-[24px] font-semibold tracking-tight text-[var(--license-primary-ink)]">{{ $activeLicenseCount }}</p>
                    <p class="mt-2 text-xs leading-5 text-[var(--license-primary-ink)]">Clients currently marked as active.</p>
                </div>

                <div class="license-analytics-card {{ $expiredLicenseCount > 0 ? 'is-negative' : '' }}">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] {{ $expiredLicenseCount > 0 ? 'text-rose-500' : 'text-slate-400' }}">Expired Licenses</p>
                    <p class="mt-3 text-[24px] font-semibold tracking-tight {{ $expiredLicenseCount > 0 ? 'text-rose-900' : 'text-slate-900' }}">{{ $expiredLicenseCount }}</p>
                    <p class="mt-2 text-xs leading-5 {{ $expiredLicenseCount > 0 ? 'text-rose-700' : 'text-slate-500' }}">Needs renewal or reactivation.</p>
                </div>

                <div class="license-analytics-card">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Expiring Soon</p>
                    <p class="mt-3 text-[24px] font-semibold tracking-tight text-slate-900">{{ $expiringSoonCount }}</p>
                    <p class="mt-2 text-xs leading-5 text-slate-500">Licenses ending within 7 days.</p>
                </div>
            </div>
        </section>

        <section class="license-panel p-6 lg:p-8">
            <div class="flex flex-col gap-3 border-b border-slate-100 pb-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-xl font-semibold tracking-tight text-slate-900">Renewal Requests</h3>
                    <p class="mt-2 text-sm text-slate-500">Requests submitted by expired or expiring client accounts for Dev review.</p>
                </div>
                <div class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600">
                    {{ $pendingRenewalCount }} pending
                </div>
            </div>

            <div class="renewal-request-grid mt-6">
                @forelse(($renewalRequests ?? collect()) as $renewalRequest)
                    <article class="renewal-request-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-base font-semibold text-slate-900">{{ $renewalRequest->requester_name ?: 'Unknown requester' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $renewalRequest->requester_email }}</p>
                            </div>
                            <span class="renewal-request-badge {{ $renewalRequest->status === 'pending' ? 'bg-amber-50 text-amber-700' : ($renewalRequest->status === 'approved' ? 'bg-blue-50 text-blue-700' : ($renewalRequest->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700')) }}">
                                {{ \App\Models\RenewalRequest::statusLabels()[$renewalRequest->status] ?? ucfirst($renewalRequest->status) }}
                            </span>
                        </div>

                        <div class="mt-4 space-y-2 text-sm text-slate-600">
                            <p><span class="font-semibold text-slate-800">Client:</span> {{ $renewalRequest->softwareLicense?->client_name ?: $renewalRequest->softwareLicense?->license_name ?: 'No linked license' }}</p>
                            <p><span class="font-semibold text-slate-800">Requested:</span> {{ $renewalRequest->created_at?->format('M d, Y h:i A') }}</p>
                            @if(filled($renewalRequest->request_note))
                                <p><span class="font-semibold text-slate-800">Note:</span> {{ $renewalRequest->request_note }}</p>
                            @endif
                            @if(filled($renewalRequest->resolution_note))
                                <p><span class="font-semibold text-slate-800">Dev note:</span> {{ $renewalRequest->resolution_note }}</p>
                            @endif
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @if($renewalRequest->status === 'pending')
                                <form action="{{ route('renewal-requests.update', $renewalRequest) }}" method="POST" data-loading-message="Updating renewal request status...">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="rounded-2xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white transition hover:bg-blue-700">
                                        Approve
                                    </button>
                                </form>

                                <form action="{{ route('renewal-requests.update', $renewalRequest) }}" method="POST" data-loading-message="Updating renewal request status...">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-rose-600 transition hover:bg-rose-100">
                                        Reject
                                    </button>
                                </form>
                            @elseif($renewalRequest->status === 'approved')
                                <form action="{{ route('renewal-requests.update', $renewalRequest) }}" method="POST" data-loading-message="Completing renewal request...">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="rounded-2xl bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white transition hover:bg-emerald-700">
                                        Mark Completed
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rounded-[24px] border border-dashed border-slate-300 bg-white/80 p-8 text-center text-sm text-slate-500 md:col-span-2">
                        No renewal requests yet.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="license-panel p-6 lg:p-8">
            <div class="license-table-shell mt-5">
                <div class="overflow-x-auto">
                    <table class="license-table min-w-full text-sm">
                        <thead class="text-left">
                            <tr>
                                <th class="px-4 py-3 font-semibold">
                                    <input type="checkbox" class="license-row-checkbox" disabled>
                                </th>
                                <th class="px-4 py-3 font-semibold">Client</th>
                                <th class="px-4 py-3 font-semibold">License</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 font-semibold">Duration</th>
                                <th class="px-4 py-3 font-semibold">Store Limit</th>
                                <th class="px-4 py-3 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($licenses as $license)
                                <tr class="align-top">
                                    <td class="px-4 py-4">
                                        <input type="checkbox" class="license-row-checkbox" disabled>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="license-client-cell">
                                            <p class="license-client-title">{{ $license->client_name ?: 'No client name' }}</p>
                                            <div class="license-client-meta">
                                                <span class="font-mono">{{ $license->license_key ?: 'No key set' }}</span>
                                                @if($license->is_current)
                                                    <span class="font-semibold text-[var(--license-primary)]">Current Active</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="font-medium text-slate-700">{{ $license->license_name }}</p>
                                        @if(filled($license->license_notes))
                                            <p class="mt-1 max-w-xs text-xs leading-5 text-slate-500">{{ \Illuminate\Support\Str::limit($license->license_notes, 80) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="license-status-pill {{ $license->license_status === 'active' ? 'bg-emerald-50 text-emerald-700' : ($license->license_status === 'expired' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600') }}">
                                            {{ ucfirst($license->license_status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="space-y-1 text-[12px] text-slate-600">
                                            <p>{{ $license->starts_at?->format('M d, Y h:i A') ?? '-' }}</p>
                                            <p>{{ $license->ends_at?->format('M d, Y h:i A') ?? '-' }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-600">
                                        <div class="space-y-1 text-[12px]">
                                            <p class="font-semibold text-slate-800">{{ $license->max_stores ?: '-' }}</p>
                                            <p class="text-slate-500">Current usage {{ $licenseSettings['current_store_count'] }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-2">
                                            @if(!$license->is_current)
                                                <form action="{{ route('licensing.registry.activate', $license) }}" method="POST" data-confirm-title="Switch active license" data-confirm-message="Apply this client license as the current running license for the system?" data-confirm-label="Switch" data-loading-message="Switching active client license...">
                                                    @csrf
                                                    <button type="submit" class="rounded-xl border border-[var(--license-primary-soft-strong)] bg-[var(--license-primary-soft)] px-3 py-2 text-xs font-semibold text-[var(--license-primary-ink)] transition hover:bg-[#dce8f9]">
                                                        Use Now
                                                    </button>
                                                </form>
                                            @endif
                                            <button
                                                type="button"
                                                onclick="document.getElementById('editLicenseModal{{ $license->id }}').classList.remove('hidden');document.getElementById('editLicenseModal{{ $license->id }}').classList.add('flex');"
                                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No client licenses yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <div id="addClientLicenseModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 p-4 backdrop-blur-sm">
        <div class="license-modal-card w-full max-w-3xl p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">New Client</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Add client license</h3>
                </div>
                <button type="button" onclick="document.getElementById('addClientLicenseModal').classList.add('hidden');document.getElementById('addClientLicenseModal').classList.remove('flex');" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('licensing.registry.store') }}" method="POST" class="license-modal-form mt-6 md:grid-cols-2" data-loading-message="Adding client license..." data-loading-title="Saving client license">
                @csrf
                <label class="block">
                    <span class="license-field-label">License Name</span>
                    <input type="text" name="license_name" class="license-input" required>
                </label>
                <label class="block">
                    <span class="license-field-label">Client / Business Name</span>
                    <input type="text" name="client_name" class="license-input">
                </label>
                <label class="block md:col-span-2">
                    <span class="license-field-label">License Key</span>
                    <input type="text" name="license_key" class="license-input font-mono" placeholder="Leave blank to auto-generate">
                </label>
                <div class="block">
                    <span class="license-field-label">Status</span>
                    <div class="license-status-note">
                        <p class="license-status-note-copy">
                            Automatically set to <span class="font-semibold text-emerald-600">Active</span> when saved, then switches to <span class="font-semibold text-rose-600">Expired</span> after the end date.
                        </p>
                    </div>
                </div>
                <label class="block">
                    <span class="license-field-label">Max Stores</span>
                    <input type="number" min="1" name="max_stores" class="license-input">
                </label>
                <label class="block">
                    <span class="license-field-label">Start Date & Time</span>
                    <input type="datetime-local" name="license_starts_at" class="license-input">
                </label>
                <label class="block">
                    <span class="license-field-label">End Date & Time</span>
                    <input type="datetime-local" name="license_ends_at" class="license-input">
                </label>
                <label class="block md:col-span-2">
                    <span class="license-field-label">Notes</span>
                    <textarea name="license_notes" rows="4" class="license-textarea"></textarea>
                </label>
                <div class="md:col-span-2 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addClientLicenseModal').classList.add('hidden');document.getElementById('addClientLicenseModal').classList.remove('flex');" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-2xl bg-[var(--license-primary)] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[var(--license-primary-dark)]">Save Client License</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($licenses as $license)
        <div id="editLicenseModal{{ $license->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 p-4 backdrop-blur-sm">
            <div class="license-modal-card w-full max-w-3xl p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Edit Client</p>
                        <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $license->client_name ?: $license->license_name }}</h3>
                    </div>
                    <button type="button" onclick="document.getElementById('editLicenseModal{{ $license->id }}').classList.add('hidden');document.getElementById('editLicenseModal{{ $license->id }}').classList.remove('flex');" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('licensing.registry.update', $license) }}" method="POST" class="license-modal-form mt-6 md:grid-cols-2" data-loading-message="Updating client license..." data-loading-title="Saving client license">
                    @csrf
                    @method('PUT')
                    <label class="block">
                        <span class="license-field-label">License Name</span>
                        <input type="text" name="license_name" value="{{ $license->license_name }}" class="license-input" required>
                    </label>
                    <label class="block">
                        <span class="license-field-label">Client / Business Name</span>
                        <input type="text" name="client_name" value="{{ $license->client_name }}" class="license-input">
                    </label>
                    <label class="block md:col-span-2">
                        <span class="license-field-label">License Key</span>
                        <input type="text" name="license_key" value="{{ $license->license_key }}" class="license-input font-mono">
                    </label>
                    <div class="block">
                        <span class="license-field-label">Status</span>
                        <div class="license-status-note">
                            <p class="license-status-note-copy">
                                This license is currently <span class="font-semibold {{ $license->license_status === 'expired' ? 'text-rose-600' : 'text-emerald-600' }}">{{ ucfirst($license->license_status) }}</span> and updates automatically based on the end date.
                            </p>
                        </div>
                    </div>
                    <label class="block">
                        <span class="license-field-label">Max Stores</span>
                        <input type="number" min="1" name="max_stores" value="{{ $license->max_stores }}" class="license-input">
                    </label>
                    <label class="block">
                        <span class="license-field-label">Start Date & Time</span>
                        <input type="datetime-local" name="license_starts_at" value="{{ $license->starts_at?->format('Y-m-d\\TH:i') }}" class="license-input">
                    </label>
                    <label class="block">
                        <span class="license-field-label">End Date & Time</span>
                        <input type="datetime-local" name="license_ends_at" value="{{ $license->ends_at?->format('Y-m-d\\TH:i') }}" class="license-input">
                    </label>
                    <label class="block md:col-span-2">
                        <span class="license-field-label">Notes</span>
                        <textarea name="license_notes" rows="4" class="license-textarea">{{ $license->license_notes }}</textarea>
                    </label>
                    <div class="md:col-span-2 flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('editLicenseModal{{ $license->id }}').classList.add('hidden');document.getElementById('editLicenseModal{{ $license->id }}').classList.remove('flex');" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-2xl bg-[var(--license-primary)] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[var(--license-primary-dark)]">Update Client License</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    @if(filled($licenseSettings['license_ends_at']))
        <script>
            (() => {
                const remainingLabel = document.getElementById('licenseRemainingLabel');
                const endAt = new Date(@json(\Illuminate\Support\Carbon::parse($licenseSettings['license_ends_at'])->toIso8601String()));

                function renderCountdown() {
                    if (!remainingLabel || Number.isNaN(endAt.getTime())) {
                        return;
                    }

                    const diffMs = endAt.getTime() - Date.now();

                    if (diffMs <= 0) {
                        remainingLabel.textContent = 'License period has ended.';
                        return;
                    }

                    const totalHours = Math.ceil(diffMs / (1000 * 60 * 60));
                    const days = Math.floor(totalHours / 24);
                    const hours = totalHours % 24;
                    const parts = [];

                    if (days > 0) {
                        parts.push(`${days} day${days === 1 ? '' : 's'}`);
                    }

                    parts.push(`${hours} hr${hours === 1 ? '' : 's'} remaining`);
                    remainingLabel.textContent = parts.join(' ');
                }

                renderCountdown();
                window.setInterval(renderCountdown, 60000);
            })();
        </script>
    @endif
@endsection
