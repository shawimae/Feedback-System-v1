<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Feedback System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --apple-font: "SF Pro Text", "SF Pro Display", "Helvetica Neue", "Helvetica", "Arial", -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
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
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-blue-50 to-indigo-100 flex items-center justify-center p-6">

    <div class="w-full max-w-5xl grid lg:grid-cols-2 rounded-[32px] overflow-hidden shadow-2xl bg-white border border-slate-200">

        <!-- LEFT PANEL -->
        <div class="hidden lg:flex flex-col justify-center bg-gradient-to-br from-blue-900 via-indigo-800 to-slate-900 text-white p-12">
            <div class="max-w-md">
                <p class="text-sm uppercase tracking-[0.3em] text-blue-200 mb-4">
                    Create Account
                </p>
                <h1 class="text-4xl font-semibold leading-tight mb-5">
                    Start building your feedback system
                </h1>
                <p class="text-slate-200 text-base leading-7">
                    Create your account to manage stores, generate QR codes, collect surveys, and gain insights from your customers.
                </p>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="p-8 md:p-12 flex items-center justify-center">
            <div class="w-full max-w-md">

                <!-- HEADER -->
                <div class="mb-8">
                    <h2 class="text-3xl font-semibold text-slate-800">Register</h2>
                    <p class="text-slate-500 mt-2">Create your account.</p>
                </div>

                <!-- ERROR -->
                @if($errors->any())
                    <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- FORM -->
                <form action="{{ route('register.post') }}" method="POST" class="space-y-5">
                    @csrf

                    <!-- NAME -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Name
                        </label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Enter your name"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <!-- EMAIL -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Email
                        </label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Enter your email"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <!-- PHONE -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Phone
                        </label>
                        <input
                            type="text"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="Enter your phone number"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <!-- PASSWORD -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Password
                        </label>
                        <input
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <!-- CONFIRM PASSWORD -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Confirm Password
                        </label>
                        <input
                            type="password"
                            name="password_confirmation"
                            placeholder="Confirm your password"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <!-- BUTTON -->
                    <button
                        type="submit"
                        class="w-full rounded-2xl bg-slate-900 text-white py-3.5 font-medium hover:bg-slate-800 transition"
                    >
                        Create Account
                    </button>
                </form>

                <!-- LOGIN LINK -->
                <p class="mt-6 text-center text-sm text-slate-600">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-700">
                        Sign in
                    </a>
                </p>

            </div>
        </div>
    </div>

</body>
</html>
