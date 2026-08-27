<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithUserAccess;
use App\Http\Controllers\Concerns\LogsActivity;
use App\Models\Feedback;
use App\Models\FeedbackAnswer;
use App\Models\AppSetting;
use App\Models\SoftwareLicense;
use App\Models\Store;
use App\Models\SurveyQuestion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use InteractsWithUserAccess, LogsActivity;

    /**
     * Show login page
     */
    public function showLogin()
    {
        return view('auth.login', [
            'canSelfRegister' => !User::exists(),
            'licenseState' => AppSetting::licenseState(),
        ]);
    }

    /**
     * Show register page
     */
    public function showRegister()
    {
        if (User::exists()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Public registration is disabled. Please contact a Dev account administrator.']);
        }

        return view('auth.register');
    }

    /**
     * Handle registration
     */
    public function register(Request $request)
    {
        if (User::exists()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Public registration is disabled. Please contact a Dev account administrator.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => User::ROLE_DEV,
            'feature_access' => User::defaultFeatureAccessFor(User::ROLE_DEV),
            'password' => Hash::make($validated['password']),
        ]);

        // Redirect to login with success modal trigger
        return redirect()
            ->route('login')
            ->with('account_created', 'Account successfully created.');
    }

    /**
     * Handle login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = $request->user();
            $licenseState = AppSetting::licenseStateForUser($user);

            if ($user && ! $user->isDev() && ! $licenseState['is_valid']) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->with('license_prompt', true)
                    ->with('license_reason', $licenseState['reason'])
                    ->with('license_can_request_renewal', $user->isSuperAdmin())
                    ->withInput(['email' => $credentials['email']])
                    ->withErrors(['email' => $licenseState['message']]);
            }

            $this->logActivity(
                'auth.login',
                ($user->name ?? 'User') . ' signed in to the system.'
            );

            return redirect()
                ->route('dashboard')
                ->with('success', 'Login successful.');
        }

        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
    }

    /**
     * Dashboard (protected)
     */
    public function dashboard(Request $request)
    {
        $stores = $this->accessibleStores();
        $selectedStoreId = $this->normalizeSelectedStoreId(
            $request->query('store_id'),
            $stores,
            !$this->currentUser()->isAdmin()
        );
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $metrics = $this->getDashboardMetrics($selectedStoreId, $dateFrom, $dateTo);

        return view('dashboard', array_merge([
            'stores' => $stores,
            'selectedStoreId' => $selectedStoreId,
            'dateFrom' => $metrics['dateFrom'],
            'dateTo' => $metrics['dateTo'],
            'dateRangeLabel' => $metrics['dateRangeLabel'],
        ], $metrics));
    }

    public function dashboardExport(Request $request)
    {
        $stores = $this->accessibleStores();
        $selectedStoreId = $this->normalizeSelectedStoreId(
            $request->query('store_id'),
            $stores,
            !$this->currentUser()->isAdmin()
        );
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $metrics = $this->getDashboardMetrics($selectedStoreId, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('dashboard.export', array_merge([
            'stores' => $stores,
            'selectedStoreId' => $selectedStoreId,
            'dateFrom' => $metrics['dateFrom'],
            'dateTo' => $metrics['dateTo'],
            'dateRangeLabel' => $metrics['dateRangeLabel'],
        ], $metrics));

        $storeSlug = ($selectedStoreId && $selectedStoreId !== 'all')
            ? ('store_' . $selectedStoreId)
            : 'all_stores';
        $rangeSlug = str_replace('-', '_', $metrics['dateFrom']) . '_to_' . str_replace('-', '_', $metrics['dateTo']);

        return $pdf->download('feedback_dashboard_' . $storeSlug . '_' . $rangeSlug . '_' . now()->format('Y_m_d_His') . '.pdf');
    }

    protected function getDashboardMetrics($selectedStoreId, $dateFrom = null, $dateTo = null)
    {
        $user = $this->currentUser();
        $from = $dateFrom
            ? now()->parse($dateFrom)->startOfDay()
            : now()->startOfWeek();

        $to = $dateTo
            ? now()->parse($dateTo)->endOfDay()
            : now()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $dateFrom = $from->toDateString();
        $dateTo = $to->toDateString();
        $dateRangeLabel = $from->isSameDay($to)
            ? $from->format('M d, Y')
            : $from->format('M d, Y') . ' - ' . $to->format('M d, Y');

        $feedbackQuery = Feedback::with(['store', 'answers.question']);
        $this->scopeStoreIdQuery($feedbackQuery, 'store_id', $user);
        $isSingleStoreView = filled($selectedStoreId) && $selectedStoreId !== 'all';

        $feedbackCollection = $feedbackQuery
            ->when($isSingleStoreView, fn ($query) => $query->where('store_id', $selectedStoreId))
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $storeFocusedAverageRating = null;

        if ($isSingleStoreView) {
            $storeFocusedRatings = $feedbackCollection
                ->flatMap(function ($feedback) {
                    return $feedback->answers->filter(function ($answer) {
                        $sortOrder = (int) ($answer->question?->sort_order ?? 0);

                        return $sortOrder >= 1
                            && $sortOrder <= 3
                            && !is_null($answer->answer_rating);
                    });
                })
                ->pluck('answer_rating')
                ->map(fn ($rating) => (float) $rating)
                ->values();

            if ($storeFocusedRatings->isNotEmpty()) {
                $storeFocusedAverageRating = round((float) $storeFocusedRatings->avg(), 1);
            }
        }

        $ratedFeedback = $feedbackCollection->filter(fn ($feedback) => !is_null($feedback->overall_rating))->values();
        $totalFeedback = $feedbackCollection->count();
        $averageRating = $ratedFeedback->isNotEmpty()
            ? round((float) $ratedFeedback->avg('overall_rating'), 1)
            : null;
        $positiveFeedback = $ratedFeedback->filter(fn ($feedback) => (float) $feedback->overall_rating >= 4)->count();
        $neutralFeedback = $ratedFeedback->filter(fn ($feedback) => (float) $feedback->overall_rating >= 3 && (float) $feedback->overall_rating < 4)->count();
        $lowFeedback = $ratedFeedback->filter(fn ($feedback) => (float) $feedback->overall_rating <= 2)->count();
        $rating5 = $ratedFeedback->filter(fn ($feedback) => (float) $feedback->overall_rating >= 4.5)->count();
        $rating4 = $ratedFeedback->filter(fn ($feedback) => (float) $feedback->overall_rating >= 3.5 && (float) $feedback->overall_rating < 4.5)->count();
        $rating3 = $ratedFeedback->filter(fn ($feedback) => (float) $feedback->overall_rating >= 2.5 && (float) $feedback->overall_rating < 3.5)->count();
        $rating2 = $ratedFeedback->filter(fn ($feedback) => (float) $feedback->overall_rating >= 1.5 && (float) $feedback->overall_rating < 2.5)->count();
        $rating1 = $ratedFeedback->filter(fn ($feedback) => (float) $feedback->overall_rating >= 0 && (float) $feedback->overall_rating < 1.5)->count();

        $recentFeedbacks = $feedbackCollection
            ->sortByDesc(fn ($feedback) => optional($feedback->created_at)?->timestamp ?? 0)
            ->take(5)
            ->values()
            ->map(function ($feedback) {
                return [
                    'feedback_id' => $feedback->feedback_id,
                    'date' => optional($feedback->created_at)?->format('M d, Y'),
                    'branch' => $feedback->store?->name ?? 'Unknown branch',
                    'staff_name' => $this->extractTaggedPersonName($feedback, 'staff') ?? 'N/A',
                    'feedback' => $this->extractFeedbackSnippet($feedback),
                    'average_rating' => !is_null($feedback->overall_rating)
                        ? round((float) $feedback->overall_rating, 1)
                        : null,
                ];
            });

        $stores = $this->accessibleStoresQuery($user)
            ->when($isSingleStoreView, fn ($query) => $query->where('store_id', $selectedStoreId))
            ->orderBy('name')
            ->get();

        $totalStores = $stores->count();
        $writtenFeedbackCount = $feedbackCollection
            ->filter(function ($feedback) {
                if (filled($feedback->overall_comment)) {
                    return true;
                }

                return $feedback->answers->contains(function ($answer) {
                    return filled($answer->answer_comment)
                        || (
                            filled($answer->answer_text)
                            && !in_array($answer->question?->applies_to, ['staff', 'manager'], true)
                        );
                });
            })
            ->count();
        $responseCountsByStore = $feedbackCollection
            ->groupBy('store_id')
            ->map(fn ($group) => $group->count());
        $overallRatingsByStore = $feedbackCollection
            ->groupBy('store_id')
            ->map(fn ($group) => $group->pluck('overall_rating')->filter(fn ($rating) => !is_null($rating))->map(fn ($rating) => (float) $rating)->values());
        $recoveryCasesByStore = $feedbackCollection
            ->groupBy('store_id')
            ->map(fn ($group) => $group->filter(fn ($feedback) => !is_null($feedback->overall_rating) && (float) $feedback->overall_rating <= 2)->count());

        $overallServiceScoresByStore = FeedbackAnswer::query()
            ->selectRaw('feedbacks.store_id, AVG(feedback_answers.answer_rating) as average_rating, COUNT(feedback_answers.answer_id) as rating_count')
            ->join('feedbacks', 'feedbacks.feedback_id', '=', 'feedback_answers.feedback_id')
            ->join('survey_questions', 'survey_questions.question_id', '=', 'feedback_answers.question_id')
            ->when($user->isAdmin(), fn ($query) => $query->where('feedbacks.store_id', $user->assigned_store_id ?? 0))
            ->when($isSingleStoreView, fn ($query) => $query->where('feedbacks.store_id', $selectedStoreId))
            ->whereBetween('feedbacks.created_at', [$from, $to])
            ->where('survey_questions.applies_to', 'overall_service')
            ->whereNotNull('feedback_answers.answer_rating')
            ->groupBy('feedbacks.store_id')
            ->get()
            ->keyBy('store_id');

        $branchPerformance = $stores
            ->map(function ($store) use ($responseCountsByStore, $overallServiceScoresByStore, $overallRatingsByStore, $recoveryCasesByStore) {
                $serviceStats = $overallServiceScoresByStore->get($store->store_id);
                $average = $serviceStats?->average_rating;
                $overallRatings = $overallRatingsByStore->get($store->store_id, collect());
                $responsesCount = (int) ($responseCountsByStore->get($store->store_id, 0));
                $recoveryCases = (int) ($recoveryCasesByStore->get($store->store_id, 0));

                return [
                    'store_id' => $store->store_id,
                    'name' => $store->name,
                    'store_number' => $store->store_number,
                    'responses_count' => $responsesCount,
                    'average_rating' => !is_null($average) ? round((float) $average, 1) : null,
                    'rating_count' => (int) ($serviceStats?->rating_count ?? 0),
                    'overall_average_rating' => $overallRatings->isNotEmpty() ? round((float) $overallRatings->avg(), 1) : null,
                    'recovery_cases' => $recoveryCases,
                    'positive_rate' => $responsesCount > 0
                        ? round(($overallRatings->filter(fn ($rating) => $rating >= 4)->count() / $responsesCount) * 100, 1)
                        : 0,
                ];
            })
            ->values();

        $topBranch = $branchPerformance
            ->filter(fn ($store) => !is_null($store['average_rating']))
            ->sortBy([
                fn ($store) => -($store['average_rating'] ?? 0),
                fn ($store) => -($store['rating_count'] ?? 0),
                fn ($store) => strtolower($store['name'] ?? ''),
            ])
            ->values()
            ->first();

        $weakestBranch = $branchPerformance
            ->filter(fn ($store) => !is_null($store['average_rating']))
            ->sortBy([
                fn ($store) => ($store['average_rating'] ?? 0),
                fn ($store) => -($store['recovery_cases'] ?? 0),
                fn ($store) => strtolower($store['name'] ?? ''),
            ])
            ->values()
            ->first();

        $topStaffLeaderboard = $this->buildTaggedLeaderboard($feedbackCollection, 'staff');
        $topManagerLeaderboard = $this->buildTaggedLeaderboard($feedbackCollection, 'manager');
        $topStaff = $topStaffLeaderboard->first();
        $topManager = $topManagerLeaderboard->first();

        $ratingTrendDays = collect(range(6, 0))->map(fn ($offset) => now()->copy()->subDays($offset));
        $feedbackByDate = $feedbackCollection->groupBy(
            fn ($feedback) => optional($feedback->created_at)?->toDateString()
        );

        $ratingTrendLabels = $ratingTrendDays
            ->map(fn ($date) => $date->format('M d'))
            ->values();

        $ratingTrendData = $ratingTrendDays
            ->map(function ($date) use ($feedbackByDate) {
                $dayRatings = collect($feedbackByDate->get($date->toDateString(), []))
                    ->pluck('overall_rating')
                    ->filter(fn ($rating) => !is_null($rating))
                    ->map(fn ($rating) => (float) $rating)
                    ->values();

                return $dayRatings->isNotEmpty()
                    ? round((float) $dayRatings->avg(), 1)
                    : 0;
            })
            ->values();

        $responseTrendData = $ratingTrendDays
            ->map(fn ($date) => collect($feedbackByDate->get($date->toDateString(), []))->count())
            ->values();

        $branchDistribution = $branchPerformance
            ->filter(fn ($store) => ($store['responses_count'] ?? 0) > 0)
            ->values();

        $branchDistributionLabels = $branchDistribution->pluck('name')->values();
        $branchDistributionData = $branchDistribution->pluck('responses_count')->values();
        $branchDistributionPalette = collect(['#4c82e6', '#f7a04d', '#6bc39c', '#d870d1', '#ffd166', '#2a9d8f']);
        $branchDistributionColors = $branchDistribution
            ->values()
            ->map(fn ($store, $index) => $branchDistributionPalette[$index % $branchDistributionPalette->count()])
            ->values();
        $branchRankings = $branchPerformance
            ->sortBy([
                fn ($store) => is_null($store['average_rating']) ? 1 : 0,
                fn ($store) => -($store['average_rating'] ?? 0),
                fn ($store) => -($store['responses_count'] ?? 0),
            ])
            ->values();

        $branchLeaderboardChartLabels = $branchRankings
            ->take(6)
            ->pluck('name')
            ->values();
        $branchLeaderboardChartData = $branchRankings
            ->take(6)
            ->pluck('average_rating')
            ->map(fn ($rating) => $rating ?? 0)
            ->values();

        $serviceMixLabels = collect(['Positive', 'Neutral', 'Low'])->values();
        $serviceMixData = collect([$positiveFeedback, $neutralFeedback, $lowFeedback])->values();
        $serviceMixColors = collect(['#16a34a', '#f59e0b', '#ef4444'])->values();

        $teamPerformance = $topStaffLeaderboard
            ->take(3)
            ->map(fn ($person) => array_merge($person, ['role' => 'Staff']))
            ->merge(
                $topManagerLeaderboard
                    ->take(2)
                    ->map(fn ($person) => array_merge($person, ['role' => 'Manager']))
            )
            ->sortBy([
                fn ($person) => is_null($person['average_rating']) ? 1 : 0,
                fn ($person) => -($person['average_rating'] ?? 0),
                fn ($person) => -($person['feedback_count'] ?? 0),
            ])
            ->take(5)
            ->values();

        $teamChartLabels = $teamPerformance
            ->map(fn ($person) => $person['name'] . ' (' . $person['role'] . ')')
            ->values();
        $teamChartData = $teamPerformance
            ->pluck('average_rating')
            ->map(fn ($rating) => $rating ?? 0)
            ->values();

        $positiveRate = $totalFeedback > 0 ? round(($positiveFeedback / $totalFeedback) * 100, 1) : 0;
        $neutralRate = $totalFeedback > 0 ? round(($neutralFeedback / $totalFeedback) * 100, 1) : 0;
        $lowRate = $totalFeedback > 0 ? round(($lowFeedback / $totalFeedback) * 100, 1) : 0;
        $responseRate = $totalStores > 0 ? round(($totalFeedback / max($totalStores, 1)) * 100, 1) : 0;

        $serviceHealthLabel = match (true) {
            is_null($averageRating) => 'No ratings yet',
            $averageRating >= 4.5 && $lowRate <= 10 => 'Excellent service health',
            $averageRating >= 4.0 && $lowRate <= 20 => 'Healthy with minor issues',
            $averageRating >= 3.0 => 'Mixed experience, needs attention',
            default => 'Urgent recovery needed',
        };

        return compact(
            'totalFeedback',
            'averageRating',
            'positiveFeedback',
            'neutralFeedback',
            'lowFeedback',
            'rating5',
            'rating4',
            'rating3',
            'rating2',
            'rating1',
            'recentFeedbacks',
            'topBranch',
            'weakestBranch',
            'topStaff',
            'topManager',
            'topStaffLeaderboard',
            'topManagerLeaderboard',
            'branchPerformance',
            'branchRankings',
            'ratingTrendLabels',
            'ratingTrendData',
            'responseTrendData',
            'branchDistributionLabels',
            'branchDistributionData',
            'branchDistributionColors',
            'branchLeaderboardChartLabels',
            'branchLeaderboardChartData',
            'serviceMixLabels',
            'serviceMixData',
            'serviceMixColors',
            'teamChartLabels',
            'teamChartData',
            'teamPerformance',
            'writtenFeedbackCount',
            'serviceHealthLabel',
            'storeFocusedAverageRating',
            'dateRangeLabel',
            'dateFrom',
            'dateTo',
            'positiveRate',
            'neutralRate',
            'lowRate',
            'responseRate'
        );
    }

    protected function extractTaggedPersonName(Feedback $feedback, string $tag): ?string
    {
        return $this->extractMentionNamesFromFeedback($feedback, $tag)->first();
    }

    protected function extractFeedbackSnippet(Feedback $feedback): string
    {
        if (filled($feedback->overall_comment)) {
            return Str::limit(trim((string) $feedback->overall_comment), 70);
        }

        $commentAnswer = $feedback->answers
            ->first(fn ($answer) => filled($answer->answer_comment));

        if ($commentAnswer) {
            return Str::limit(trim((string) $commentAnswer->answer_comment), 70);
        }

        $textAnswer = $feedback->answers
            ->first(function ($answer) {
                $text = trim((string) ($answer->answer_text ?? ''));
                $appliesTo = $answer->question?->applies_to;

                return $text !== '' && $appliesTo !== 'staff';
            });

        if ($textAnswer) {
            return Str::limit(trim((string) $textAnswer->answer_text), 70);
        }

        return 'No written feedback';
    }

    protected function buildTaggedLeaderboard($feedbackCollection, string $tag)
    {
        return $feedbackCollection
            ->map(function ($feedback) use ($tag) {
                $personNames = $this->extractMentionNamesFromFeedback($feedback, $tag);

                if ($personNames->isEmpty()) {
                    return null;
                }

                $ratings = $feedback->answers
                    ->filter(function ($answer) use ($tag) {
                        $appliesTo = $answer->question?->applies_to;

                        return $appliesTo === $tag && !is_null($answer->answer_rating);
                    })
                    ->pluck('answer_rating')
                    ->map(fn ($rating) => (float) $rating)
                    ->values();

                return $personNames->map(fn ($personName) => [
                    'name' => $personName,
                    'feedback_id' => $feedback->feedback_id,
                    'ratings' => $ratings,
                    'overall_rating' => !is_null($feedback->overall_rating) ? (float) $feedback->overall_rating : null,
                ]);
            })
            ->filter()
            ->flatMap(fn ($entries) => $entries)
            ->groupBy(fn ($entry) => Str::lower(trim((string) $entry['name'])))
            ->map(function ($entries) use ($tag) {
                $ratings = $entries->flatMap(fn ($entry) => $entry['ratings'])->filter(fn ($rating) => !is_null($rating))->values();
                $fallbackRatings = $entries->pluck('overall_rating')->filter(fn ($rating) => !is_null($rating))->values();
                $average = $ratings->isNotEmpty()
                    ? $ratings->avg()
                    : ($fallbackRatings->isNotEmpty() ? $fallbackRatings->avg() : null);

                return [
                    'name' => $this->formatDetectedName($entries->first()['name']),
                    'role' => Str::headline($tag),
                    'average_rating' => !is_null($average) ? round((float) $average, 1) : null,
                    'feedback_count' => $entries->pluck('feedback_id')->unique()->count(),
                    'mention_count' => $entries->count(),
                ];
            })
            ->sortBy([
                fn ($person) => -($person['mention_count'] ?? 0),
                fn ($person) => -($person['feedback_count'] ?? 0),
                fn ($person) => is_null($person['average_rating']) ? 1 : 0,
                fn ($person) => -($person['average_rating'] ?? 0),
                fn ($person) => strtolower($person['name'] ?? ''),
            ])
            ->values();
    }

    protected function extractMentionNamesFromFeedback(Feedback $feedback, string $tag)
    {
        if ($tag === 'staff' && filled($feedback->selected_staff_name)) {
            return collect([
                $this->formatDetectedName((string) $feedback->selected_staff_name),
            ]);
        }

        $answer = $feedback->answers
            ->first(function ($item) use ($tag) {
                if (($item->question?->applies_to ?? null) !== $tag) {
                    return false;
                }

                return filled($item->answer_text) || filled($item->answer_comment);
            });

        if (!$answer) {
            return collect();
        }

        return $this->extractMentionNames($answer->answer_comment, $answer->answer_text);
    }

    protected function extractMentionNames(?string $comment, ?string $text = null)
    {
        $raw = trim((string) ($comment ?: $text ?: ''));

        if ($raw === '') {
            return collect();
        }

        $normalized = preg_replace('/\s+/', ' ', str_replace(["\r\n", "\r"], "\n", $raw));
        $normalized = preg_replace('/\b(staff|manager|name|comment)\s*[:\-]\s*/iu', '', (string) $normalized);
        $segments = preg_split('/[\n,;\/|]+|\s+(?:and|&)\s+/iu', (string) $normalized) ?: [];

        return collect($segments)
            ->map(fn ($segment) => trim((string) $segment))
            ->map(fn ($segment) => preg_replace('/\s+/', ' ', $segment))
            ->filter(function ($segment) {
                if ($segment === '') {
                    return false;
                }

                return preg_match('/[a-z]/iu', $segment) === 1;
            })
            ->map(fn ($segment) => $this->formatDetectedName($segment))
            ->values();
    }

    protected function formatDetectedName(string $name): string
    {
        $cleaned = trim(preg_replace('/\s+/', ' ', $name));

        return Str::of(Str::lower($cleaned))
            ->title()
            ->replaceMatches('/\bNg\b/u', 'ng')
            ->replaceMatches('/\bDe\b/u', 'de')
            ->replaceMatches('/\bDela\b/u', 'Dela')
            ->toString();
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $this->logActivity(
                'auth.logout',
                $user->name . ' signed out of the system.'
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Logged out successfully.');
    }
}
