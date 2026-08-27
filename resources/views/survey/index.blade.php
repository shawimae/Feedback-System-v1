<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $questionnaireTitle ?? 'Feedback Survey' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --apple-font: "SF Pro Text", "SF Pro Display", "Helvetica Neue", "Helvetica", "Arial", -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
        }
        body {
            font-family: var(--apple-font);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            letter-spacing: -0.01em;
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.12), transparent 28%),
                radial-gradient(circle at bottom right, rgba(34, 197, 94, 0.10), transparent 26%),
                linear-gradient(180deg, #eef4ff 0%, #f8fafc 48%, #eef8f2 100%);
        }
        h1, h2, h3 {
            letter-spacing: -0.03em;
        }
        .glass-shell {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(18px);
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.14);
        }
        .intro-panel {
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(248,250,252,0.92));
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
        }
        .intro-fade {
            transition: opacity .38s ease, transform .38s cubic-bezier(.22, 1, .36, 1), max-height .38s ease, margin .38s ease, padding .38s ease;
            max-height: 520px;
            opacity: 1;
            transform: translateY(0);
            overflow: hidden;
        }
        .intro-fade.is-hidden {
            max-height: 0;
            opacity: 0;
            transform: translateY(-18px);
            margin: 0;
            padding-top: 0;
            padding-bottom: 0;
            border-width: 0;
        }
        .survey-shell {
            display: none;
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .42s ease, transform .42s cubic-bezier(.22, 1, .36, 1);
        }
        .survey-shell.is-active {
            display: block;
        }
        .survey-shell.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        .intro-star {
            color: #f59e0b;
            filter: drop-shadow(0 6px 14px rgba(245, 158, 11, 0.16));
            animation: floatSoft 4.6s ease-in-out infinite;
        }
        .intro-star:nth-child(2) { animation-delay: .08s; }
        .intro-star:nth-child(3) { animation-delay: .16s; }
        .intro-star:nth-child(4) { animation-delay: .24s; }
        .intro-star:nth-child(5) { animation-delay: .32s; }
        .question-step {
            display: none;
            opacity: 0;
            transform: translateX(32px) translateY(10px) scale(0.985);
            transition: opacity .42s ease, transform .42s cubic-bezier(.22, 1, .36, 1);
        }
        .question-step.is-active {
            display: block;
        }
        .question-step.is-visible {
            opacity: 1;
            transform: translateX(0) translateY(0) scale(1);
        }
        .question-row {
            animation: floatIn .45s ease both;
        }
        .rating-star {
            transition: transform .2s ease, color .2s ease, filter .2s ease;
        }
        .rating-option:hover .rating-star {
            transform: translateY(-2px);
        }
        .rating-option input:checked ~ .rating-star,
        .rating-option.is-active .rating-star {
            color: #f59e0b;
            filter: drop-shadow(0 6px 14px rgba(245, 158, 11, 0.2));
        }
        .field-input {
            border: 1px solid rgba(148, 163, 184, 0.3);
            background: rgba(255, 255, 255, 0.92);
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }
        .field-input:focus {
            outline: none;
            border-color: rgba(37, 99, 235, 0.42);
            box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.10);
            transform: translateY(-1px);
        }
        .progress-fill {
            transition: width .42s cubic-bezier(.22, 1, .36, 1);
        }
        .comment-panel[hidden] {
            display: none !important;
        }
        .nav-btn[disabled] {
            opacity: .45;
            pointer-events: none;
        }
        .step-dot.is-active {
            background: #0f172a;
            transform: scale(1.1);
        }
        .step-dot.is-done {
            background: #22c55e;
        }
        .survey-loading-modal {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.52);
            backdrop-filter: blur(6px);
        }
        .survey-loading-modal.is-active {
            display: flex;
        }
        .survey-loading-spinner {
            height: 54px;
            width: 54px;
            border-radius: 9999px;
            border: 4px solid rgba(255, 255, 255, 0.25);
            border-top-color: #ffffff;
            animation: spin .9s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @keyframes floatIn {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes drift {
            0%, 100% { transform: translate3d(0, 0, 0); }
            50% { transform: translate3d(0, -10px, 0); }
        }
        @keyframes floatSoft {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8">
    @php
        $questions = collect($questions ?? []);
        $questionChunks = $questions->chunk(5)->values();
        $staffOptions = collect($staffOptions ?? []);
        $managerOptions = collect($managerOptions ?? []);
        if ($questionChunks->isEmpty()) {
            $questionChunks = collect([collect()]);
        }
        $stepCount = $questionChunks->count();
    @endphp

    <div class="mx-auto w-full max-w-[1120px]">
        <div class="glass-shell overflow-hidden rounded-[34px] border border-white/70">
            <div class="px-5 py-6 md:px-10 md:py-9">
                <section id="surveyIntro" class="intro-panel intro-fade rounded-[30px] border border-slate-200/80 px-6 py-10 text-center md:px-10 md:py-12">
                    <div class="mx-auto flex h-24 w-full max-w-[220px] items-center justify-center overflow-hidden rounded-[28px] border border-slate-200 bg-white px-4 shadow-[0_16px_36px_-24px_rgba(15,23,42,0.24)]">
                        @if($store->profile_photo_url)
                            <img src="{{ $store->profile_photo_url }}" alt="{{ $store->name }} logo" class="h-full w-full bg-white py-2 object-contain">
                        @else
                            <img src="{{ asset('assets/img/logo.png.png') }}" alt="System Logo" class="h-14 w-14 object-contain">
                        @endif
                    </div>

                    <div class="mt-7 flex justify-center gap-1.5">
                        @for($i = 0; $i < 5; $i++)
                            <span class="intro-star text-[34px]">&#9733;</span>
                        @endfor
                    </div>

                    <h1 class="mt-5 text-[30px] font-semibold tracking-tight text-slate-900 md:text-[34px]">
                        {{ $store->name }}
                    </h1>

                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-500 md:text-[15px]">
                        Enter your transaction number first before opening the feedback survey.
                    </p>

                    <div class="mx-auto mt-6 max-w-md text-left">
                        <label for="introTransactionNumber" class="mb-2 block text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">Transaction number</label>
                        <input
                            type="text"
                            id="introTransactionNumber"
                            value="{{ old('transaction_number') }}"
                            class="field-input w-full rounded-[20px] px-4 py-3 text-sm text-slate-800"
                            placeholder="Enter 10-digit transaction number"
                            inputmode="numeric"
                            maxlength="10"
                        >
                        <p id="introTransactionError" class="mt-2 hidden text-xs font-medium text-rose-500">Transaction number must be exactly 10 digits.</p>
                    </div>

                    <button type="button" id="startSurveyBtn" class="mt-7 inline-flex items-center justify-center rounded-[20px] bg-slate-950 px-8 py-3.5 text-[15px] font-medium text-white transition hover:bg-slate-800">
                        Rate us
                    </button>
                    <p id="introTransactionLoading" class="mt-3 hidden text-xs font-medium text-slate-500">Checking transaction number...</p>
                </section>

                <main id="surveyShell" class="survey-shell mt-6">
                    @if(session('success'))
                        <div class="mb-5 rounded-[24px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-5 rounded-[24px] border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ route('survey.submit', $store->slug) }}" method="POST" enctype="multipart/form-data" id="surveyForm" class="space-y-8">
                        @csrf
                        <input type="hidden" name="transaction_number" id="surveyTransactionNumber" value="{{ old('transaction_number') }}">

                        <div class="rounded-[24px] border border-slate-200/80 bg-white/72 px-4 py-4 shadow-[0_16px_44px_-32px_rgba(15,23,42,0.25)]">
                            <div class="flex items-center justify-between gap-4 text-[11px] uppercase tracking-[0.18em] text-slate-400">
                                <span>Progress</span>
                                <span id="progressLabel">Step 1 of {{ $stepCount }}</span>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-slate-200/70">
                                <div id="progressFill" class="progress-fill h-2 rounded-full bg-gradient-to-r from-sky-400 via-cyan-300 to-emerald-300" style="width: {{ 100 / $stepCount }}%;"></div>
                            </div>
                            <div id="stepDots" class="mt-4 flex flex-wrap gap-2">
                                @for($i = 0; $i < $stepCount; $i++)
                                    <span class="step-dot {{ $i === 0 ? 'is-active' : '' }} h-2.5 w-8 rounded-full bg-slate-200 transition"></span>
                                @endfor
                            </div>
                        </div>

                        <div class="border-b border-slate-200/80 pb-7">
                            <div class="mb-5 flex items-center justify-between gap-4">
                                <h2 class="text-[19px] font-semibold tracking-tight text-slate-900">Your details</h2>
                                <div class="rounded-full bg-sky-50 px-3 py-1 text-[11px] font-medium text-sky-700" id="transactionNumberBadge">
                                    Transaction #: {{ old('transaction_number') ?: 'Not set' }}
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="Name" class="field-input w-full rounded-[20px] px-4 py-3 text-sm text-slate-800">
                                </div>
                                <div>
                                    <input type="email" name="customer_email" value="{{ old('customer_email') }}" placeholder="Email" class="field-input w-full rounded-[20px] px-4 py-3 text-sm text-slate-800">
                                </div>
                                <div class="md:col-span-2">
                                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="Phone number" class="field-input w-full rounded-[20px] px-4 py-3 text-sm text-slate-800">
                                </div>
                            </div>
                        </div>

                        <div class="relative overflow-hidden pt-1">
                            @foreach($questionChunks as $stepIndex => $questionChunk)
                                <section class="question-step {{ $stepIndex === 0 ? 'is-active is-visible' : '' }}" data-step="{{ $stepIndex }}">
                                    <div class="mb-6 flex items-end justify-between gap-4">
                                        <div>
                                            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-slate-400">Step {{ $stepIndex + 1 }}</p>
                                            <h3 class="mt-1 text-[26px] font-semibold tracking-tight text-slate-900">
                                                {{ $stepIndex === $stepCount - 1 ? 'Final step' : 'Feedback' }}
                                            </h3>
                                        </div>
                                        <div class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-medium text-slate-500">
                                            {{ min(($stepIndex * 5) + 1, $questions->count()) }}-{{ min((($stepIndex + 1) * 5), $questions->count()) }} of {{ $questions->count() }}
                                        </div>
                                    </div>

                                    <div class="mx-auto max-w-[760px] space-y-6">
                                        @foreach($questionChunk as $question)
                                            <article class="question-row border-b border-slate-200/80 pb-6" data-required="{{ $question->is_required ? 'true' : 'false' }}" data-comment-required="{{ !empty($question->allow_comment) ? 'true' : 'false' }}" data-attachment-required="{{ !empty($question->allow_attachment) ? 'true' : 'false' }}" data-question-type="{{ $question->type }}">
                                                <div class="flex items-start justify-between gap-3">
                                                    <p class="max-w-2xl text-[16px] font-normal leading-8 text-slate-800">
                                                        {{ $question->question }}
                                                    </p>
                                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                                        @if(in_array(($question->applies_to ?? 'overall_service'), ['overall_service', 'staff', 'manager'], true))
                                                            <span class="rounded-full bg-sky-50 px-2.5 py-1 text-[10px] font-medium uppercase tracking-[0.16em] text-sky-700">
                                                                {{ match ($question->applies_to ?? 'overall_service') {
                                                                    'staff' => 'Staff',
                                                                    'manager' => 'Manager',
                                                                    default => 'Overall/Service',
                                                                } }}
                                                            </span>
                                                        @endif
                                                        @if($question->is_required)
                                                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-medium uppercase tracking-[0.16em] text-amber-700">Req</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if(in_array(($question->applies_to ?? 'overall_service'), ['staff', 'manager'], true))
                                                    @php
                                                        $pickerOptions = ($question->applies_to ?? 'overall_service') === 'manager' ? $managerOptions : $staffOptions;
                                                        $pickerLabel = ($question->applies_to ?? 'overall_service') === 'manager' ? 'manager' : 'staff';
                                                        $oldMention = old('question_target_' . $question->question_id);
                                                    @endphp
                                                    <div
                                                        class="mt-4 rounded-[22px] border border-slate-200 bg-slate-50/70 p-4"
                                                        data-mention-picker
                                                        data-question-id="{{ $question->question_id }}"
                                                        data-role="{{ $pickerLabel }}"
                                                    >
                                                        <label class="mb-2 block text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">
                                                            Select {{ $pickerLabel }}
                                                        </label>
                                                        <select
                                                            class="field-input w-full rounded-[20px] px-4 py-3 text-sm text-slate-800"
                                                            data-mention-select
                                                        >
                                                            <option value="">Choose {{ $pickerLabel }}</option>
                                                            @foreach($pickerOptions as $person)
                                                                <option
                                                                    value="{{ $person->name }}"
                                                                    data-name="{{ $person->name }}"
                                                                    data-photo="{{ $person->profile_photo_path ? asset('storage/' . $person->profile_photo_path) : '' }}"
                                                                    data-role="{{ $person->role ?: ucfirst($pickerLabel) }}"
                                                                    {{ $oldMention === $person->name ? 'selected' : '' }}
                                                                >
                                                                    {{ $person->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>

                                                        <input
                                                            type="hidden"
                                                            name="question_target_{{ $question->question_id }}"
                                                            value="{{ $oldMention }}"
                                                            data-mention-confirmed
                                                        >

                                                        <p class="mt-2 text-xs text-slate-500">
                                                            Pick a {{ $pickerLabel }}, preview the profile picture, then confirm the selection.
                                                        </p>

                                                        <p
                                                            class="{{ $errors->has('question_target_' . $question->question_id) ? '' : 'hidden' }} mt-2 text-xs font-medium text-rose-500"
                                                            data-mention-error
                                                        >
                                                            {{ $errors->first('question_target_' . $question->question_id) ?: 'Please confirm your selected ' . $pickerLabel . '.' }}
                                                        </p>

                                                        <div class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_240px] md:items-start">
                                                            <div class="flex flex-wrap items-center gap-3">
                                                                <button
                                                                    type="button"
                                                                    class="inline-flex items-center justify-center rounded-[18px] bg-slate-950 px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800"
                                                                    data-mention-confirm-btn
                                                                >
                                                                    Confirm {{ ucfirst($pickerLabel) }}
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    class="inline-flex items-center justify-center rounded-[18px] border border-slate-300 bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                                                    data-mention-clear-btn
                                                                >
                                                                    Clear
                                                                </button>
                                                                <span
                                                                    class="{{ $oldMention ? '' : 'hidden' }} rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-medium text-emerald-700"
                                                                    data-mention-badge
                                                                >
                                                                    Confirmed: <span data-mention-badge-name>{{ $oldMention ?: ucfirst($pickerLabel) }}</span>
                                                                </span>
                                                            </div>

                                                            <div class="{{ $oldMention ? '' : 'hidden' }} rounded-[20px] border border-slate-200 bg-white p-3" data-mention-preview>
                                                                <div class="flex items-center gap-3">
                                                                    <img
                                                                        src="{{ 'https://placehold.co/160x160/e2e8f0/64748b?text=No+Photo' }}"
                                                                        alt="Selected {{ $pickerLabel }} profile"
                                                                        class="h-16 w-16 rounded-[18px] border border-slate-100 object-cover shadow-sm"
                                                                        data-mention-image
                                                                    >
                                                                    <div class="min-w-0">
                                                                        <p class="text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">Preview</p>
                                                                        <p class="mt-1 truncate text-sm font-semibold text-slate-900" data-mention-name>{{ $oldMention ?: 'No selection yet' }}</p>
                                                                        <p class="mt-1 text-xs text-slate-500" data-mention-meta>{{ $oldMention ? 'Confirmed selection' : 'Choose a name first' }}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($question->type === 'rating')
                                                    <div class="mt-4 flex flex-wrap gap-2">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            <label class="rating-option cursor-pointer">
                                                                <input
                                                                    type="radio"
                                                                    name="question_{{ $question->question_id }}"
                                                                    value="{{ $i }}"
                                                                    class="sr-only"
                                                                    {{ old('question_' . $question->question_id) == $i ? 'checked' : '' }}
                                                                >
                                                                <span class="rating-star inline-flex h-11 w-11 items-center justify-center rounded-[16px] border border-slate-200 bg-white text-2xl text-slate-300 transition">&#9733;</span>
                                                            </label>
                                                        @endfor
                                                    </div>
                                                @elseif($question->type === 'multiple_choice')
                                                    <div class="mt-4 space-y-3">
                                                        @foreach(($question->options ?? []) as $option)
                                                            <label class="flex cursor-pointer items-start gap-3 rounded-[18px] border border-slate-200 bg-white px-4 py-3 transition hover:border-slate-300 hover:bg-slate-50">
                                                                <input
                                                                    type="radio"
                                                                    name="question_{{ $question->question_id }}"
                                                                    value="{{ $option }}"
                                                                    class="mt-1 h-4 w-4 border-slate-300 text-slate-900 focus:ring-slate-300"
                                                                    {{ old('question_' . $question->question_id) === $option ? 'checked' : '' }}
                                                                >
                                                                <span class="text-sm leading-6 text-slate-700">{{ $option }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @elseif($question->type === 'text')
                                                    <input
                                                        type="text"
                                                        name="question_{{ $question->question_id }}"
                                                        value="{{ old('question_' . $question->question_id) }}"
                                                        data-primary-answer="true"
                                                        class="field-input mt-4 w-full rounded-[20px] px-4 py-3 text-sm text-slate-800"
                                                        placeholder="Type your answer"
                                                    >
                                                @elseif($question->type === 'textarea')
                                                    <textarea
                                                        name="question_{{ $question->question_id }}"
                                                        rows="4"
                                                        data-primary-answer="true"
                                                        class="field-input mt-4 w-full rounded-[20px] px-4 py-3 text-sm text-slate-800"
                                                        placeholder="Share more details">{{ old('question_' . $question->question_id) }}</textarea>
                                                @endif

                                                @if(!empty($question->allow_comment))
                                                    <div class="mt-4">
                                                        <label class="mb-2 block text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">Required comment</label>
                                                        <textarea
                                                            name="question_comment_{{ $question->question_id }}"
                                                            rows="3"
                                                            data-comment-field="true"
                                                            class="field-input w-full rounded-[20px] px-4 py-3 text-sm text-slate-800"
                                                            placeholder="Please add your comment here">{{ old('question_comment_' . $question->question_id) }}</textarea>
                                                    </div>
                                                @endif

                                                @if(!empty($question->allow_attachment))
                                                    <div class="mt-4">
                                                        <button
                                                            type="button"
                                                            data-photo-toggle
                                                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                                                        >
                                                            <span class="text-sm leading-none">+</span>
                                                            <span>Add photo</span>
                                                        </button>
                                                        <div class="comment-panel mt-3" data-photo-panel {{ $errors->has('question_photo_' . $question->question_id) ? '' : 'hidden' }}>
                                                            <label class="mb-2 block text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">Required photo</label>
                                                            <input
                                                                type="file"
                                                                name="question_photo_{{ $question->question_id }}"
                                                                accept="image/*"
                                                                data-photo-answer="true"
                                                                class="block w-full text-sm text-slate-700 file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-slate-800"
                                                            >
                                                            <p class="mt-2 text-xs text-slate-500">Please attach a photo for this question.</p>
                                                        </div>
                                                    </div>
                                                @endif
                                            </article>
                                        @endforeach

                                        @if($stepIndex === $stepCount - 1)
                                            <article class="question-row border-b border-slate-200/80 pb-6">
                                                <p class="text-[17px] font-medium leading-8 text-slate-800">Anything else?</p>
                                                <textarea
                                                    name="overall_comment"
                                                    rows="4"
                                                    class="field-input mt-4 w-full rounded-[20px] px-4 py-3 text-sm text-slate-800"
                                                    placeholder="Anything else you'd like us to know?">{{ old('overall_comment') }}</textarea>
                                            </article>
                                        @endif
                                    </div>
                                </section>
                            @endforeach

                            <div class="mt-8 flex flex-col gap-3 border-t border-slate-200/80 pt-6 sm:flex-row sm:items-center sm:justify-between">
                                <button type="button" id="prevStepBtn" class="nav-btn inline-flex items-center justify-center rounded-[18px] border border-slate-300 bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                    Previous
                                </button>

                                <div class="text-center text-[11px] uppercase tracking-[0.18em] text-slate-400">
                                    Smooth flow
                                </div>

                                <button type="button" id="nextStepBtn" class="nav-btn inline-flex items-center justify-center rounded-[18px] bg-slate-950 px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">
                                    Next
                                </button>

                                <button type="submit" id="submitBtn" class="hidden inline-flex items-center justify-center rounded-[18px] bg-emerald-500 px-5 py-3 text-sm font-medium text-white transition hover:bg-emerald-600">
                                    Submit
                                </button>
                            </div>
                        </div>
                    </form>
                </main>
            </div>
        </div>
    </div>

    <div id="surveyLoadingModal" class="survey-loading-modal" aria-hidden="true">
        <div class="w-full max-w-xs rounded-[28px] bg-white px-6 py-7 text-center shadow-[0_24px_60px_rgba(15,23,42,0.22)]">
            <div class="mx-auto survey-loading-spinner"></div>
            <p class="mt-5 text-base font-semibold text-slate-900">Checking transaction number</p>
            <p class="mt-2 text-sm text-slate-500">Please wait while we verify your 10-digit transaction number.</p>
        </div>
    </div>

    <script>
        const steps = Array.from(document.querySelectorAll('.question-step'));
        const surveyForm = document.getElementById('surveyForm');
        const prevStepBtn = document.getElementById('prevStepBtn');
        const nextStepBtn = document.getElementById('nextStepBtn');
        const submitBtn = document.getElementById('submitBtn');
        const progressLabel = document.getElementById('progressLabel');
        const progressFill = document.getElementById('progressFill');
        const stepDots = Array.from(document.querySelectorAll('.step-dot'));
        const startSurveyBtn = document.getElementById('startSurveyBtn');
        const surveyIntro = document.getElementById('surveyIntro');
        const surveyShell = document.getElementById('surveyShell');
        const introTransactionNumber = document.getElementById('introTransactionNumber');
        const introTransactionError = document.getElementById('introTransactionError');
        const introTransactionLoading = document.getElementById('introTransactionLoading');
        const surveyLoadingModal = document.getElementById('surveyLoadingModal');
        const surveyTransactionNumber = document.getElementById('surveyTransactionNumber');
        const transactionNumberBadge = document.getElementById('transactionNumberBadge');
        const transactionCheckUrl = @json(route('survey.transaction-check', $store->slug));
        const defaultStaffPreviewImage = 'https://placehold.co/160x160/e2e8f0/64748b?text=No+Photo';
        let currentStep = 0;

        function toggleLoadingModal(show, title = 'Checking transaction number', message = 'Please wait while we verify your 10-digit transaction number.') {
            surveyLoadingModal?.classList.toggle('is-active', show);
            surveyLoadingModal?.setAttribute('aria-hidden', show ? 'false' : 'true');
            const modalTitle = surveyLoadingModal?.querySelector('p.text-base');
            const modalMessage = surveyLoadingModal?.querySelector('p.text-sm');

            if (modalTitle) modalTitle.textContent = title;
            if (modalMessage) modalMessage.textContent = message;
        }

        function getMentionPickerParts(picker) {
            return {
                select: picker.querySelector('[data-mention-select]'),
                confirmedInput: picker.querySelector('[data-mention-confirmed]'),
                preview: picker.querySelector('[data-mention-preview]'),
                image: picker.querySelector('[data-mention-image]'),
                name: picker.querySelector('[data-mention-name]'),
                meta: picker.querySelector('[data-mention-meta]'),
                badge: picker.querySelector('[data-mention-badge]'),
                badgeName: picker.querySelector('[data-mention-badge-name]'),
                error: picker.querySelector('[data-mention-error]'),
            };
        }

        function getSelectedMentionOption(select) {
            if (!select) return null;

            const selectedOption = select.options[select.selectedIndex];

            if (!selectedOption || selectedOption.value === '') {
                return null;
            }

            return selectedOption;
        }

        function syncMentionPickerPreview(picker) {
            const { select, confirmedInput, preview, image, name, meta } = getMentionPickerParts(picker);
            const selectedOption = getSelectedMentionOption(select);

            if (!selectedOption) {
                preview?.classList.add('hidden');
                if (image) image.src = defaultStaffPreviewImage;
                if (name) name.textContent = 'No selection yet';
                if (meta) meta.textContent = 'Choose a name first';
                return;
            }

            preview?.classList.remove('hidden');
            if (image) image.src = selectedOption.dataset.photo || defaultStaffPreviewImage;
            if (name) name.textContent = selectedOption.dataset.name || selectedOption.textContent.trim();
            if (meta) {
                meta.textContent = confirmedInput?.value === selectedOption.value
                    ? 'Confirmed selection'
                    : 'Click confirm to save this selection';
            }
        }

        function clearMentionConfirmation(picker, resetSelect = false) {
            const { select, confirmedInput, badge, badgeName, error } = getMentionPickerParts(picker);

            if (confirmedInput) {
                confirmedInput.value = '';
            }

            badge?.classList.add('hidden');
            if (badgeName) {
                badgeName.textContent = picker.dataset.role || 'Selection';
            }

            if (resetSelect && select) {
                select.value = '';
            }

            error?.classList.add('hidden');
            syncMentionPickerPreview(picker);
        }

        function confirmMentionPicker(picker) {
            const { select, confirmedInput, badge, badgeName, error } = getMentionPickerParts(picker);
            const selectedOption = getSelectedMentionOption(select);

            if (!selectedOption) {
                error?.classList.remove('hidden');
                picker.scrollIntoView({ behavior: 'smooth', block: 'center' });
                picker.classList.add('ring-2', 'ring-rose-200');
                setTimeout(() => picker.classList.remove('ring-2', 'ring-rose-200'), 1400);
                return false;
            }

            if (confirmedInput) {
                confirmedInput.value = selectedOption.value;
            }

            if (badgeName) {
                badgeName.textContent = selectedOption.dataset.name || selectedOption.textContent.trim();
            }

            badge?.classList.remove('hidden');
            error?.classList.add('hidden');
            syncMentionPickerPreview(picker);
            return true;
        }

        function validateMentionPickers(index) {
            const step = steps[index];
            if (!step) return true;

            const mentionPickers = step.querySelectorAll('[data-mention-picker]');

            for (const picker of mentionPickers) {
                const { confirmedInput, error } = getMentionPickerParts(picker);

                if (confirmedInput?.value?.trim()) {
                    error?.classList.add('hidden');
                    continue;
                }

                error?.classList.remove('hidden');
                picker.scrollIntoView({ behavior: 'smooth', block: 'center' });
                picker.classList.add('ring-2', 'ring-rose-200');
                setTimeout(() => picker.classList.remove('ring-2', 'ring-rose-200'), 1400);
                return false;
            }

            return true;
        }

        async function openSurvey() {
            if (!surveyShell || !surveyIntro || !introTransactionNumber || !surveyTransactionNumber) return;

            const transactionNumber = introTransactionNumber.value.replace(/\D/g, '').slice(0, 10);

            introTransactionNumber.value = transactionNumber;

            if (!/^\d{10}$/.test(transactionNumber)) {
                introTransactionError?.classList.remove('hidden');
                if (introTransactionError) {
                    introTransactionError.textContent = 'Transaction number must be exactly 10 digits.';
                }
                introTransactionNumber.focus();
                introTransactionNumber.classList.add('ring-2', 'ring-rose-200');
                setTimeout(() => introTransactionNumber.classList.remove('ring-2', 'ring-rose-200'), 1400);
                return;
            }

            startSurveyBtn?.setAttribute('disabled', 'disabled');
            startSurveyBtn?.classList.add('opacity-70');
            introTransactionLoading?.classList.remove('hidden');
            toggleLoadingModal(true);

            try {
                const response = await fetch(transactionCheckUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: JSON.stringify({
                        transaction_number: transactionNumber,
                    }),
                });

                const payload = await response.json();

                if (!response.ok || !payload.available) {
                    if (introTransactionError) {
                        introTransactionError.textContent = payload.message || 'This transaction number has already been used.';
                        introTransactionError.classList.remove('hidden');
                    }
                    introTransactionNumber.focus();
                    introTransactionNumber.classList.add('ring-2', 'ring-rose-200');
                    setTimeout(() => introTransactionNumber.classList.remove('ring-2', 'ring-rose-200'), 1400);
                    return;
                }
            } catch (error) {
                if (introTransactionError) {
                    introTransactionError.textContent = 'Unable to validate transaction number right now. Please try again.';
                    introTransactionError.classList.remove('hidden');
                }
                return;
            } finally {
                startSurveyBtn?.removeAttribute('disabled');
                startSurveyBtn?.classList.remove('opacity-70');
                introTransactionLoading?.classList.add('hidden');
                toggleLoadingModal(false);
            }

            introTransactionError?.classList.add('hidden');
            surveyTransactionNumber.value = transactionNumber;
            if (transactionNumberBadge) {
                transactionNumberBadge.textContent = `Transaction #: ${transactionNumber}`;
            }

            surveyIntro.classList.add('is-hidden');
            surveyShell.classList.add('is-active');

            requestAnimationFrame(() => {
                surveyShell.classList.add('is-visible');
                setTimeout(() => {
                    surveyShell.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 80);
            });
        }

        introTransactionNumber?.addEventListener('input', () => {
            introTransactionNumber.value = introTransactionNumber.value.replace(/\D/g, '').slice(0, 10);

            if (introTransactionNumber.value.trim() !== '') {
                introTransactionError?.classList.add('hidden');
            }
        });

        function revealStep(index) {
            steps.forEach((step, stepIndex) => {
                step.classList.remove('is-active', 'is-visible');
                if (stepIndex === index) {
                    step.classList.add('is-active');
                    requestAnimationFrame(() => step.classList.add('is-visible'));
                }
            });

            currentStep = index;
            const totalSteps = steps.length;
            progressLabel.textContent = `Step ${index + 1} of ${totalSteps}`;
            progressFill.style.width = `${((index + 1) / totalSteps) * 100}%`;

            stepDots.forEach((dot, dotIndex) => {
                dot.classList.toggle('is-active', dotIndex === index);
                dot.classList.toggle('is-done', dotIndex < index);
            });

            prevStepBtn.disabled = index === 0;
            nextStepBtn.classList.toggle('hidden', index === totalSteps - 1);
            submitBtn.classList.toggle('hidden', index !== totalSteps - 1);
        }

        function validateStep(index) {
            const step = steps[index];
            if (!step) return true;

            const requiredQuestions = step.querySelectorAll('[data-required="true"]');

            for (const question of requiredQuestions) {
                const questionType = question.dataset.questionType;
                let answerValid = false;

                if (questionType === 'rating' || questionType === 'multiple_choice') {
                    const selectedOption = question.querySelector('input[type="radio"]:checked');

                    if (selectedOption) {
                        answerValid = true;
                    }
                } else {
                    const primaryInput = question.querySelector('[data-primary-answer="true"]');

                    if (primaryInput && primaryInput.type === 'file' && primaryInput.files.length > 0) {
                        answerValid = true;
                    }

                    if (primaryInput && primaryInput.type !== 'file' && primaryInput.value.trim() !== '') {
                        answerValid = true;
                    }
                }

                if (!answerValid) {
                    question.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    question.classList.add('ring-2', 'ring-rose-200');
                    setTimeout(() => question.classList.remove('ring-2', 'ring-rose-200'), 1400);
                    return false;
                }
            }

            const commentQuestions = step.querySelectorAll('[data-comment-required="true"]');

            for (const question of commentQuestions) {
                const commentInput = question.querySelector('[data-comment-field="true"]');

                if (!commentInput || commentInput.value.trim() === '') {
                    question.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    question.classList.add('ring-2', 'ring-rose-200');
                    setTimeout(() => question.classList.remove('ring-2', 'ring-rose-200'), 1400);
                    return false;
                }
            }

            const attachmentQuestions = step.querySelectorAll('[data-attachment-required="true"]');

            for (const question of attachmentQuestions) {
                const photoInput = question.querySelector('[data-photo-answer="true"]');

                if (!photoInput || photoInput.files.length === 0) {
                    question.querySelector('[data-photo-panel]')?.removeAttribute('hidden');
                    const toggleLabel = question.querySelector('[data-photo-toggle] span:last-child');
                    if (toggleLabel) {
                        toggleLabel.textContent = 'Hide photo';
                    }
                    question.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    question.classList.add('ring-2', 'ring-rose-200');
                    setTimeout(() => question.classList.remove('ring-2', 'ring-rose-200'), 1400);
                    return false;
                }
            }

            return true;
        }

        prevStepBtn?.addEventListener('click', () => {
            if (currentStep > 0) revealStep(currentStep - 1);
        });

        nextStepBtn?.addEventListener('click', () => {
            if (!validateMentionPickers(currentStep)) return;
            if (!validateStep(currentStep)) return;
            if (currentStep < steps.length - 1) revealStep(currentStep + 1);
        });

        startSurveyBtn?.addEventListener('click', openSurvey);

        document.querySelectorAll('[data-mention-picker]').forEach((picker) => {
            const { select, confirmedInput, error } = getMentionPickerParts(picker);
            const confirmButton = picker.querySelector('[data-mention-confirm-btn]');
            const clearButton = picker.querySelector('[data-mention-clear-btn]');

            confirmButton?.addEventListener('click', () => {
                confirmMentionPicker(picker);
            });

            clearButton?.addEventListener('click', () => {
                clearMentionConfirmation(picker, true);
            });

            select?.addEventListener('change', () => {
                const selectionChanged = confirmedInput?.value && confirmedInput.value !== select.value;

                if (selectionChanged) {
                    clearMentionConfirmation(picker, false);
                } else {
                    syncMentionPickerPreview(picker);
                }

                if (select.value !== '') {
                    error?.classList.add('hidden');
                }
            });

            const selectedOption = getSelectedMentionOption(select);
            if (confirmedInput?.value && selectedOption && confirmedInput.value === selectedOption.value) {
                const image = picker.querySelector('[data-mention-image]');
                if (image) {
                    image.src = selectedOption.dataset.photo || defaultStaffPreviewImage;
                }
            }

            syncMentionPickerPreview(picker);
        });

        surveyForm?.addEventListener('submit', (event) => {
            if (!validateMentionPickers(currentStep)) {
                event.preventDefault();
                return;
            }

            if (!validateStep(currentStep)) {
                event.preventDefault();
                return;
            }

            toggleLoadingModal(true, 'Submitting feedback', 'Please wait while we save your feedback.');
        });

        document.querySelectorAll('.rating-option input').forEach((input) => {
            input.addEventListener('change', () => {
                const options = Array.from(input.closest('.flex').querySelectorAll('.rating-option'));
                options.forEach((option, optionIndex) => {
                    option.classList.toggle('is-active', optionIndex < Number(input.value));
                });
            });

            if (input.checked) {
                const options = Array.from(input.closest('.flex').querySelectorAll('.rating-option'));
                options.forEach((option, optionIndex) => {
                    option.classList.toggle('is-active', optionIndex < Number(input.value));
                });
            }
        });

        document.querySelectorAll('[data-photo-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const panel = button.parentElement?.querySelector('[data-photo-panel]');
                if (!panel) return;

                const isHidden = panel.hasAttribute('hidden');
                panel.toggleAttribute('hidden', !isHidden);
                button.querySelector('span:last-child').textContent = isHidden ? 'Hide photo' : 'Add photo';

                if (isHidden) {
                    panel.querySelector('input[type="file"]')?.focus();
                }
            });

            const panel = button.parentElement?.querySelector('[data-photo-panel]');
            if (panel && !panel.hasAttribute('hidden')) {
                button.querySelector('span:last-child').textContent = 'Hide photo';
            }
        });

        revealStep(0);

        @if($errors->any())
            openSurvey();
        @endif
    </script>
</body>
</html>
