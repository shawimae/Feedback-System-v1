<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Feedback System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --apple-font: "SF Pro Text", "SF Pro Display", "Helvetica Neue", "Helvetica", "Arial", -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            --theme-primary: #4f81c7;
            --theme-primary-dark: #3d6aaa;
            --theme-primary-soft: #e8f0fb;
        }
        html, body {
            font-family: var(--apple-font);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            letter-spacing: -0.01em;
        }
        body { font-weight: 400; }
        h1, h2, h3 { letter-spacing: -0.03em; }
        input, button { font: inherit; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-100 via-[#eef4fc] to-[#dbe8f9] p-6">
    <div class="w-full max-w-5xl grid lg:grid-cols-2 rounded-[32px] overflow-hidden shadow-2xl bg-white border border-slate-200">
        
        <div class="hidden lg:flex flex-col justify-center bg-gradient-to-br from-[#274e82] via-[#4f81c7] to-[#7ca7df] text-white p-12">
            <div class="max-w-md">
                <p class="mb-4 text-sm uppercase tracking-[0.3em] text-blue-100/90">Feedback Platform</p>
                <h1 class="text-4xl font-semibold leading-tight mb-5">
                    Welcome back
                </h1>
                <p class="text-slate-200 text-base leading-7">
                    Sign in to manage your feedback system, stores, QR surveys, and customer insights.
                </p>
            </div>
        </div>

        <div class="p-8 md:p-12 flex items-center justify-center">
            <div class="w-full max-w-md">
                <div class="mb-8">
                    <h2 class="text-3xl font-semibold text-slate-800">Login</h2>
                    <p class="text-slate-500 mt-2">Sign in to your account.</p>
                </div>

                @if(session('success'))
                    <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-700 text-sm">
                        {{ session('warning') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Enter your email"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-[#4f81c7] focus:ring-4 focus:ring-[#e8f0fb]"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                        <input
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-[#4f81c7] focus:ring-4 focus:ring-[#e8f0fb]"
                        >
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remember" class="rounded border-slate-300">
                            Remember me
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-2xl bg-[#4f81c7] py-3.5 font-medium text-white transition hover:bg-[#3d6aaa]"
                    >
                        Sign In
                    </button>
                </form>

                @if($canSelfRegister ?? false)
                    <p class="mt-6 text-center text-sm text-slate-600">
                        No account yet?
                        <a href="{{ route('register') }}" class="font-medium text-[#4f81c7] hover:text-[#3d6aaa]">
                            Create account
                        </a>
                    </p>
                @endif
            </div>
        </div>
    </div>

    @if(session('account_created'))
        <div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md rounded-[28px] bg-white shadow-2xl border border-slate-200 p-8 text-center animate-[fadeIn_.2s_ease-in-out]">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                    <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h3 class="text-2xl font-semibold text-slate-800">Account Successfully Created</h3>
                <p class="mt-3 text-slate-500 leading-7">
                    Your account has been created successfully. You may now log in to access the dashboard.
                </p>

                <button
                    onclick="closeSuccessModal()"
                    class="mt-6 inline-flex items-center justify-center rounded-2xl bg-[#4f81c7] px-5 py-3 font-medium text-white transition hover:bg-[#3d6aaa]"
                >
                    Go to Login
                </button>
            </div>
        </div>

        <script>
            function closeSuccessModal() {
                const modal = document.getElementById('successModal');
                if (modal) {
                    modal.style.display = 'none';
                }
            }
        </script>
    @endif

    @if(session('license_prompt'))
        @php
            $canRequestRenewal = (bool) session('license_can_request_renewal', false);
        @endphp
        <div id="licenseModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/65 p-4">
            <div class="w-full max-w-lg rounded-[28px] border border-slate-200 bg-white p-8 shadow-2xl">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#e8f0fb] text-[#4f81c7]">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3h.008v.008H12v-.008ZM10.29 3.86 1.82 18a2 2 0 0 0 1.72 3h16.92a2 2 0 0 0 1.72-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                    </svg>
                </div>

                <p class="text-center text-sm font-semibold uppercase tracking-[0.28em] text-[#4f81c7]">Subscribe</p>
                <h3 class="mt-3 text-center text-3xl font-semibold tracking-tight text-slate-900">
                    {{ session('license_reason') === 'expired' ? 'License Expired' : 'License Required' }}
                </h3>
                <p class="mt-4 text-center text-base leading-7 text-slate-600">
                    {{ $errors->first() ?: 'Your subscription needs attention before this system can be used.' }}
                </p>

                @if($canRequestRenewal)
                    <form action="{{ route('renewal-requests.store') }}" method="POST" class="mt-8 space-y-4">
                        @csrf

                        <div>
                            <label class="mb-2 block text-left text-sm font-medium text-slate-700">Account Email</label>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-[#4f81c7] focus:ring-4 focus:ring-[#e8f0fb]"
                                placeholder="Enter your account email"
                                required
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-left text-sm font-medium text-slate-700">Note for Dev (optional)</label>
                            <textarea
                                name="request_note"
                                rows="3"
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-[#4f81c7] focus:ring-4 focus:ring-[#e8f0fb]"
                                placeholder="Example: Please renew our subscription for next month."
                            >{{ old('request_note') }}</textarea>
                        </div>

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-2xl bg-[#4f81c7] px-5 py-3.5 font-medium text-white transition hover:bg-[#3d6aaa]"
                        >
                            Request Renewal
                        </button>

                        <button
                            type="button"
                            onclick="closeLicenseModal()"
                            class="inline-flex items-center justify-center rounded-2xl bg-[#4f81c7] px-5 py-3.5 font-medium text-white transition hover:bg-[#3d6aaa]"
                        >
                            Close
                        </button>
                    </form>
                @else
                    <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-6 text-slate-600">
                        Please contact your Super Admin to request a license renewal for this account.
                    </div>

                    <button
                        type="button"
                        onclick="closeLicenseModal()"
                        class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-[#4f81c7] px-5 py-3.5 font-medium text-white transition hover:bg-[#3d6aaa]"
                    >
                        Close
                    </button>
                @endif
            </div>
        </div>

        <script>
            function closeLicenseModal() {
                const modal = document.getElementById('licenseModal');
                if (modal) {
                    modal.style.display = 'none';
                }
            }
        </script>
    @endif
</body>
</html>
