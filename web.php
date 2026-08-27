<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\RenewalRequestController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreStaffController;
use App\Http\Controllers\SurveyQuestionController;
use App\Http\Controllers\SoftwareLicenseController;
use App\Http\Controllers\UserManagementController;
use App\Models\AppSetting;
use App\Models\Store;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/renewal-requests', [RenewalRequestController::class, 'store'])->name('renewal-requests.store');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::middleware(['auth', 'license'])->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->middleware('feature:dashboard')->name('dashboard');
    Route::get('/dashboard/export', [AuthController::class, 'dashboardExport'])->middleware('feature:dashboard')->name('dashboard.export');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:dev,super_admin')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        Route::get('/history', [ActivityLogController::class, 'index'])->name('history.index');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/branding', [BrandingController::class, 'index'])->name('branding.index');
        Route::put('/branding', [BrandingController::class, 'update'])->name('branding.update');
    });

    Route::middleware('role:dev')->group(function () {
        Route::get('/licensing', [SoftwareLicenseController::class, 'index'])->name('licensing.index');
        Route::post('/licensing/generate-key', [SoftwareLicenseController::class, 'refreshKey'])->name('licensing.generate-key');
        Route::post('/licensing/registry', [SoftwareLicenseController::class, 'store'])->name('licensing.registry.store');
        Route::put('/licensing/registry/{softwareLicense}', [SoftwareLicenseController::class, 'updateRegistry'])->name('licensing.registry.update');
        Route::post('/licensing/registry/{softwareLicense}/activate', [SoftwareLicenseController::class, 'activate'])->name('licensing.registry.activate');
        Route::put('/licensing', [SoftwareLicenseController::class, 'update'])->name('licensing.update');
        Route::put('/renewal-requests/{renewalRequest}', [RenewalRequestController::class, 'update'])->name('renewal-requests.update');
    });

    // STORES
    Route::middleware('feature:stores')->group(function () {
        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::get('/stores/ranks', [StoreController::class, 'ranks'])->name('stores.ranks');
        Route::get('/stores/ranks/export/pdf', [StoreController::class, 'exportRanksPdf'])->name('stores.ranks.export.pdf');
        Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
        Route::delete('/stores/bulk-delete', [StoreController::class, 'bulkDestroy'])->name('stores.bulk-destroy');
        Route::put('/stores/{store}', [StoreController::class, 'update'])->name('stores.update');
        Route::delete('/stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');
    });

    Route::middleware('feature:staff')->group(function () {
        Route::get('/stores/{store}/staff', [StoreStaffController::class, 'index'])->name('stores.staff.index');
        Route::get('/stores/{store}/staff/export/pdf', [StoreStaffController::class, 'exportPdf'])->name('stores.staff.export.pdf');
        Route::post('/stores/{store}/staff', [StoreStaffController::class, 'store'])->name('stores.staff.store');
        Route::put('/stores/{store}/staff/{staff}', [StoreStaffController::class, 'update'])->name('stores.staff.update');
        Route::delete('/stores/{store}/staff/{staff}', [StoreStaffController::class, 'destroy'])->name('stores.staff.destroy');
    });

    Route::middleware('feature:analytics')->group(function () {
        Route::get('/stores/{store}/staff/analytics', [StoreStaffController::class, 'analytics'])->name('stores.staff.analytics');
        Route::get('/stores/{store}/staff/analytics/export/pdf', [StoreStaffController::class, 'exportAnalyticsPdf'])->name('stores.staff.analytics.export.pdf');
        Route::get('/stores/{store}/staff/overall-analytics', [StoreStaffController::class, 'overallAnalytics'])->name('stores.staff.overall-analytics');
    });

    // QUESTIONS
    Route::middleware('feature:questions')->group(function () {
        Route::get('/questions', [SurveyQuestionController::class, 'index'])->name('questions.index');
        Route::put('/questions/settings', [SurveyQuestionController::class, 'updateSettings'])->name('questions.settings.update');
        Route::post('/questions/sync-public', [SurveyQuestionController::class, 'syncPublic'])->name('questions.sync-public');
        Route::post('/questions', [SurveyQuestionController::class, 'store'])->name('questions.store');
        Route::put('/questions/{surveyQuestion}', [SurveyQuestionController::class, 'update'])->name('questions.update');
        Route::delete('/questions/{surveyQuestion}', [SurveyQuestionController::class, 'destroy'])->name('questions.destroy');
    });

    // FEEDBACKS
    Route::middleware('feature:feedbacks')->group(function () {
        Route::get('/feedbacks', [FeedbackController::class, 'index'])->name('feedbacks.index');
        Route::put('/feedbacks/{feedback}/resolution', [FeedbackController::class, 'updateResolution'])->name('feedbacks.resolution');
        Route::put('/feedbacks/{feedback}/reply', [FeedbackController::class, 'reply'])->name('feedbacks.reply');
        Route::put('/feedbacks/{feedback}/reward', [FeedbackController::class, 'updateReward'])->name('feedbacks.reward');
        Route::delete('/feedbacks/{feedback}', [FeedbackController::class, 'destroy'])->name('feedbacks.destroy');
    });

    Route::post('/notifications/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [AdminNotificationController::class, 'markAsRead'])->name('notifications.read');
});

