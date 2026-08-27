<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Feedback System' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @php
            $adminBranding = \App\Models\AppSetting::brandingForUser(auth()->user());
            $brandLogoUrl = $adminBranding['logo_url'] ?: asset('assets/img/logo.png');
        @endphp
        :root {
            --apple-font: "SF Pro Text", "SF Pro Display", "Helvetica Neue", "Helvetica", "Arial", -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            --theme-primary: {{ $adminBranding['primary'] }};
            --theme-primary-dark: {{ $adminBranding['dark'] }};
            --theme-primary-soft: {{ $adminBranding['soft'] }};
            --theme-primary-soft-strong: {{ $adminBranding['soft_strong'] }};
            --theme-primary-ink: {{ $adminBranding['ink'] }};
            --theme-primary-ring: {{ $adminBranding['ring'] }};
            --admin-shell: #f3f7fc;
            --admin-header-bg: {{ $adminBranding['header_bg'] }};
            --admin-header-border: rgba(255, 255, 255, 0.18);
            --admin-nav-active-bg: #ffffff;
            --admin-nav-active-text: #264b7e;
            --admin-nav-hover-bg: rgba(255, 255, 255, 0.12);
            --admin-nav-text: rgba(255, 255, 255, 0.96);
            --admin-brand-text: {{ $adminBranding['brand_text'] }};
        }

        html, body {
            font-family: var(--apple-font);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            letter-spacing: -0.01em;
        }

        body {
            font-weight: 400;
            font-size: 14px;
        }

        h1, h2, h3, h4, h5, h6 {
            letter-spacing: -0.025em;
        }

        input, select, textarea, button {
            font: inherit;
        }

        .admin-header {
            background: var(--admin-header-bg);
            border-bottom: 1px solid var(--admin-header-border);
            box-shadow: 0 14px 28px rgba(51, 86, 140, 0.16);
        }

        .admin-brand-badge {
            display: none;
        }

        .admin-nav {
            background: transparent;
        }

        .admin-nav-frame {
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(214, 226, 244, 0.92);
            box-shadow: 0 12px 28px rgba(63, 98, 149, 0.12);
        }

        .admin-nav-link {
            color: #6f8098;
            position: relative;
            font-weight: 600;
            letter-spacing: -0.01em;
            text-transform: none;
            font-size: 12px;
            line-height: 1;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
            white-space: nowrap;
            border-radius: 14px;
            border: 1px solid transparent;
        }

        .admin-nav-link:hover {
            background: #f8fbff;
            color: #35557f;
            border-color: #d8e7fb;
            transform: none;
        }

        .admin-nav-link:hover .admin-nav-icon,
        .admin-nav-link:hover .admin-tab-label {
            color: #35557f;
        }

        .admin-nav-link.is-active {
            background: var(--theme-primary-soft);
            color: var(--theme-primary);
            border-color: var(--theme-primary-soft-strong);
        }

        .admin-nav-link.is-active:hover {
            color: var(--theme-primary);
            border-color: var(--theme-primary-soft-strong);
        }

        .admin-nav-link + .admin-nav-link {
            margin-left: 0;
            padding-left: 0;
        }

        .admin-nav-link + .admin-nav-link::before {
            display: none;
        }

        .admin-nav-icon {
            box-shadow: none;
        }

        .admin-nav-icon {
            color: currentColor;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            line-height: 1;
        }

        .admin-nav-link.is-active .admin-nav-icon {
            color: var(--theme-primary);
        }

        .admin-nav-link.is-active .admin-tab-label {
            font-weight: 700;
        }

        .admin-user-chip {
            display: none;
        }

        .admin-user-avatar {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: none;
        }

        .admin-ghost-button {
            background: transparent;
            color: #ffffff;
            border: 0;
            box-shadow: none;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .admin-notification-button {
            border-radius: 999px;
            height: 2.35rem;
            width: 2.35rem;
        }

        .admin-ghost-button:hover {
            background: transparent;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .admin-header-shell {
            position: relative;
        }

        .admin-header-shell::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.12);
        }

        .admin-toolbar {
            min-width: 0;
            align-items: stretch;
        }

        .admin-nav-shell {
            min-width: 0;
            flex: 1 1 auto;
            overflow: visible;
            display: flex;
            align-items: center;
        }

        .admin-nav {
            width: 100%;
            min-width: 0;
            flex-wrap: wrap;
            align-items: stretch;
        }

        .admin-nav > .admin-nav-link {
            flex: 1 1 0;
            justify-content: center;
            min-width: 0;
        }

        .admin-tab-label {
            white-space: nowrap;
        }

        .admin-actions {
            min-width: 0;
            flex: 0 0 auto;
            align-items: center;
            padding: 0;
            border-radius: 0;
            background: transparent;
            border: 0;
            box-shadow: none;
        }

        .admin-actions-divider {
            height: 24px;
            width: 1px;
            background: rgba(255, 255, 255, 0.3);
        }

        .admin-user-badge {
            border: 1px solid rgba(255, 255, 255, 0.25);
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 12px 24px rgba(35, 66, 112, 0.14);
        }

        .admin-user-badge-name {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .admin-brand-lockup {
            line-height: 1;
        }

        .admin-brand-wordmark {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -0.035em;
            color: var(--admin-brand-text);
        }

        .admin-brand-panel {
            margin-top: 4px;
            font-size: 11px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
        }

        .admin-brand-mark {
            display: inline-flex;
            height: 38px;
            width: 38px;
            align-items: center;
            justify-content: center;
            border-radius: 0;
            overflow: hidden;
            background: transparent;
            color: #ffffff;
            box-shadow: none;
        }

        .admin-brand-mark img {
            height: 100%;
            width: 100%;
            object-fit: cover;
        }

        .admin-content-shell {
            width: 100%;
            max-width: 90rem;
        }

        main .admin-content-shell {
            max-width: 96rem;
        }

        main .admin-content-shell > * {
            font-size: 0.95em;
        }

        main section[class*="rounded"],
        main article[class*="rounded"],
        main div[class*="rounded-[28px]"],
        main div[class*="rounded-[24px]"] {
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }

        main table {
            font-size: 0.82rem;
        }

        main table th,
        main table td {
            line-height: 1.35;
        }

        main table th[class*="py-"],
        main table td[class*="py-"] {
            padding-top: 0.65rem !important;
            padding-bottom: 0.65rem !important;
        }

        main .text-4xl {
            font-size: 1.95rem !important;
        }

        main .text-3xl {
            font-size: 1.65rem !important;
        }

        main .text-2xl {
            font-size: 1.35rem !important;
        }

        main .text-xl {
            font-size: 1.1rem !important;
        }

        main .text-lg {
            font-size: 1rem !important;
        }

        main .text-base {
            font-size: 0.92rem !important;
        }

        main .text-sm {
            font-size: 0.8rem !important;
        }

        main .text-xs {
            font-size: 0.72rem !important;
        }

        main .p-8 {
            padding: 1.5rem !important;
        }

        main .p-6 {
            padding: 1.2rem !important;
        }

        main .px-6 {
            padding-left: 1.15rem !important;
            padding-right: 1.15rem !important;
        }

        main .py-4 {
            padding-top: 0.85rem !important;
            padding-bottom: 0.85rem !important;
        }

        main .h-10.w-10,
        main .inline-flex.h-10.w-10 {
            height: 2.1rem !important;
            width: 2.1rem !important;
        }

        main .h-12.w-12,
        main .inline-flex.h-12.w-12 {
            height: 2.45rem !important;
            width: 2.45rem !important;
        }

        .admin-user-name {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .admin-settings-menu {
            width: min(296px, calc(100vw - 1.5rem));
        }

        .admin-settings-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-radius: 16px;
            padding: 0.72rem 0.82rem;
            font-size: 11px;
            font-weight: 600;
            color: #0f172a;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .admin-settings-link-icon-shell {
            display: inline-flex;
            height: 1.8rem;
            width: 1.8rem;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #f8fafc;
            color: #5578aa;
        }

        .admin-settings-link-icon {
            height: 0.9rem;
            width: 0.9rem;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            line-height: 1;
        }

        .admin-settings-link-label {
            flex: 1 1 auto;
            line-height: 1.2;
        }

        .admin-settings-link:hover {
            background: var(--theme-primary-soft);
            color: var(--theme-primary-ink);
            transform: translateY(-1px);
        }

        .admin-settings-link:hover .admin-settings-link-icon-shell,
        .admin-settings-link.is-active .admin-settings-link-icon-shell {
            background: rgba(79, 129, 199, 0.14);
            color: var(--theme-primary-ink);
        }

        .admin-settings-link.is-active {
            background: var(--theme-primary-soft);
            color: var(--theme-primary-ink);
        }

        .global-loading-modal {
            position: fixed;
            inset: 0;
            z-index: 90;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(5px);
        }

        .global-loading-modal.is-active {
            display: flex;
        }

        .global-loading-spinner {
            height: 52px;
            width: 52px;
            border-radius: 999px;
            border: 4px solid rgba(79, 129, 199, 0.18);
            border-top-color: var(--theme-primary);
            animation: globalSpin .9s linear infinite;
        }

        .global-confirm-modal {
            position: fixed;
            inset: 0;
            z-index: 95;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(4px);
        }

        .global-confirm-modal.is-active {
            display: flex;
        }

        @keyframes globalSpin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1023px) {
            .admin-header-shell .admin-content-shell {
                gap: 0.9rem;
            }

            .admin-nav-shell {
                width: 100%;
            }

            .admin-actions {
                width: 100%;
                justify-content: space-between;
                gap: 0.55rem;
            }

            .admin-nav-frame {
                width: 100%;
            }

            .admin-nav {
                gap: 0.55rem;
            }

            .admin-user-badge {
                display: none;
            }
        }

        @media (min-width: 1024px) {
            .admin-actions {
                gap: 0.55rem;
            }

            .admin-nav-link {
                padding: 0.78rem 1rem;
                font-size: 12px;
            }

            .admin-nav {
                gap: 0.45rem;
            }

            .admin-toolbar {
                gap: 0.9rem;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-[var(--admin-shell)]">
    <div class="flex min-h-screen flex-col">
        <header class="admin-header admin-header-shell text-slate-900">
            <div class="admin-content-shell mx-auto flex flex-col gap-2.5 px-4 py-3 lg:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3 lg:min-w-[170px]">
                    <span class="admin-brand-mark" aria-hidden="true">
                        <img src="{{ $brandLogoUrl }}" alt="System logo">
                    </span>
                    <div class="admin-brand-lockup">
                        <h1 class="admin-brand-wordmark">tugon.</h1>
                        <p class="admin-brand-panel">Feedback System</p>
                    </div>
                </div>

                <div class="admin-toolbar flex flex-1 flex-col gap-2.5 lg:mx-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="admin-nav-shell lg:justify-center">
                    <div class="admin-nav-frame w-full px-2.5 py-2 lg:w-auto lg:min-w-[640px]">
                    <nav class="admin-nav flex items-center gap-2 text-[12px] font-medium lg:flex-nowrap">
                        @if(auth()->user()?->hasFeature(\App\Models\User::FEATURE_DASHBOARD))
                            <a href="{{ route('dashboard') }}"
                               class="admin-nav-link group inline-flex items-center gap-2 rounded-[16px] px-4 py-2.5 transition {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                                <i class="bi bi-speedometer2 admin-nav-icon" aria-hidden="true"></i>
                                <span class="admin-tab-label">Dashboard</span>
                            </a>
                        @endif

                        @if(auth()->user()?->hasFeature(\App\Models\User::FEATURE_STORES))
                            <a href="{{ route('stores.index') }}"
                               class="admin-nav-link group inline-flex items-center gap-2 rounded-[16px] px-4 py-2.5 transition {{ request()->routeIs('stores.*') ? 'is-active' : '' }}">
                                <i class="bi bi-shop admin-nav-icon" aria-hidden="true"></i>
                                <span class="admin-tab-label">Stores</span>
                            </a>
                        @endif

                        @if(auth()->user()?->hasFeature(\App\Models\User::FEATURE_QUESTIONS))
                            <a href="{{ route('questions.index') }}"
                               class="admin-nav-link group inline-flex items-center gap-2 rounded-[16px] px-4 py-2.5 transition {{ request()->routeIs('questions.*') ? 'is-active' : '' }}">
                                <i class="bi bi-ui-checks-grid admin-nav-icon" aria-hidden="true"></i>
                                <span class="admin-tab-label">Survey</span>
                            </a>
                        @endif

                        @if(auth()->user()?->hasFeature(\App\Models\User::FEATURE_FEEDBACKS))
                            <a href="{{ route('feedbacks.index') }}"
                               class="admin-nav-link group inline-flex items-center gap-2 rounded-[16px] px-4 py-2.5 transition {{ request()->routeIs('feedbacks.*') ? 'is-active' : '' }}">
                                <i class="bi bi-chat-left-text admin-nav-icon" aria-hidden="true"></i>
                                <span class="admin-tab-label">Feedback</span>
                            </a>
                        @endif

                    </nav>
                    </div>
                    </div>

                    <div class="admin-actions flex flex-wrap items-center gap-2 lg:justify-end">
                        <details class="group relative" data-header-dropdown>
                            <summary class="flex cursor-pointer list-none items-center justify-center rounded-full focus:outline-none">
                                <span class="admin-ghost-button admin-notification-button relative inline-flex h-10 w-10 items-center justify-center transition">
                                    <i class="bi bi-bell admin-nav-icon text-[18px]" aria-hidden="true"></i>
                                    @if(($unreadAdminNotificationsCount ?? 0) > 0)
                                        <span class="absolute -right-1 -top-1 inline-flex min-w-[18px] items-center justify-center rounded-full bg-white px-1.5 py-0.5 text-[10px] font-semibold leading-none text-[var(--theme-primary-ink)] shadow-[0_10px_18px_rgba(35,66,112,0.16)]">
                                            {{ $unreadAdminNotificationsCount > 9 ? '9+' : $unreadAdminNotificationsCount }}
                                        </span>
                                    @endif
                                </span>
                            </summary>

                            <div class="absolute right-0 z-30 mt-2 w-[340px] overflow-hidden rounded-2xl border border-[var(--theme-primary-soft-strong)] bg-white shadow-[0_18px_45px_rgba(15,23,42,0.12)]">
                                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                    <div>
                                        <p class="text-[13px] font-semibold text-slate-900">Notifications</p>
                                        <p class="text-[11px] text-slate-500">New survey responses</p>
                                    </div>
                                </div>

                                <div class="max-h-[360px] overflow-y-auto">
                                    @forelse(($adminNotifications ?? []) as $notification)
                                        @php
                                            $notificationSubject = $notification->subject;
                                            $notificationMessage = $notification->message;

                                            if (
                                                $notification->channel === 'admin'
                                                && $notification->notification_type === 'survey_feedback'
                                                && $notification->feedback
                                            ) {
                                                $notificationSubject = $notification->feedback->customer_email ?: 'No email provided';
                                                $notificationMessage = trim(collect([
                                                    'Store Number: ' . ($notification->feedback->store->store_number ?: $notification->feedback->store->name),
                                                    !is_null($notification->feedback->overall_rating)
                                                        ? 'Rating: ' . number_format((float) $notification->feedback->overall_rating, 1)
                                                        : null,
                                                ])->filter()->implode(' | '));
                                            }
                                        @endphp
                                        <div class="border-b border-slate-100 px-4 py-3 last:border-b-0 {{ $notification->is_read ? 'bg-white' : 'bg-[var(--theme-primary-soft)]/70' }}">
                                            <div class="flex items-start gap-3">
                                                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $notification->is_read ? 'bg-slate-100 text-slate-400' : 'bg-[var(--theme-primary-soft)] text-[var(--theme-primary)]' }}">
                                                    <i class="bi bi-chat-square-text text-[14px]" aria-hidden="true"></i>
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <p class="text-[12px] font-semibold text-slate-800">{{ $notificationSubject }}</p>
                                                        @if(!$notification->is_read)
                                                            <span class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full bg-[var(--theme-primary)]"></span>
                                                        @endif
                                                    </div>
                                                    <p class="mt-1 text-[11px] leading-5 text-slate-500">{{ $notificationMessage }}</p>
                                                    <div class="mt-2 flex items-center justify-between gap-3">
                                                        <span class="text-[10px] text-slate-400">{{ $notification->created_at?->diffForHumans() }}</span>
                                                        <div class="flex items-center gap-3">
                                                            @if($notification->feedback_id)
                                                                <a href="{{ route('feedbacks.index', ['feedback' => $notification->feedback_id]) }}" class="text-[11px] font-medium text-[var(--theme-primary)] transition hover:text-[var(--theme-primary-dark)]">
                                                                    View
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-4 py-8 text-center">
                                            <p class="text-[12px] font-medium text-slate-600">No notifications yet.</p>
                                            <p class="mt-1 text-[11px] text-slate-400">New survey submissions will appear here.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </details>

                        <details class="group relative" data-header-dropdown>
                            <summary class="flex cursor-pointer list-none items-center justify-center rounded-full focus:outline-none">
                                <span class="admin-ghost-button admin-notification-button inline-flex h-10 w-10 items-center justify-center transition">
                                    <i class="bi bi-gear admin-nav-icon text-[18px]" aria-hidden="true"></i>
                                </span>
                            </summary>

                            <div class="admin-settings-menu absolute right-0 z-30 mt-2 overflow-hidden rounded-2xl border border-[var(--theme-primary-soft-strong)] bg-white p-3 shadow-[0_18px_45px_rgba(15,23,42,0.12)]">
                                <div class="rounded-2xl bg-slate-50 px-3 py-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">System owner</p>
                                    <p class="mt-1 admin-user-badge-name text-[13px] font-semibold text-slate-900" title="{{ auth()->user()->name ?? 'Admin' }}">
                                        {{ auth()->user()->name ?? 'Admin' }}
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-500">
                                        {{ auth()->user()?->role ? (\App\Models\User::roles()[auth()->user()->role] ?? ucfirst(str_replace('_', ' ', auth()->user()->role))) : 'Account' }}
                                    </p>
                                </div>

                                <div class="mt-3 space-y-1.5">
                                    @if(auth()->user()?->isDev() || auth()->user()?->isSuperAdmin())
                                        <a href="{{ route('users.index') }}" class="admin-settings-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}">
                                            <span class="admin-settings-link-icon-shell" aria-hidden="true">
                                                <i class="bi bi-people admin-settings-link-icon" aria-hidden="true"></i>
                                            </span>
                                            <span class="admin-settings-link-label">Manage Users</span>
                                        </a>

                                        <a href="{{ route('history.index') }}" class="admin-settings-link {{ request()->routeIs('history.*') ? 'is-active' : '' }}">
                                            <span class="admin-settings-link-icon-shell" aria-hidden="true">
                                                <i class="bi bi-clock-history admin-settings-link-icon" aria-hidden="true"></i>
                                            </span>
                                            <span class="admin-settings-link-label">Activity History</span>
                                        </a>
                                    @endif

                                    @if(auth()->user()?->isSuperAdmin())
                                        <a href="{{ route('branding.index') }}" class="admin-settings-link {{ request()->routeIs('branding.*') ? 'is-active' : '' }}">
                                            <span class="admin-settings-link-icon-shell" aria-hidden="true">
                                                <i class="bi bi-palette admin-settings-link-icon" aria-hidden="true"></i>
                                            </span>
                                            <span class="admin-settings-link-label">Brand Customization</span>
                                        </a>
                                    @endif

                                    @if(auth()->user()?->isDev())
                                        <a href="{{ route('licensing.index') }}" class="admin-settings-link {{ request()->routeIs('licensing.*') ? 'is-active' : '' }}">
                                            <span class="admin-settings-link-icon-shell" aria-hidden="true">
                                                <i class="bi bi-patch-check admin-settings-link-icon" aria-hidden="true"></i>
                                            </span>
                                            <span class="admin-settings-link-label">License Settings</span>
                                        </a>
                                    @endif
                                </div>

                                <div class="mt-3 border-t border-slate-100 pt-3">
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button class="admin-settings-link w-full text-left text-rose-600 hover:bg-rose-50 hover:text-rose-700" aria-label="Logout">
                                            <span class="admin-settings-link-icon-shell bg-rose-50 text-rose-500" aria-hidden="true">
                                                <i class="bi bi-box-arrow-right admin-settings-link-icon" aria-hidden="true"></i>
                                            </span>
                                            <span class="admin-settings-link-label">Logout</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 pb-2 pt-2.5 lg:px-6 lg:pb-3 lg:pt-3">
            <div class="admin-content-shell mx-auto">
            @if(session('success'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-4 rounded-xl border border-[var(--theme-primary-soft-strong)] bg-[var(--theme-primary-soft)] px-3.5 py-2.5 text-sm text-[var(--theme-primary-ink)]">
                    {{ session('warning') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
            </div>
        </main>
    </div>

    <div id="globalLoadingModal" class="global-loading-modal" aria-hidden="true">
        <div class="w-full max-w-xs rounded-[28px] bg-white px-6 py-7 text-center shadow-[0_24px_60px_rgba(15,23,42,0.22)]">
            <div class="mx-auto global-loading-spinner"></div>
            <p id="globalLoadingTitle" class="mt-5 text-base font-semibold text-slate-900">Processing request</p>
            <p id="globalLoadingMessage" class="mt-2 text-sm text-slate-500">Please wait while we complete your request.</p>
        </div>
    </div>

    <div id="globalConfirmModal" class="global-confirm-modal" aria-hidden="true">
        <div class="w-full max-w-md rounded-[28px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.22)]">
            <div class="flex items-start gap-4">
                <div class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[var(--theme-primary-soft)] text-[var(--theme-primary)]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3h.008v.008H12v-.008ZM10.29 3.86 1.82 18a2 2 0 0 0 1.72 3h16.92a2 2 0 0 0 1.72-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--theme-primary)]">Confirmation</p>
                    <h3 id="globalConfirmTitle" class="mt-2 text-xl font-semibold text-slate-900">Please confirm</h3>
                    <p id="globalConfirmMessage" class="mt-2 text-sm leading-6 text-slate-500">Are you sure you want to continue?</p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" id="globalConfirmCancel" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" id="globalConfirmProceed" class="rounded-2xl bg-[var(--theme-primary)] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[var(--theme-primary-dark)]">
                    Continue
                </button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const loadingModal = document.getElementById('globalLoadingModal');
            const loadingTitle = document.getElementById('globalLoadingTitle');
            const loadingMessage = document.getElementById('globalLoadingMessage');
            const confirmModal = document.getElementById('globalConfirmModal');
            const confirmTitle = document.getElementById('globalConfirmTitle');
            const confirmMessage = document.getElementById('globalConfirmMessage');
            const confirmCancel = document.getElementById('globalConfirmCancel');
            const confirmProceed = document.getElementById('globalConfirmProceed');
            let pendingAction = null;

            window.openGlobalLoadingModal = (message = 'Please wait while we complete your request.', title = 'Processing request') => {
                if (loadingTitle) loadingTitle.textContent = title;
                if (loadingMessage) loadingMessage.textContent = message;
                loadingModal?.classList.add('is-active');
                loadingModal?.setAttribute('aria-hidden', 'false');
            };

            window.closeGlobalLoadingModal = () => {
                loadingModal?.classList.remove('is-active');
                loadingModal?.setAttribute('aria-hidden', 'true');
            };

            const closeConfirmModal = () => {
                confirmModal?.classList.remove('is-active');
                confirmModal?.setAttribute('aria-hidden', 'true');
                pendingAction = null;
            };

            const openConfirmModal = ({ title, message, onConfirm, confirmLabel }) => {
                pendingAction = onConfirm;
                if (confirmTitle) confirmTitle.textContent = title || 'Please confirm';
                if (confirmMessage) confirmMessage.textContent = message || 'Are you sure you want to continue?';
                if (confirmProceed) confirmProceed.textContent = confirmLabel || 'Continue';
                confirmModal?.classList.add('is-active');
                confirmModal?.setAttribute('aria-hidden', 'false');
            };

            confirmCancel?.addEventListener('click', closeConfirmModal);
            confirmProceed?.addEventListener('click', () => {
                const action = pendingAction;
                closeConfirmModal();
                action?.();
            });

            document.querySelectorAll('form').forEach((form) => {
                const loadingMessageText = form.dataset.loadingMessage;
                const loadingTitleText = form.dataset.loadingTitle;
                const confirmMessageText = form.dataset.confirmMessage;
                const confirmTitleText = form.dataset.confirmTitle;
                const confirmLabelText = form.dataset.confirmLabel;

                form.addEventListener('submit', (event) => {
                    if (form.dataset.confirmed === 'true') {
                        form.dataset.confirmed = 'false';
                        if (loadingMessageText) {
                            window.openGlobalLoadingModal(loadingMessageText, loadingTitleText || 'Processing request');
                        }
                        return;
                    }

                    if (confirmMessageText) {
                        event.preventDefault();
                        openConfirmModal({
                            title: confirmTitleText,
                            message: confirmMessageText,
                            confirmLabel: confirmLabelText,
                            onConfirm: () => {
                                form.dataset.confirmed = 'true';
                                form.requestSubmit();
                            },
                        });
                        return;
                    }

                    if (loadingMessageText) {
                        window.openGlobalLoadingModal(loadingMessageText, loadingTitleText || 'Processing request');
                    }
                });
            });

            const headerDropdowns = Array.from(document.querySelectorAll('[data-header-dropdown]'));

            document.addEventListener('click', (event) => {
                headerDropdowns.forEach((dropdown) => {
                    if (!dropdown.hasAttribute('open')) {
                        return;
                    }

                    if (!dropdown.contains(event.target)) {
                        dropdown.removeAttribute('open');
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                headerDropdowns.forEach((dropdown) => {
                    dropdown.removeAttribute('open');
                });
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
