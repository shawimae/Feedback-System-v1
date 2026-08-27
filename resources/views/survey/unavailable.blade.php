<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Unavailable</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-rose-50 to-orange-50 p-4 md:p-8">
    <div class="mx-auto flex min-h-[calc(100vh-2rem)] max-w-3xl items-center justify-center">
        <div class="w-full rounded-[32px] border border-white/80 bg-white/95 p-8 text-center shadow-[0_30px_80px_rgba(15,23,42,0.14)] backdrop-blur md:p-12">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3h.008v.008H12v-.008ZM10.29 3.86 1.82 18a2 2 0 0 0 1.72 3h16.92a2 2 0 0 0 1.72-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                </svg>
            </div>

            <p class="mt-6 text-xs font-semibold uppercase tracking-[0.3em] text-rose-500">Subscription Required</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 md:text-4xl">Survey temporarily unavailable</h1>
            <p class="mx-auto mt-4 max-w-xl text-sm leading-7 text-slate-600 md:text-base">
                {{ $message ?? 'This survey is currently unavailable because the client subscription is inactive or expired.' }}
            </p>

            @if(!empty($store?->name))
                <div class="mt-8 inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-600">
                    Branch: {{ $store->name }}
                </div>
            @endif

            <p class="mt-6 text-sm text-slate-500">
                Please contact the restaurant administrator to renew or reactivate the subscription.
            </p>
        </div>
    </div>
</body>
</html>
