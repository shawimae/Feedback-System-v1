@extends('layouts.admin')

@section('content')
    @php
        $requiredCount = $questions->where('is_required', true)->count();
        $activeCount = $questions->where('is_active', true)->count();
        $questionTitle = old('questionnaire_title', $questionnaireTitle ?? 'Customer Feedback');
        $publishedCount = collect($publishedQuestions ?? [])->count();
    @endphp

    <style>
        .question-scrollbar::-webkit-scrollbar {
            width: 8px;
        }

        .question-scrollbar::-webkit-scrollbar-thumb {
            background: #fdba74;
            border-radius: 9999px;
        }

        .question-editor-locked textarea,
        .question-editor-locked select,
        .question-editor-locked input[type="number"] {
            pointer-events: none;
            opacity: 0.8;
        }

        .question-editor-locked input[type="checkbox"] {
            pointer-events: none;
        }

        .choice-options-group.is-hidden {
            display: none;
        }

        .choice-option-row[data-template="true"] {
            display: none;
        }
    </style>

    <div class="space-y-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-[22px] font-semibold tracking-tight text-slate-950">Master Questionnaire</h2>
                <p class="mt-1 text-sm text-slate-500">Create a uniform questionnaire and publish it to all stores.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex items-center gap-2 rounded-xl border border-orange-100 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-600 shadow-sm">
                    <span class="text-orange-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M5.75 19.25h9.5A2.75 2.75 0 0 0 18 16.5V7.5a2.75 2.75 0 0 0-2.75-2.75h-9.5A2.75 2.75 0 0 0 3 7.5v9A2.75 2.75 0 0 0 5.75 19.25Z" />
                        </svg>
                    </span>
                    <span>{{ $questions->count() }} Questions</span>
                </div>

                <div class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm">
                    <span class="inline-flex h-2 w-2 rounded-full bg-white/80"></span>
                    <span>Active</span>
                </div>
            </div>
        </div>

        <div id="questionToast" class="pointer-events-none fixed bottom-5 right-5 z-50 hidden rounded-2xl border border-orange-100 bg-white px-4 py-3 text-sm text-slate-700 shadow-xl"></div>
        <div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4">
            <div class="w-full max-w-md rounded-[24px] border border-orange-100 bg-white p-5 shadow-2xl">
                <h3 id="confirmModalTitle" class="text-lg font-semibold text-slate-900">Confirm action</h3>
                <p id="confirmModalMessage" class="mt-2 text-sm leading-6 text-slate-500">Please confirm this action.</p>
                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeConfirmModal()" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="button" id="confirmModalButton" class="rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600">
                        Confirm
                    </button>
                </div>
            </div>
        </div>

        <div class="grid gap-3 xl:grid-cols-[500px_minmax(0,1fr)]">
            <aside class="space-y-3">
                <section class="rounded-[22px] border border-orange-100 bg-white p-4 shadow-[0_10px_26px_rgba(15,23,42,0.05)]">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-orange-50 text-orange-300/80">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5h6.75" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12.75 7.5h6.75" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 16.5h6.75" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12.75 16.5h6.75" />
                                <circle cx="12" cy="7.5" r="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="12" cy="16.5" r="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-[18px] font-semibold text-slate-900">Settings</h3>
                        </div>
                    </div>

                    <form action="{{ route('questions.settings.update') }}" method="POST" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-800">Questionnaire Title</label>
                            <input type="text" name="questionnaire_title" value="{{ $questionTitle }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-orange-300">
                        </div>

                        <label class="flex items-start gap-2">
                            <input type="checkbox" checked disabled class="mt-1 h-4 w-4 rounded border-slate-300 text-orange-500 focus:ring-orange-300">
                            <span>
                                <span class="block text-sm font-medium text-slate-800">Active</span>
                                <span class="block text-xs text-slate-500">Stores will accept responses when active</span>
                            </span>
                        </label>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" class="rounded-xl bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-600">
                                Save Changes
                            </button>
                            <span class="text-xs text-slate-500">Saved title is used across the questionnaire.</span>
                        </div>
                    </form>
                </section>

                <section class="rounded-[22px] border border-orange-100 bg-white p-4 shadow-[0_10px_26px_rgba(15,23,42,0.05)]">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-50 text-orange-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.55">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 11.25 14.25-6-4.5 13.5-3.5-4.25-6.25-3.25Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="m10.75 14.5 3.5 4.25" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-[18px] font-semibold text-slate-900">Publish to Stores</h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Public survey uses the last synced version only.
                                @if($lastSyncedAt)
                                    Last synced: {{ \Carbon\Carbon::parse($lastSyncedAt)->format('M d, Y h:i A') }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <p class="mt-4 text-sm text-slate-500">Draft changes stay in admin only. Added, edited, or deleted questions will appear on the public survey after you sync.</p>

                    <div class="mt-4 rounded-xl border border-orange-100 bg-orange-50 px-3 py-2 text-[11px] font-medium text-orange-700">
                        Live public questions: {{ $publishedCount }}
                    </div>

                    <form id="syncPublicQuestionsForm" action="{{ route('questions.sync-public') }}" method="POST">
                        @csrf
                        <button type="button" onclick="openConfirmModal({ title: 'Sync questionnaire to public survey?', message: 'This will publish the current draft questions and title to the public survey. Past answered responses will keep their old version.', confirmLabel: 'Publish / Sync Now', onConfirm: () => document.getElementById('syncPublicQuestionsForm').submit() })" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-3 text-sm font-semibold text-white transition hover:from-orange-600 hover:to-orange-700">
                            Publish / Sync Now
                        </button>
                    </form>
                </section>

                <section class="rounded-[22px] border border-orange-100 bg-white p-4 shadow-[0_10px_26px_rgba(15,23,42,0.05)]">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-50 text-orange-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.55">
                                <circle cx="12" cy="12" r="8.25" stroke-linecap="round" stroke-linejoin="round" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v7.5" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 12h-7.5" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-[18px] font-semibold text-slate-900">Add Question</h3>
                        </div>
                    </div>

                    <form action="{{ route('questions.store') }}" method="POST" class="mt-4 space-y-4" data-choice-builder-form="true">
                        @csrf

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-800">Question Text</label>
                            <textarea name="question" rows="3" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-orange-300" placeholder="Enter your question here...">{{ old('question') }}</textarea>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-800">Question Type</label>
                            <select name="type" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-orange-300">
                                <option value="rating" {{ old('type', 'rating') === 'rating' ? 'selected' : '' }}>Rating (1-5)</option>
                                <option value="multiple_choice" {{ old('type') === 'multiple_choice' ? 'selected' : '' }}>Multiple Choice</option>
                                <option value="text" {{ old('type') === 'text' ? 'selected' : '' }}>Text</option>
                                <option value="textarea" {{ old('type') === 'textarea' ? 'selected' : '' }}>Long Text</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-800">Question For</label>
                            <select name="applies_to" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-orange-300">
                                <option value="overall_service" {{ old('applies_to', 'overall_service') === 'overall_service' ? 'selected' : '' }}>Overall/Service</option>
                                <option value="staff" {{ old('applies_to') === 'staff' ? 'selected' : '' }}>Staff</option>
                                <option value="manager" {{ old('applies_to') === 'manager' ? 'selected' : '' }}>Manager</option>
                            </select>
                        </div>

                        <div class="choice-options-group {{ old('type') === 'multiple_choice' ? '' : 'is-hidden' }}">
                            <label class="mb-2 block text-sm font-medium text-slate-800">Choices</label>
                            @php
                                $oldChoices = old('choice_options', ['', '']);
                                if (!is_array($oldChoices)) {
                                    $oldChoices = ['', ''];
                                }
                                $oldChoices = array_values(array_pad($oldChoices, 2, ''));
                            @endphp
                            <div class="space-y-2" data-choice-list>
                                @foreach($oldChoices as $choiceIndex => $choiceValue)
                                    <div class="choice-option-row flex items-center gap-2" data-choice-row>
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-xs font-semibold text-orange-500">{{ $choiceIndex + 1 }}</span>
                                        <input type="text" name="choice_options[]" value="{{ $choiceValue }}" class="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-orange-300" placeholder="Choice {{ $choiceIndex + 1 }}">
                                        <button type="button" data-remove-choice class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-500" aria-label="Remove choice">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                                <div class="choice-option-row flex items-center gap-2" data-choice-row data-template="true">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-xs font-semibold text-orange-500" data-choice-number>1</span>
                                    <input type="text" name="choice_options[]" value="" class="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-orange-300" placeholder="Choice 1">
                                    <button type="button" data-remove-choice class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-500" aria-label="Remove choice">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button type="button" data-add-choice class="mt-3 inline-flex items-center gap-2 rounded-xl border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-600 transition hover:bg-orange-100">
                                <span class="text-base leading-none">+</span>
                                <span>Add choice</span>
                            </button>
                            <p class="mt-2 text-xs text-slate-500">Mas malinaw ito parang poll builder. Minimum 2 choices.</p>
                        </div>

                        <input type="hidden" name="sort_order" value="{{ old('sort_order', max($questions->count(), 1)) }}">

                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="is_required" value="1" {{ old('is_required') ? 'checked' : '' }} class="mt-1 h-4 w-4 rounded border-slate-300 text-orange-500 focus:ring-orange-300">
                            <span>
                                <span class="block text-sm font-medium text-slate-800">Required</span>
                                <span class="block text-xs text-slate-500">Users must answer this question</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="allow_comment" value="1" {{ old('allow_comment') ? 'checked' : '' }} class="mt-1 h-4 w-4 rounded border-slate-300 text-orange-500 focus:ring-orange-300">
                            <span>
                                <span class="block text-sm font-medium text-slate-800">Require comment field</span>
                                <span class="block text-xs text-slate-500">Show a required comment box for this question.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="allow_attachment" value="1" {{ old('allow_attachment') ? 'checked' : '' }} class="mt-1 h-4 w-4 rounded border-slate-300 text-orange-500 focus:ring-orange-300">
                            <span>
                                <span class="block text-sm font-medium text-slate-800">Add photo button</span>
                                <span class="block text-xs text-slate-500">Show a required photo attachment field for this question.</span>
                            </span>
                        </label>

                        <button class="w-full rounded-xl border border-orange-300 bg-white px-4 py-2.5 text-sm font-semibold text-orange-500 transition hover:bg-orange-50">
                            + Add Question
                        </button>

                        <p class="text-xs text-slate-500">Questions support rating, multiple choice, text, long text, plus optional comment and required photo add-ons.</p>
                    </form>
                </section>
            </aside>

            <section class="rounded-[22px] border border-orange-100 bg-white p-4 shadow-[0_14px_35px_rgba(15,23,42,0.06)]">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-orange-50 text-orange-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.55">
                                <rect x="5.25" y="4.5" width="13.5" height="15" rx="2.25" stroke-linecap="round" stroke-linejoin="round" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25h7.5M8.25 12h7.5M8.25 15.75h5.25" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-[18px] font-semibold text-slate-900">Questions</h3>
                            <p class="mt-0.5 text-sm text-slate-500">Drag to reorder questions</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- <button type="button" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                            Renumber
                        </button> -->
                        <div class="rounded-xl border border-orange-100 bg-orange-50 px-3 py-1.5 text-[11px] font-medium text-orange-600">
                            {{ $questions->count() }} Questions
                        </div>
                    </div>
                </div>

                <div id="questionList" class="question-scrollbar space-y-3 overflow-y-auto">
                    @forelse($questions as $index => $question)
                        <article class="question-card rounded-[18px] border border-slate-200 bg-white px-4 py-3 shadow-sm transition hover:border-orange-200" data-question-id="{{ $question->question_id }}">
                            <div class="flex items-start gap-3">
                                <div class="flex items-center pt-2 text-orange-300/80">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.45">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6h.01M15 6h.01M9 12h.01M15 12h.01M9 18h.01M15 18h.01" />
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="mb-1 flex flex-wrap items-center gap-2">
                                        <h4 class="truncate text-[14px] font-semibold text-slate-900">{{ $question->question }}@if($question->is_required)<span class="text-rose-500"> *</span>@endif</h4>
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">{{ match($question->type) {
                                            'multiple_choice' => 'Multiple Choice',
                                            'text' => 'Text',
                                            'textarea' => 'Long Text',
                                            default => 'Rating',
                                        } }}</span>
                                        <span class="rounded-full bg-orange-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-orange-600">{{ $question->applies_to_label }}</span>
                                        @if($question->allow_comment)
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-emerald-600">Comment On</span>
                                        @endif
                                        @if($question->allow_attachment)
                                            <span class="rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-sky-600">Photo On</span>
                                        @endif
                                    </div>

                                    <form id="questionForm{{ $question->question_id }}" action="{{ route('questions.update', $question) }}" method="POST" class="question-editor question-editor-locked mt-3 hidden space-y-3" data-choice-builder-form="true">
                                        @csrf
                                        @method('PUT')

                                        <textarea name="question" rows="2" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-orange-300 focus:bg-white">{{ $question->question }}</textarea>

                                        <div class="grid gap-2 md:grid-cols-[0.95fr,0.8fr,0.65fr,1fr]">
                                            <select name="type" disabled class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-orange-300 focus:bg-white">
                                                <option value="rating" {{ $question->type === 'rating' ? 'selected' : '' }}>Rating</option>
                                                <option value="multiple_choice" {{ $question->type === 'multiple_choice' ? 'selected' : '' }}>Multiple Choice</option>
                                                <option value="text" {{ $question->type === 'text' ? 'selected' : '' }}>Text</option>
                                                <option value="textarea" {{ $question->type === 'textarea' ? 'selected' : '' }}>Long Text</option>
                                            </select>

                                            <select name="applies_to" disabled class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-orange-300 focus:bg-white">
                                                <option value="overall_service" {{ ($question->applies_to ?? 'overall_service') === 'overall_service' ? 'selected' : '' }}>Overall/Service</option>
                                                <option value="staff" {{ ($question->applies_to ?? 'overall_service') === 'staff' ? 'selected' : '' }}>Staff</option>
                                                <option value="manager" {{ ($question->applies_to ?? 'overall_service') === 'manager' ? 'selected' : '' }}>Manager</option>
                                            </select>

                                            <input type="number" name="sort_order" value="{{ $question->sort_order }}" min="1" readonly class="question-sort-order w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-orange-300 focus:bg-white">

                                            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                                <label class="flex items-center gap-2">
                                                    <input type="checkbox" name="is_required" value="1" {{ $question->is_required ? 'checked' : '' }} disabled class="h-4 w-4 rounded border-slate-300 text-orange-500 focus:ring-orange-300">
                                                    Required
                                                </label>
                                            </div>
                                        </div>

                                        <label class="flex items-start gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                            <input type="checkbox" name="allow_comment" value="1" {{ $question->allow_comment ? 'checked' : '' }} disabled class="mt-1 h-4 w-4 rounded border-slate-300 text-orange-500 focus:ring-orange-300">
                                            <span>
                                                <span class="block font-medium text-slate-800">Require comment field</span>
                                                <span class="block text-xs text-slate-500">Require a comment input for this question in the survey.</span>
                                            </span>
                                        </label>

                                        <label class="flex items-start gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                            <input type="checkbox" name="allow_attachment" value="1" {{ $question->allow_attachment ? 'checked' : '' }} disabled class="mt-1 h-4 w-4 rounded border-slate-300 text-orange-500 focus:ring-orange-300">
                                            <span>
                                                <span class="block font-medium text-slate-800">Add photo button</span>
                                                <span class="block text-xs text-slate-500">Require a photo attachment for this question in the survey.</span>
                                            </span>
                                        </label>

                                        <div class="choice-options-group {{ $question->type === 'multiple_choice' ? '' : 'is-hidden' }}">
                                            @php
                                                $existingChoices = collect($question->options ?? [])->values();
                                                if ($existingChoices->count() < 2) {
                                                    $existingChoices = collect(['', '']);
                                                }
                                            @endphp
                                            <div class="space-y-2" data-choice-list>
                                                @foreach($existingChoices as $choiceIndex => $choiceValue)
                                                    <div class="choice-option-row flex items-center gap-2" data-choice-row>
                                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-xs font-semibold text-orange-500">{{ $choiceIndex + 1 }}</span>
                                                        <input type="text" name="choice_options[]" value="{{ $choiceValue }}" readonly class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-orange-300 focus:bg-white" placeholder="Choice {{ $choiceIndex + 1 }}">
                                                        <button type="button" data-remove-choice class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-500" aria-label="Remove choice">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                @endforeach
                                                <div class="choice-option-row flex items-center gap-2" data-choice-row data-template="true">
                                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-xs font-semibold text-orange-500" data-choice-number>1</span>
                                                    <input type="text" name="choice_options[]" value="" readonly class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-orange-300 focus:bg-white" placeholder="Choice 1">
                                                    <button type="button" data-remove-choice class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-500" aria-label="Remove choice">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <button type="button" data-add-choice class="mt-3 inline-flex items-center gap-2 rounded-xl border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-600 transition hover:bg-orange-100">
                                                <span class="text-base leading-none">+</span>
                                                <span>Add choice</span>
                                            </button>
                                            <p class="mt-2 text-xs text-slate-500">Each row is one poll option shown on the public survey.</p>
                                        </div>

                                        <div class="question-editor-actions hidden items-center justify-end gap-2">
                                            <button type="button" onclick="cancelQuestionEdit({{ $question->question_id }})" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                                Cancel
                                            </button>
                                                <button type="button" onclick="openConfirmModal({ title: 'Save question changes?', message: 'This will update the draft only. Run Public Sync when you are ready for stores to see the changes.', confirmLabel: 'Save Changes', onConfirm: () => document.getElementById('questionForm{{ $question->question_id }}').submit() })" class="rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600">
                                                    Save
                                                </button>
                                        </div>
                                    </form>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <button type="button" onclick="enableQuestionEdit({{ $question->question_id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-orange-200 bg-white text-orange-400 transition hover:bg-orange-50" aria-label="Edit question">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.55">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 5.25 18.75 9m-10.5 9.75 3.25-.75L19.5 10a1.768 1.768 0 0 0 0-2.5l-1-1a1.768 1.768 0 0 0-2.5 0L8 14.5l-.75 3.25Z" />
                                        </svg>
                                    </button>

                                    <form id="deleteQuestionForm{{ $question->question_id }}" action="{{ route('questions.destroy', $question) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" onclick="openConfirmModal({ title: 'Delete this question?', message: 'This removes the question from the draft list only. The public survey will change after the next Public Sync, and past answers will keep the old question version.', confirmLabel: 'Delete', confirmTone: 'danger', onConfirm: () => document.getElementById('deleteQuestionForm{{ $question->question_id }}').submit() })" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-rose-200 bg-white text-rose-400 transition hover:bg-rose-50" aria-label="Delete question">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.55">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5h10.5" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 7.5v-1.5A.75.75 0 0 1 10.5 5.25h3a.75.75 0 0 1 .75.75v1.5" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 7.5.6 9.15a.75.75 0 0 0 .75.7h4.8a.75.75 0 0 0 .75-.7l.6-9.15" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[22px] border border-dashed border-orange-200 p-10 text-center text-slate-500">
                            No questions yet. Add your first question.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <script>
        let confirmModalAction = null;

        function questionActionToast(message) {
            const toast = document.getElementById('questionToast');
            toast.textContent = message;
            toast.classList.remove('hidden');
            clearTimeout(window.questionToastTimer);
            window.questionToastTimer = setTimeout(() => {
                toast.classList.add('hidden');
            }, 2200);
        }

        function setQuestionEditorState(questionId, editable) {
            const form = document.getElementById(`questionForm${questionId}`);
            if (!form) return;

            const textarea = form.querySelector('textarea[name="question"]');
            const select = form.querySelector('select[name="type"]');
            const appliesToSelect = form.querySelector('select[name="applies_to"]');
            const sortInput = form.querySelector('input[name="sort_order"]');
            const checkbox = form.querySelector('input[name="is_required"]');
            const commentCheckbox = form.querySelector('input[name="allow_comment"]');
            const attachmentCheckbox = form.querySelector('input[name="allow_attachment"]');
            const choiceOptions = form.querySelectorAll('input[name="choice_options[]"]');
            const addChoiceButton = form.querySelector('[data-add-choice]');
            const removeChoiceButtons = form.querySelectorAll('[data-remove-choice]');
            const actions = form.querySelector('.question-editor-actions');

            form.classList.toggle('question-editor-locked', !editable);
            form.classList.toggle('hidden', !editable);

            if (textarea) textarea.readOnly = !editable;
            if (select) select.disabled = !editable;
            if (appliesToSelect) appliesToSelect.disabled = !editable;
            if (sortInput) sortInput.readOnly = !editable;
            if (checkbox) checkbox.disabled = !editable;
            if (commentCheckbox) commentCheckbox.disabled = !editable;
            if (attachmentCheckbox) attachmentCheckbox.disabled = !editable;
            choiceOptions.forEach((input) => {
                input.readOnly = !editable;
                input.classList.toggle('bg-slate-50', !editable);
                input.classList.toggle('bg-white', editable);
            });
            if (addChoiceButton) addChoiceButton.classList.toggle('hidden', !editable);
            removeChoiceButtons.forEach((button) => button.classList.toggle('hidden', !editable));
            if (actions) actions.classList.toggle('hidden', !editable);
            if (actions) actions.classList.toggle('flex', editable);

            toggleChoiceOptions(form);
            refreshChoiceRows(form);
        }

        function enableQuestionEdit(questionId) {
            setQuestionEditorState(questionId, true);
            const form = document.getElementById(`questionForm${questionId}`);
            const textarea = form?.querySelector('textarea[name="question"]');
            textarea?.focus();
        }

        function cancelQuestionEdit(questionId) {
            const form = document.getElementById(`questionForm${questionId}`);
            form?.reset();
            setQuestionEditorState(questionId, false);
            refreshQuestionNumbers();
        }

        function toggleChoiceOptions(form) {
            if (!form) return;

            const typeSelect = form.querySelector('select[name="type"]');
            const optionsGroup = form.querySelector('.choice-options-group');

            if (!typeSelect || !optionsGroup) return;

            optionsGroup.classList.toggle('is-hidden', typeSelect.value !== 'multiple_choice');
        }

        function refreshChoiceRows(form) {
            const rows = Array.from(form.querySelectorAll('[data-choice-row]')).filter((row) => row.dataset.template !== 'true');

            rows.forEach((row, index) => {
                const badge = row.querySelector('[data-choice-number]') ?? row.querySelector('span');
                const input = row.querySelector('input[name="choice_options[]"]');
                const removeButton = row.querySelector('[data-remove-choice]');

                if (badge) badge.textContent = index + 1;
                if (input) input.placeholder = `Choice ${index + 1}`;
                if (removeButton) removeButton.disabled = rows.length <= 2;
            });
        }

        function addChoiceRow(form, value = '') {
            const list = form.querySelector('[data-choice-list]');
            const template = form.querySelector('[data-template="true"]');

            if (!list || !template) return;

            const clone = template.cloneNode(true);
            clone.removeAttribute('data-template');

            const input = clone.querySelector('input[name="choice_options[]"]');
            if (input) {
                input.value = value;
                input.readOnly = form.classList.contains('question-editor-locked');
            }

            list.appendChild(clone);
            refreshChoiceRows(form);
        }

        function ensureMinimumChoiceRows(form) {
            while (Array.from(form.querySelectorAll('[data-choice-row]')).filter((row) => row.dataset.template !== 'true').length < 2) {
                addChoiceRow(form);
            }

            refreshChoiceRows(form);
        }

        function openConfirmModal({ title, message, confirmLabel = 'Confirm', confirmTone = 'default', onConfirm }) {
            const modal = document.getElementById('confirmModal');
            const titleEl = document.getElementById('confirmModalTitle');
            const messageEl = document.getElementById('confirmModalMessage');
            const confirmButton = document.getElementById('confirmModalButton');

            titleEl.textContent = title || 'Confirm action';
            messageEl.textContent = message || 'Please confirm this action.';
            confirmButton.textContent = confirmLabel;
            confirmButton.className = confirmTone === 'danger'
                ? 'rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700'
                : 'rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600';

            confirmModalAction = onConfirm || null;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeConfirmModal() {
            const modal = document.getElementById('confirmModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            confirmModalAction = null;
        }

        document.getElementById('confirmModalButton')?.addEventListener('click', () => {
            if (typeof confirmModalAction === 'function') {
                confirmModalAction();
            }
            closeConfirmModal();
        });

        const questionList = document.getElementById('questionList');

        function refreshQuestionNumbers() {
            if (!questionList) return;

            [...questionList.querySelectorAll('.question-card')].forEach((card, index) => {
                const badge = card.querySelector('.question-index');
                const orderInput = card.querySelector('.question-sort-order');
                if (badge) badge.textContent = `# ${String(index).padStart(2, '0')}`;
                if (orderInput) orderInput.value = index + 1;
            });
        }

        refreshQuestionNumbers();

        document.querySelectorAll('form').forEach((form) => {
            const typeSelect = form.querySelector('select[name="type"]');

            if (!typeSelect) return;

            toggleChoiceOptions(form);
            refreshChoiceRows(form);
            typeSelect.addEventListener('change', () => toggleChoiceOptions(form));
        });

        document.querySelectorAll('[data-choice-builder-form]').forEach((form) => {
            refreshChoiceRows(form);

            form.addEventListener('click', (event) => {
                const addButton = event.target.closest('[data-add-choice]');
                const removeButton = event.target.closest('[data-remove-choice]');

                if (addButton) {
                    addChoiceRow(form);
                    const rows = Array.from(form.querySelectorAll('[data-choice-row]')).filter((row) => row.dataset.template !== 'true');
                    rows.at(-1)?.querySelector('input[name="choice_options[]"]')?.focus();
                    return;
                }

                if (removeButton) {
                    const rows = Array.from(form.querySelectorAll('[data-choice-row]')).filter((row) => row.dataset.template !== 'true');
                    if (rows.length <= 2) return;

                    removeButton.closest('[data-choice-row]')?.remove();
                    refreshChoiceRows(form);
                }
            });

            ensureMinimumChoiceRows(form);
        });
    </script>
@endsection
