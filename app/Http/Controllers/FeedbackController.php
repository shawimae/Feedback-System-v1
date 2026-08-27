<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithUserAccess;
use App\Http\Controllers\Concerns\LogsActivity;
use App\Models\Feedback;
use App\Models\FeedbackAnswer;
use App\Models\Store;
use App\Models\SurveyQuestion;
use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\CustomerPointsLog;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Mail\AdminFeedbackReceivedMail;
use App\Mail\CustomerRewardMail;
use App\Mail\CustomerFeedbackReplyMail;
use App\Mail\CustomerFeedbackSubmittedMail;
use App\Services\SmsNotificationService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class FeedbackController extends Controller
{
    use InteractsWithUserAccess, LogsActivity;

    public function checkTransactionNumber(Request $request, Store $store): JsonResponse
    {
        $licenseState = AppSetting::licenseStateForStore($store);

        if (! $licenseState['is_valid']) {
            return response()->json([
                'available' => false,
                'license_invalid' => true,
                'message' => $licenseState['message'],
            ], 423);
        }

        $validated = $request->validate([
            'transaction_number' => ['required', 'digits:10'],
        ]);

        $transactionNumber = trim((string) $validated['transaction_number']);
        $exists = Feedback::where('transaction_number', $transactionNumber)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists
                ? 'This transaction number has already been used.'
                : 'Transaction number is available.',
        ]);
    }

    protected function calculateReviewRewardPoints(?float $overallRating, ?string $overallComment): int
    {
        $points = 15;

        if (!is_null($overallRating) && $overallRating >= 5) {
            $points += 5;
        } elseif (!is_null($overallRating) && $overallRating >= 4) {
            $points += 3;
        }

        if (!empty($overallComment)) {
            $points += 2;
        }

        return $points;
    }

    protected function isReviewRewardEligible(Feedback $feedback): bool
    {
        return !empty($feedback->customer_email)
            && !empty(optional($feedback->store)->google_review_url)
            && !is_null($feedback->overall_rating)
            && $feedback->overall_rating >= 4;
    }

    /**
     * Admin feedback list page with filters
     */
    public function index(Request $request)
    {
        $stores = $this->accessibleStores();
        $timeframe = $request->input('timeframe', 'monthly');

        $query = Feedback::with(['store', 'answers.question'])->latest();
        $this->scopeStoreIdQuery($query);

        if ($request->filled('store_id') && $stores->contains('store_id', (int) $request->store_id)) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        } elseif (!$request->filled('date_to')) {
            if ($timeframe === 'daily') {
                $query->whereDate('created_at', Carbon::today());
            } elseif ($timeframe === 'weekly') {
                $query->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay());
            } else {
                $query->where('created_at', '>=', Carbon::now()->subDays(29)->startOfDay());
            }
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', '%' . $search . '%')
                  ->orWhere('customer_email', 'like', '%' . $search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $search . '%')
                  ->orWhere('overall_comment', 'like', '%' . $search . '%')
                  ->orWhereHas('store', function ($storeQuery) use ($search) {
                      $storeQuery->where('name', 'like', '%' . $search . '%')
                                 ->orWhere('store_number', 'like', '%' . $search . '%');
                  });
            });
        }

        $feedbacks = $query->get();

        return view('stores.feedbacks', compact('feedbacks', 'stores', 'timeframe'));
    }

    public function reply(Request $request, Feedback $feedback)
    {
        $this->ensureStoreAccess($feedback->store()->firstOrFail());

        $validated = $request->validate([
            'admin_reply' => ['nullable', 'string', 'max:2000'],
        ]);

        $feedback->loadMissing(['store', 'answers.question']);

        $reply = trim((string) ($validated['admin_reply'] ?? ''));

        $feedback->update([
            'admin_reply' => $reply !== '' ? $reply : null,
            'admin_replied_at' => $reply !== '' ? now() : null,
        ]);

        if ($reply !== '' && !empty($feedback->customer_email)) {
            $customer = Customer::where('email', $feedback->customer_email)->first();
            $pointsEarned = CustomerPointsLog::where('feedback_id', $feedback->feedback_id)->sum('points');

            Mail::to($feedback->customer_email)->send(
                new CustomerFeedbackReplyMail(
                    $feedback->fresh(['store', 'answers.question']),
                    $feedback->store,
                    $customer,
                    (int) $pointsEarned,
                    $reply
                )
            );
        }

        $this->logActivity(
            'feedbacks.reply',
            'Updated admin reply for feedback #' . $feedback->feedback_id . '.',
            [
                'subject' => $feedback,
                'store' => $feedback->store,
            ]
        );

        return redirect()->route('feedbacks.index', array_filter([
            'store_id' => $request->input('store_id'),
            'timeframe' => $request->input('timeframe'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
            'tab' => $request->input('tab', 'all'),
            'feedback' => $feedback->feedback_id,
        ]))->with('success', 'Reply updated successfully.');
    }

    public function updateResolution(Request $request, Feedback $feedback)
    {
        $this->ensureStoreAccess($feedback->store()->firstOrFail());

        $validated = $request->validate([
            'is_resolved' => ['required', 'boolean'],
        ]);

        $isResolved = (bool) $validated['is_resolved'];

        $feedback->update([
            'is_resolved' => $isResolved,
            'resolved_at' => $isResolved ? now() : null,
        ]);

        $feedback->loadMissing('store');

        $this->logActivity(
            'feedbacks.resolution',
            ($isResolved ? 'Marked' : 'Reopened') . ' feedback #' . $feedback->feedback_id . '.',
            [
                'subject' => $feedback,
                'store' => $feedback->store,
            ]
        );

        return redirect()->route('feedbacks.index', array_filter([
            'store_id' => $request->input('store_id'),
            'timeframe' => $request->input('timeframe'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
            'tab' => $request->input('tab', 'all'),
            'feedback' => $feedback->feedback_id,
        ]))->with('success', $isResolved ? 'Feedback marked as resolved.' : 'Feedback moved back to unresolved.');
    }

    public function claimReviewReward(Request $request, Feedback $feedback)
    {
        $feedback->loadMissing('store');

        abort_unless($this->isReviewRewardEligible($feedback), 403);

        $validated = $request->validate([
            'review_claim_name' => ['required', 'string', 'max:255'],
            'review_claim_notes' => ['nullable', 'string', 'max:1000'],
            'review_claim_screenshot' => ['nullable', 'image', 'max:4096'],
        ]);

        $screenshotPath = $feedback->review_claim_screenshot;

        if ($request->hasFile('review_claim_screenshot')) {
            $screenshotPath = $request->file('review_claim_screenshot')->store('review-claims', 'public');
        }

        $feedback->update([
            'review_claim_name' => $validated['review_claim_name'],
            'review_claim_notes' => $validated['review_claim_notes'] ?? null,
            'review_claim_screenshot' => $screenshotPath,
            'review_claimed_at' => now(),
            'reward_status' => 'claimed',
        ]);

        return redirect()->route('stores.thank-you', $feedback)->with('success', 'Your review claim was submitted and is now waiting for approval.');
    }

    public function updateReward(Request $request, Feedback $feedback, SmsNotificationService $smsService)
    {
        $this->ensureStoreAccess($feedback->store()->firstOrFail());

        $validated = $request->validate([
            'reward_action' => ['required', 'in:approve,reject,reset'],
        ]);

        $feedback->loadMissing(['store', 'answers.question']);
        $customer = !empty($feedback->customer_email)
            ? Customer::where('email', $feedback->customer_email)->first()
            : null;

        $action = $validated['reward_action'];

        if ($action === 'approve') {
            abort_unless($feedback->reward_status === 'claimed', 422);

            if (!$customer) {
                return back()->withErrors(['reward' => 'No customer record found for this feedback.']);
            }

            $points = (int) ($feedback->reward_points_pending ?? 0);

            DB::transaction(function () use ($feedback, $customer, $points) {
                $customer->increment('total_points', $points);

                CustomerPointsLog::create([
                    'customer_id' => $customer->customer_id,
                    'feedback_id' => $feedback->feedback_id,
                    'points' => $points,
                    'reason' => 'Google Review reward approved',
                ]);

                $feedback->update([
                    'reward_status' => 'approved',
                    'reward_points_awarded' => $points,
                    'reward_approved_at' => now(),
                ]);
            });

            Mail::to($customer->email)->send(
                new CustomerRewardMail(
                    $feedback->fresh(['answers.question']),
                    $feedback->store,
                    $customer->fresh(),
                    $points
                )
            );

            NotificationLog::create([
                'customer_id' => $customer->customer_id,
                'feedback_id' => $feedback->feedback_id,
                'channel' => 'email',
                'recipient' => $customer->email,
                'subject' => 'Your Google Review reward was approved',
                'message' => 'Reward approval email sent.',
                'status' => 'sent',
            ]);

            if (!empty($customer->phone)) {
                $smsMessage = 'Hi ' . ($customer->name ?? 'Customer') . ', your Google Review reward for '
                    . ($feedback->store->name ?? 'our store')
                    . ' was approved. You earned ' . $points . ' points. Total points: ' . $customer->fresh()->total_points . '.';

                $smsService->sendRewardNotification($customer->fresh(), $feedback, $customer->phone, $smsMessage);
            }

            $feedback->update([
                'reward_notified_at' => now(),
            ]);

            $this->logActivity(
                'feedbacks.reward',
                'Approved reward claim for feedback #' . $feedback->feedback_id . '.',
                [
                    'subject' => $feedback,
                    'store' => $feedback->store,
                ]
            );

            return back()->with('success', 'Reward approved and customer notification sent.');
        }

        if ($action === 'reject') {
            $feedback->update([
                'reward_status' => 'rejected',
            ]);

            $this->logActivity(
                'feedbacks.reward',
                'Rejected reward claim for feedback #' . $feedback->feedback_id . '.',
                [
                    'subject' => $feedback,
                    'store' => $feedback->store,
                ]
            );

            return back()->with('success', 'Reward claim rejected.');
        }

        $feedback->update([
            'reward_status' => $this->isReviewRewardEligible($feedback) ? 'eligible' : 'not_eligible',
            'review_claim_name' => null,
            'review_claim_notes' => null,
            'review_claim_screenshot' => null,
            'review_claimed_at' => null,
        ]);

        $this->logActivity(
            'feedbacks.reward',
            'Reset reward claim for feedback #' . $feedback->feedback_id . '.',
            [
                'subject' => $feedback,
                'store' => $feedback->store,
            ]
        );

        return back()->with('success', 'Reward claim reset.');
    }

    /**
     * Download filtered feedbacks as CSV
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $query = Feedback::with(['store', 'answers.question'])->latest();
        $this->scopeStoreIdQuery($query);

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $feedbacks = $query->get();
        $questions = SurveyQuestion::orderBy('sort_order')->get();

        $filename = 'feedback_report_' . now()->format('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($feedbacks, $questions) {
            $handle = fopen('php://output', 'w');

            $headers = [
                'Feedback ID',
                'Store Number',
                'Store Name',
                'Customer Name',
                'Customer Email',
                'Customer Phone',
                'Overall Rating',
                'Overall Comment',
                'Submitted At',
            ];

            foreach ($questions as $question) {
                $headers[] = $question->question;
            }

            fputcsv($handle, $headers);

            foreach ($feedbacks as $feedback) {
                $row = [
                    $feedback->feedback_id,
                    $feedback->store->store_number ?? '',
                    $feedback->store->name ?? '',
                    $feedback->customer_name ?? '',
                    $feedback->customer_email ?? '',
                    $feedback->customer_phone ?? '',
                    $feedback->overall_rating ?? '',
                    $feedback->overall_comment ?? '',
                    optional($feedback->created_at)->format('Y-m-d H:i:s'),
                ];

                $answersByQuestion = $feedback->answers->keyBy('question_id');

                foreach ($questions as $question) {
                    $answer = $answersByQuestion->get($question->question_id);

                    if (!$answer) {
                        $row[] = '';
                        continue;
                    }

                    $answerValue = !is_null($answer->answer_rating)
                        ? $answer->answer_rating . '/5'
                        : $answer->answer_text;

                    if (!empty($answer->answer_attachment)) {
                        $answerValue = trim(($answerValue ?: 'Attachment submitted') . ' | Attachment: ' . Storage::url($answer->answer_attachment));
                    }

                    if (!empty($answer->answer_comment)) {
                        $answerValue = trim(($answerValue ?: 'No main answer') . ' | Comment: ' . $answer->answer_comment);
                    }

                    $row[] = $answerValue;
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename);
    }

    /**
     * Public survey submit
     */
    public function store(Request $request, Store $store)
    {
        $licenseState = AppSetting::licenseStateForStore($store);

        if (! $licenseState['is_valid']) {
            return response()
                ->view('survey.unavailable', [
                    'store' => $store,
                    'message' => $licenseState['message'],
                ], 423);
        }

        $questions = collect(AppSetting::getJson('published_questionnaire_snapshot'))
            ->filter(fn (array $question) => (bool) ($question['is_active'] ?? true))
            ->sortBy('sort_order')
            ->values()
            ->map(function (array $question) {
                $questionType = $question['type'] ?? 'text';

                if ($questionType === 'image_attachment') {
                    $questionType = 'text';
                    $question['allow_attachment'] = true;
                }

                return (object) [
                    'question_id' => $question['source_question_id'] ?? null,
                    'question' => $question['question'] ?? '',
                    'type' => $questionType,
                    'options' => collect($question['options'] ?? [])->filter()->values()->all(),
                    'applies_to' => ($question['applies_to'] ?? 'overall_service') === 'general'
                        ? 'overall_service'
                        : ($question['applies_to'] ?? 'overall_service'),
                    'allow_comment' => (bool) ($question['allow_comment'] ?? false),
                    'allow_attachment' => (bool) ($question['allow_attachment'] ?? false),
                    'sort_order' => (int) ($question['sort_order'] ?? 0),
                    'is_required' => (bool) ($question['is_required'] ?? false),
                ];
            });

        $rules = [
            'transaction_number' => ['required', 'digits:10', 'unique:feedbacks,transaction_number'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'overall_comment' => ['nullable', 'string'],
        ];

        foreach ($questions as $question) {
            $field = 'question_' . $question->question_id;
            $mentionField = 'question_target_' . $question->question_id;
            $commentField = 'question_comment_' . $question->question_id;
            $photoField = 'question_photo_' . $question->question_id;

            $hasMentionPicker = in_array($question->applies_to, ['staff', 'manager'], true);

            if ($hasMentionPicker) {
                $targetRole = $question->applies_to === 'manager' ? 'manager' : 'staff';
                $rules[$mentionField] = $question->is_required
                    ? [
                        'required',
                        'string',
                        Rule::exists('store_staff', 'name')->where(function ($query) use ($store, $targetRole) {
                            $query->where('store_id', $store->store_id)
                                ->where('status', 'active')
                                ->whereRaw('LOWER(role) = ?', [$targetRole]);
                        }),
                    ]
                    : [
                        'nullable',
                        'string',
                        Rule::exists('store_staff', 'name')->where(function ($query) use ($store, $targetRole) {
                            $query->where('store_id', $store->store_id)
                                ->where('status', 'active')
                                ->whereRaw('LOWER(role) = ?', [$targetRole]);
                        }),
                    ];
            }

            if ($question->type === 'rating') {
                $rules[$field] = $question->is_required
                    ? ['required', 'integer', 'min:1', 'max:5']
                    : ['nullable', 'integer', 'min:1', 'max:5'];
            } elseif ($question->type === 'multiple_choice') {
                $allowedOptions = collect($question->options ?? [])->map(fn ($option) => (string) $option)->all();

                $rules[$field] = $question->is_required
                    ? ['required', 'string', Rule::in($allowedOptions)]
                    : ['nullable', 'string', Rule::in($allowedOptions)];
            } else {
                $rules[$field] = $question->is_required
                    ? ['required', 'string']
                    : ['nullable', 'string'];
            }

            if ($question->allow_comment) {
                $rules[$commentField] = ['required', 'string'];
            }

            if ($question->allow_attachment) {
                $rules[$photoField] = ['required', 'image', 'max:5120'];
            }
        }

        $validated = $request->validate($rules);

        $result = DB::transaction(function () use ($request, $store, $questions, $validated) {
            $ratings = $questions
                ->filter(fn ($question) => $question->type === 'rating')
                ->take(3)
                ->map(function ($question) use ($request) {
                    $field = 'question_' . $question->question_id;

                    return $request->filled($field)
                        ? (int) $request->input($field)
                        : null;
                })
                ->filter(fn ($rating) => !is_null($rating))
                ->values()
                ->all();

            $overallRating = count($ratings)
                ? round(array_sum($ratings) / count($ratings), 1)
                : null;

            $feedback = Feedback::create([
                'store_id' => $store->store_id,
                'transaction_number' => trim((string) $validated['transaction_number']),
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'overall_rating' => $overallRating,
                'overall_comment' => $validated['overall_comment'] ?? null,
            ]);

            foreach ($questions as $question) {
                $field = 'question_' . $question->question_id;
                $mentionField = 'question_target_' . $question->question_id;
                $commentField = 'question_comment_' . $question->question_id;
                $photoField = 'question_photo_' . $question->question_id;
                $attachmentPath = null;

                if ($question->allow_attachment && $request->hasFile($photoField)) {
                    $attachmentPath = $request->file($photoField)->store('feedback-attachments', 'public');
                }

                FeedbackAnswer::create([
                    'feedback_id' => $feedback->feedback_id,
                    'question_id' => $question->question_id && SurveyQuestion::where('question_id', $question->question_id)->exists()
                        ? $question->question_id
                        : null,
                    'question_snapshot' => $question->question,
                    'question_type_snapshot' => $question->type,
                    'answer_text' => in_array($question->applies_to, ['staff', 'manager'], true)
                        ? $request->input($mentionField)
                        : (in_array($question->type, ['text', 'textarea', 'multiple_choice'])
                            ? $request->input($field)
                            : null),
                    'answer_attachment' => $attachmentPath,
                    'answer_comment' => $question->allow_comment
                        ? $request->input($commentField)
                        : null,
                    'answer_rating' => $question->type === 'rating'
                        ? $request->input($field)
                        : null,
                ]);
            }

            $customer = null;
            $pendingRewardPoints = 0;

            if (!empty($validated['customer_email'])) {

                $customer = Customer::firstOrCreate(
                    ['email' => $validated['customer_email']],
                    [
                        'name' => $validated['customer_name'] ?? null,
                        'phone' => $validated['customer_phone'] ?? null,
                        'total_points' => 0,
                    ]
                );

                if ($overallRating >= 4 && !empty($store->google_review_url)) {
                    $pendingRewardPoints = $this->calculateReviewRewardPoints($overallRating, $validated['overall_comment'] ?? null);
                }
            }

            $feedback->update([
                'reward_status' => $pendingRewardPoints > 0 ? 'eligible' : 'not_eligible',
                'reward_points_pending' => $pendingRewardPoints,
            ]);

            return [
                'feedback' => $feedback,
                'customer' => $customer,
                'points' => $pendingRewardPoints
            ];
        });

        $submittedFeedback = $result['feedback']->fresh(['store', 'answers.question']);

        Mail::to('umayamshairamae.s@gmail.com')->send(
            new AdminFeedbackReceivedMail($submittedFeedback, $store)
        );

        if (!empty($submittedFeedback->customer_email)) {
            Mail::to($submittedFeedback->customer_email)->send(
                new CustomerFeedbackSubmittedMail($submittedFeedback, $store)
            );

            NotificationLog::create([
                'customer_id' => $result['customer']?->customer_id,
                'feedback_id' => $submittedFeedback->feedback_id,
                'channel' => 'email',
                'notification_type' => 'customer_confirmation',
                'recipient' => $submittedFeedback->customer_email,
                'subject' => 'Feedback Submission Confirmation - ' . ($store->name ?? 'Our Store'),
                'message' => 'Customer feedback submission confirmation email sent.',
                'status' => 'sent',
            ]);
        }

        NotificationLog::create([
            'customer_id' => $result['customer']?->customer_id,
            'feedback_id' => $submittedFeedback->feedback_id,
            'channel' => 'admin',
            'notification_type' => 'survey_feedback',
            'recipient' => 'admin',
            'subject' => $submittedFeedback->customer_email ?: 'No email provided',
            'message' => trim(collect([
                'Store Number: ' . ($store->store_number ?: $store->name),
                !is_null($submittedFeedback->overall_rating) ? 'Rating: ' . number_format((float) $submittedFeedback->overall_rating, 1) : null,
            ])->filter()->implode(' | ')),
            'status' => 'sent',
            'is_read' => false,
        ]);

        return redirect()->route('stores.thank-you', $result['feedback']->feedback_id);
    }

    /**
     * Thank you page
     */
    public function thankYou(Feedback $feedback)
    {
        $feedback->loadMissing('store');
        $showGoogleReviewButton = !is_null($feedback->overall_rating)
            && $feedback->overall_rating >= 4;
        $canClaimReviewReward = $this->isReviewRewardEligible($feedback);

        $googleReviewUrl = $feedback->store->google_review_url ?? 'https://g.page/r/CUbE41faxB6FEAE/review';

        return view('stores.thank-you', compact(
            'feedback',
            'showGoogleReviewButton',
            'canClaimReviewReward',
            'googleReviewUrl'
        ));
    }

    /**
     * Delete feedback
     */
    public function destroy(Feedback $feedback)
    {
        $store = $feedback->store()->firstOrFail();
        $this->ensureStoreAccess($store);
        $feedbackId = $feedback->feedback_id;
        $feedback->delete();

        $this->logActivity(
            'feedbacks.delete',
            'Deleted feedback #' . $feedbackId . '.',
            [
                'subject_type' => Feedback::class,
                'subject_id' => (string) $feedbackId,
                'store' => $store,
            ]
        );

        return redirect()->route('feedbacks.index');
    }
}
