<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Concerns\InteractsWithUserAccess;
use App\Http\Controllers\Concerns\LogsActivity;
use App\Models\Feedback;
use App\Models\Store;
use App\Models\StoreStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreStaffController extends Controller
{
    use InteractsWithUserAccess, LogsActivity;

    public function index(Store $store)
    {
        $this->ensureStoreAccess($store);

        $staffMembers = $store->staffMembers()
            ->latest('created_at')
            ->get();

        return view('stores.staff', [
            'store' => $store,
            'staffMembers' => $staffMembers,
            'staffCount' => $staffMembers->count(),
            'activeStaffCount' => $staffMembers->where('status', 'active')->count(),
            'inactiveStaffCount' => $staffMembers->where('status', 'inactive')->count(),
        ]);
    }

    public function analytics(Request $request, Store $store)
    {
        $this->ensureStoreAccess($store);

        [$targetStore, $selectedStoreId, $selectedStoreLabel] = $this->resolveAnalyticsStoreContext($request, $store);
        [$feedbacks, $staffAnalytics, $managerAnalytics, $averageStaffRating, $averageManagerRating] = $this->buildAnalyticsPayload($request, $targetStore);
        $topStaff = $staffAnalytics->first();
        $topManager = $managerAnalytics->first();
        $leastStaff = $staffAnalytics->sortBy([
            fn ($item) => $item['mention_count'] ?? PHP_INT_MAX,
            fn ($item) => strtolower((string) ($item['name'] ?? '')),
        ])->first();
        $leastManager = $managerAnalytics->sortBy([
            fn ($item) => $item['mention_count'] ?? PHP_INT_MAX,
            fn ($item) => strtolower((string) ($item['name'] ?? '')),
        ])->first();

        return view('stores.staff-analytics', [
            'store' => $store,
            'stores' => $this->accessibleStores()->map->only(['store_id', 'name']),
            'selectedStoreId' => $selectedStoreId,
            'selectedStoreLabel' => $selectedStoreLabel,
            'staffAnalytics' => $staffAnalytics,
            'managerAnalytics' => $managerAnalytics,
            'totalResponses' => $feedbacks->count(),
            'totalEmployees' => $targetStore
                ? $targetStore->staffMembers()->count()
                : StoreStaff::count(),
            'staffCount' => $staffAnalytics->count(),
            'managerCount' => $managerAnalytics->count(),
            'topStaff' => $topStaff,
            'topManager' => $topManager,
            'leastStaff' => $leastStaff,
            'leastManager' => $leastManager,
            'averageStaffRating' => $averageStaffRating,
            'averageManagerRating' => $averageManagerRating,
        ]);
    }

    public function exportAnalyticsPdf(Request $request, Store $store)
    {
        $this->ensureStoreAccess($store);

        [$targetStore, $selectedStoreId, $selectedStoreLabel] = $this->resolveAnalyticsStoreContext($request, $store);
        [$feedbacks, $staffAnalytics, $managerAnalytics, $averageStaffRating, $averageManagerRating] = $this->buildAnalyticsPayload($request, $targetStore);

        $search = Str::lower(trim((string) $request->input('search', '')));

        if ($search !== '') {
            $staffAnalytics = $staffAnalytics
                ->filter(fn ($item) => Str::contains(Str::lower((string) ($item['name'] ?? '')), $search))
                ->values();

            $managerAnalytics = $managerAnalytics
                ->filter(fn ($item) => Str::contains(Str::lower((string) ($item['name'] ?? '')), $search))
                ->values();
        }

        $pdf = Pdf::loadView('stores.staff-analytics-pdf', [
            'store' => $store,
            'selectedStoreLabel' => $selectedStoreLabel,
            'staffAnalytics' => $staffAnalytics,
            'managerAnalytics' => $managerAnalytics,
            'totalResponses' => $feedbacks->count(),
            'totalEmployees' => $targetStore ? $targetStore->staffMembers()->count() : StoreStaff::count(),
            'topStaff' => $staffAnalytics->first(),
            'topManager' => $managerAnalytics->first(),
            'leastStaff' => $staffAnalytics->sortBy([
                fn ($item) => $item['mention_count'] ?? PHP_INT_MAX,
                fn ($item) => strtolower((string) ($item['name'] ?? '')),
            ])->first(),
            'leastManager' => $managerAnalytics->sortBy([
                fn ($item) => $item['mention_count'] ?? PHP_INT_MAX,
                fn ($item) => strtolower((string) ($item['name'] ?? '')),
            ])->first(),
            'averageStaffRating' => $averageStaffRating,
            'averageManagerRating' => $averageManagerRating,
            'generatedAt' => now(),
            'search' => $request->input('search', ''),
        ]);

        return $pdf->download('staff_analytics_' . Str::slug($selectedStoreLabel) . '_' . now()->format('Y_m_d_His') . '.pdf');
    }

    public function overallAnalytics(Request $request, Store $store)
    {
        $this->ensureStoreAccess($store);

        [$feedbacks, $staffAnalytics, $managerAnalytics, $averageStaffRating, $averageManagerRating] = $this->buildAnalyticsPayload($request, $store);

        $topStaff = $staffAnalytics->first();
        $topManager = $managerAnalytics->first();

        return view('stores.staff-overall-analytics', [
            'store' => $store,
            'staffAnalytics' => $staffAnalytics,
            'managerAnalytics' => $managerAnalytics,
            'topStaff' => $topStaff,
            'topManager' => $topManager,
            'totalResponses' => $feedbacks->count(),
            'staffCount' => $staffAnalytics->count(),
            'managerCount' => $managerAnalytics->count(),
            'averageStaffRating' => $averageStaffRating,
            'averageManagerRating' => $averageManagerRating,
        ]);
    }

    public function store(Request $request, Store $store)
    {
        $this->ensureStoreAccess($store);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo_path'] = $request->file('profile_photo')->store('staff-photos', 'public');
        }

        $staff = $store->staffMembers()->create($validated);

        $this->logActivity(
            'staff.create',
            'Added employee ' . $staff->name . ' to ' . $store->name . '.',
            [
                'subject' => $staff,
                'store' => $store,
            ]
        );

        return redirect()
            ->route('stores.staff.index', $store)
            ->with('success', 'Employee added successfully.');
    }

    public function update(Request $request, Store $store, StoreStaff $staff)
    {
        $this->ensureStoreAccess($store);
        abort_unless((int) $staff->store_id === (int) $store->store_id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($staff->profile_photo_path && Storage::disk('public')->exists($staff->profile_photo_path)) {
                Storage::disk('public')->delete($staff->profile_photo_path);
            }

            $validated['profile_photo_path'] = $request->file('profile_photo')->store('staff-photos', 'public');
        }

        $staff->update($validated);

        $this->logActivity(
            'staff.update',
            'Updated employee ' . $staff->name . ' in ' . $store->name . '.',
            [
                'subject' => $staff,
                'store' => $store,
            ]
        );

        return redirect()
            ->route('stores.staff.index', $store)
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Store $store, StoreStaff $staff)
    {
        $this->ensureStoreAccess($store);
        abort_unless((int) $staff->store_id === (int) $store->store_id, 404);

        if ($staff->profile_photo_path && Storage::disk('public')->exists($staff->profile_photo_path)) {
            Storage::disk('public')->delete($staff->profile_photo_path);
        }

        $staffName = $staff->name;
        $staffId = $staff->staff_id;
        $staff->delete();

        $this->logActivity(
            'staff.delete',
            'Removed employee ' . $staffName . ' from ' . $store->name . '.',
            [
                'subject_type' => StoreStaff::class,
                'subject_id' => (string) $staffId,
                'store' => $store,
            ]
        );

        return redirect()
            ->route('stores.staff.index', $store)
            ->with('success', 'Employee removed successfully.');
    }

    public function exportPdf(Request $request, Store $store)
    {
        $this->ensureStoreAccess($store);

        $search = trim((string) $request->input('search', ''));
        $role = Str::lower(trim((string) $request->input('role', '')));
        $showing = (string) $request->input('showing', '25');

        $staffMembers = $store->staffMembers()
            ->latest('created_at')
            ->get()
            ->filter(function (StoreStaff $staff) use ($search, $role) {
                $searchable = Str::lower(trim(implode(' ', array_filter([
                    $staff->name,
                    $staff->email,
                    $staff->phone,
                    $staff->role,
                    $staff->status,
                ]))));

                $matchesSearch = $search === '' || Str::contains($searchable, Str::lower($search));
                $matchesRole = $role === '' || Str::lower((string) $staff->role) === $role;

                return $matchesSearch && $matchesRole;
            })
            ->values();

        if ($showing !== 'all') {
            $limit = (int) $showing;
            if ($limit > 0) {
                $staffMembers = $staffMembers->take($limit)->values();
            }
        }

        $pdf = Pdf::loadView('stores.staff-pdf', [
            'store' => $store,
            'staffMembers' => $staffMembers,
            'generatedAt' => now(),
            'search' => $search,
            'role' => $role,
            'showing' => $showing,
        ]);

        return $pdf->download('employees_' . Str::slug($store->name) . '_' . now()->format('Y_m_d_His') . '.pdf');
    }

    protected function buildAnalyticsPayload(Request $request, ?Store $store): array
    {
        $feedbacks = Feedback::with(['answers.question'])
            ->when($store, fn ($query) => $query->where('store_id', $store->store_id))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('date_to')))
            ->latest()
            ->get();

        $staffAnalytics = $this->buildTaggedAnalytics($feedbacks, 'staff');
        $managerAnalytics = $this->buildTaggedAnalytics($feedbacks, 'manager');

        $averageStaffRating = $staffAnalytics
            ->pluck('average_rating')
            ->filter(fn ($rating) => !is_null($rating))
            ->avg();

        $averageManagerRating = $managerAnalytics
            ->pluck('average_rating')
            ->filter(fn ($rating) => !is_null($rating))
            ->avg();

        return [
            $feedbacks,
            $staffAnalytics,
            $managerAnalytics,
            !is_null($averageStaffRating) ? round((float) $averageStaffRating, 1) : null,
            !is_null($averageManagerRating) ? round((float) $averageManagerRating, 1) : null,
        ];
    }

    protected function resolveAnalyticsStoreContext(Request $request, Store $fallbackStore): array
    {
        $accessibleStores = $this->accessibleStores();
        $selectedStoreId = (string) $request->input('store_filter', (string) $fallbackStore->store_id);

        if ($selectedStoreId === 'all' && !$this->currentUser()->isAdmin()) {
            return [null, 'all', 'All Stores'];
        }

        $targetStore = $accessibleStores->firstWhere('store_id', (int) $selectedStoreId) ?? $fallbackStore;

        return [$targetStore, (string) $targetStore->store_id, $targetStore->name];
    }

    protected function buildTaggedAnalytics(Collection $feedbacks, string $tag): Collection
    {
        return $feedbacks
            ->flatMap(function (Feedback $feedback) use ($tag) {
                $answer = $feedback->answers->first(
                    function ($item) use ($tag) {
                        if (($item->question?->applies_to ?? null) !== $tag) {
                            return false;
                        }

                        return filled($item->answer_text) || filled($item->answer_comment);
                    }
                );

                $mentions = $tag === 'staff' && filled($feedback->selected_staff_name)
                    ? collect([$this->formatDetectedName((string) $feedback->selected_staff_name)])
                    : ($answer
                        ? $this->extractMentionNames($answer->answer_comment, $answer->answer_text)
                        : collect());

                if ($mentions->isEmpty()) {
                    return [];
                }

                $ratings = $feedback->answers
                    ->filter(fn ($item) => $item->question?->applies_to === $tag && !is_null($item->answer_rating))
                    ->pluck('answer_rating')
                    ->map(fn ($rating) => (float) $rating)
                    ->values();

                $commentText = trim((string) ($answer->answer_comment ?? ''));

                return $mentions->map(function ($mentionName) use ($feedback, $ratings, $commentText) {
                    return [
                        'name' => $mentionName,
                        'feedback_id' => $feedback->feedback_id,
                        'ratings' => $ratings,
                        'overall_rating' => !is_null($feedback->overall_rating) ? (float) $feedback->overall_rating : null,
                        'has_comment' => $commentText !== '',
                    ];
                });
            })
            ->groupBy(fn ($entry) => Str::lower(trim((string) $entry['name'])))
            ->map(function (Collection $entries) {
                $ratings = $entries
                    ->flatMap(fn ($entry) => $entry['ratings'])
                    ->filter(fn ($rating) => !is_null($rating))
                    ->values();

                $fallbackRatings = $entries
                    ->pluck('overall_rating')
                    ->filter(fn ($rating) => !is_null($rating))
                    ->values();

                $average = $ratings->isNotEmpty()
                    ? $ratings->avg()
                    : ($fallbackRatings->isNotEmpty() ? $fallbackRatings->avg() : null);

                return [
                    'name' => $this->formatDetectedName($entries->first()['name']),
                    'mention_count' => $entries->count(),
                    'comment_count' => $entries->where('has_comment', true)->pluck('feedback_id')->unique()->count(),
                    'rating_count' => $ratings->count(),
                    'feedback_count' => $entries->pluck('feedback_id')->unique()->count(),
                    'average_rating' => !is_null($average) ? round((float) $average, 1) : null,
                ];
            })
            ->sortBy([
                fn ($item) => -($item['mention_count'] ?? 0),
                fn ($item) => -($item['comment_count'] ?? 0),
                fn ($item) => is_null($item['average_rating']) ? 1 : 0,
                fn ($item) => -($item['average_rating'] ?? 0),
                fn ($item) => strtolower($item['name']),
            ])
            ->values();
    }

    protected function extractMentionNames(?string $comment, ?string $text = null): Collection
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
}