Route::post('/feedback/submit', [FeedbackController::class, 'submit'])->name('feedback.submit');
Route::get('/feedback/thank-you/{feedback}', [FeedbackController::class, 'thankYou'])->name('stores.thank-you');
Route::post('/feedback/{feedback}/claim-review', [FeedbackController::class, 'claimReviewReward'])->name('feedbacks.claim-review');
/*
|--------------------------------------------------------------------------
| PUBLIC SURVEY
|--------------------------------------------------------------------------
|
| Ito ang scan ng QR code ng customer.
| Public ito, hindi kailangan naka-login.
|
*/

Route::get('/survey/{store:slug}', function (Store $store) {
    $licenseState = AppSetting::licenseStateForStore($store);

    if (! $licenseState['is_valid']) {
        return response()
            ->view('survey.unavailable', [
                'store' => $store,
                'message' => $licenseState['message'],
            ], 423);
    }

    $questionnaireTitle = AppSetting::getValue(
        'published_questionnaire_title',
        AppSetting::getValue('questionnaire_title', 'Feedback Survey')
    );
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

            return (object) array_merge($question, [
                'question_id' => $question['source_question_id'] ?? null,
                'type' => $questionType,
                'options' => collect($question['options'] ?? [])->filter()->values()->all(),
                'applies_to' => ($question['applies_to'] ?? 'overall_service') === 'general'
                    ? 'overall_service'
                    : ($question['applies_to'] ?? 'overall_service'),
                'allow_comment' => (bool) ($question['allow_comment'] ?? false),
                'allow_attachment' => (bool) ($question['allow_attachment'] ?? false),
            ]);
        });

    $activeStaff = $store->staffMembers()
        ->where('status', 'active')
        ->get()
        ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
        ->values();

    $staffOptions = $activeStaff
        ->filter(fn ($staff) => strtolower(trim((string) ($staff->role ?? ''))) === 'staff')
        ->values();

    $managerOptions = $activeStaff
        ->filter(fn ($staff) => strtolower(trim((string) ($staff->role ?? ''))) === 'manager')
        ->values();

    return view('survey.index', compact(
        'store',
        'questionnaireTitle',
        'questions',
        'staffOptions',
        'managerOptions'
    ));
})->name('survey.form');

Route::post('/survey/{store:slug}/transaction-check', [FeedbackController::class, 'checkTransactionNumber'])->name('survey.transaction-check');
Route::post('/survey/{store:slug}/submit', [FeedbackController::class, 'store'])->name('survey.submit');
