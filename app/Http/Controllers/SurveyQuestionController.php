<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsActivity;
use App\Models\AppSetting;
use App\Models\SurveyQuestion;
use Illuminate\Http\Request;

class SurveyQuestionController extends Controller
{
    use LogsActivity;

    protected function normalizeOptions(array|string|null $rawOptions): array
    {
        $options = is_array($rawOptions)
            ? $rawOptions
            : preg_split('/\r\n|\r|\n/', (string) $rawOptions);

        return collect($options)
            ->map(fn ($option) => trim((string) $option))
            ->filter()
            ->values()
            ->all();
    }

    public function index()
    {
        $questions = SurveyQuestion::orderBy('sort_order')->latest()->get();
        $questionnaireTitle = AppSetting::getValue('questionnaire_title', 'Customer Feedback');
        $publishedQuestions = collect(AppSetting::getJson('published_questionnaire_snapshot'));
        $lastSyncedAt = AppSetting::getValue('questionnaire_last_synced_at');

        return view('stores.questions', compact('questions', 'questionnaireTitle', 'publishedQuestions', 'lastSyncedAt'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'questionnaire_title' => ['required', 'string', 'max:255'],
        ]);

        AppSetting::setValue('questionnaire_title', $validated['questionnaire_title']);

        $this->logActivity(
            'questions.settings_update',
            'Updated questionnaire title to ' . $validated['questionnaire_title'] . '.'
        );

        return redirect()
            ->route('questions.index')
            ->with('success', 'Questionnaire settings updated successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:rating,multiple_choice,text,textarea'],
            'choice_options' => ['nullable', 'array'],
            'choice_options.*' => ['nullable', 'string', 'max:255'],
            'applies_to' => ['required', 'in:overall_service,staff,manager'],
            'allow_comment' => ['nullable', 'boolean'],
            'allow_attachment' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $options = $validated['type'] === 'multiple_choice'
            ? $this->normalizeOptions($validated['choice_options'] ?? '')
            : [];

        if ($validated['type'] === 'multiple_choice' && count($options) < 2) {
            return back()
                ->withErrors(['choice_options' => 'Add at least 2 choices for multiple choice questions.'])
                ->withInput();
        }

        $question = SurveyQuestion::create([
            'question' => $validated['question'],
            'type' => $validated['type'],
            'options' => $options ?: null,
            'applies_to' => $validated['applies_to'],
            'allow_comment' => $request->boolean('allow_comment'),
            'allow_attachment' => $request->boolean('allow_attachment'),
            'sort_order' => $validated['sort_order'],
            'is_required' => $request->boolean('is_required'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->logActivity(
            'questions.create',
            'Added questionnaire item: ' . $question->question . '.',
            [
                'subject' => $question,
            ]
        );

        return redirect()
            ->route('questions.index')
            ->with('success', 'Question added successfully.');
    }

    public function update(Request $request, SurveyQuestion $surveyQuestion)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:rating,multiple_choice,text,textarea'],
            'choice_options' => ['nullable', 'array'],
            'choice_options.*' => ['nullable', 'string', 'max:255'],
            'applies_to' => ['required', 'in:overall_service,staff,manager'],
            'allow_comment' => ['nullable', 'boolean'],
            'allow_attachment' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $options = $validated['type'] === 'multiple_choice'
            ? $this->normalizeOptions($validated['choice_options'] ?? '')
            : [];

        if ($validated['type'] === 'multiple_choice' && count($options) < 2) {
            return back()
                ->withErrors(['choice_options' => 'Add at least 2 choices for multiple choice questions.'])
                ->withInput();
        }

        $surveyQuestion->update([
            'question' => $validated['question'],
            'type' => $validated['type'],
            'options' => $options ?: null,
            'applies_to' => $validated['applies_to'],
            'allow_comment' => $request->boolean('allow_comment'),
            'allow_attachment' => $request->boolean('allow_attachment'),
            'sort_order' => $validated['sort_order'],
            'is_required' => $request->boolean('is_required'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->logActivity(
            'questions.update',
            'Updated questionnaire item: ' . $surveyQuestion->question . '.',
            [
                'subject' => $surveyQuestion,
            ]
        );

        return redirect()
            ->route('questions.index')
            ->with('success', 'Question updated successfully.');
    }

    public function destroy(SurveyQuestion $surveyQuestion)
    {
        $questionText = $surveyQuestion->question;
        $questionId = $surveyQuestion->question_id;
        $surveyQuestion->delete();

        $this->logActivity(
            'questions.delete',
            'Deleted questionnaire item: ' . $questionText . '.',
            [
                'subject_type' => SurveyQuestion::class,
                'subject_id' => (string) $questionId,
            ]
        );

        return redirect()
            ->route('questions.index')
            ->with('success', 'Question deleted successfully.');
    }

    public function syncPublic()
    {
        $questions = SurveyQuestion::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('question_id')
            ->get()
            ->map(fn (SurveyQuestion $question) => [
                'source_question_id' => $question->question_id,
                'question' => $question->question,
                'type' => $question->type,
                'options' => $question->options ?? [],
                'applies_to' => $question->applies_to ?? 'overall_service',
                'allow_comment' => (bool) $question->allow_comment,
                'allow_attachment' => (bool) $question->allow_attachment,
                'sort_order' => (int) $question->sort_order,
                'is_required' => (bool) $question->is_required,
                'is_active' => (bool) $question->is_active,
            ])
            ->values()
            ->all();

        AppSetting::setJson('published_questionnaire_snapshot', $questions);
        AppSetting::setValue(
            'published_questionnaire_title',
            AppSetting::getValue('questionnaire_title', 'Feedback Survey')
        );
        AppSetting::setValue('questionnaire_last_synced_at', now()->toDateTimeString());

        $this->logActivity(
            'questions.sync_public',
            'Synced the questionnaire to the public survey.',
            [
                'metadata' => [
                    'question_count' => count($questions),
                ],
            ]
        );

        return redirect()
            ->route('questions.index')
            ->with('success', 'Questionnaire synced to the public survey.');
    }
}
