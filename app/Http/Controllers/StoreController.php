<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithUserAccess;
use App\Http\Controllers\Concerns\LogsActivity;
use App\Models\AppSetting;
use App\Models\SoftwareLicense;
use App\Models\Store;
use App\Models\SurveyQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;

class StoreController extends Controller
{
    use InteractsWithUserAccess, LogsActivity;

    protected function licenseSummary(): array
    {
        $actor = request()->user();
        $license = $actor?->isDev() ? null : $actor?->softwareLicense;
        $startAt = $license?->starts_at?->format('Y-m-d H:i:s') ?? AppSetting::getValue('license_starts_at');
        $endAt = $license?->ends_at?->format('Y-m-d H:i:s') ?? AppSetting::getValue('license_ends_at');
        $maxStores = $license?->max_stores ?? (int) AppSetting::getValue('max_stores', AppSetting::getValue('license_max_stores', '0'));
        $currentStoreCount = $actor?->isSuperAdmin()
            ? Store::query()->where('owner_user_id', $actor->id)->count()
            : Store::count();
        $now = now();
        $startsAt = filled($startAt) ? Carbon::parse($startAt) : null;
        $endsAt = filled($endAt) ? Carbon::parse($endAt) : null;
        $status = SoftwareLicense::resolveStatus($startsAt, $endsAt);

        $canCreate = true;
        $message = null;

        if ($status !== 'active') {
            $canCreate = false;
            $message = 'Adding a new store is disabled because the software license has expired.';
        } elseif ($startsAt && $startsAt->isFuture()) {
            $canCreate = false;
            $message = 'Adding a new store is disabled until the license start date begins.';
        } elseif ($endsAt && $endsAt->lte($now)) {
            $canCreate = false;
            $message = 'Adding a new store is disabled because the software license has expired.';
        } elseif ($maxStores > 0 && $currentStoreCount >= $maxStores) {
            $canCreate = false;
            $message = 'Maximum store limit reached for this license.';
        }

        return [
            'status' => $status,
            'max_stores' => $maxStores,
            'current_store_count' => $currentStoreCount,
            'can_create_store' => $canCreate,
            'message' => $message,
        ];
    }

    protected function availableStoreTypes(): array
    {
        return [
            'Retail',
            'Restaurant',
            'Service Center',
            'Corporate Office',
            'Warehouse',
            'Other',
        ];
    }

    protected function validateStorePayload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'store_manager' => ['nullable', 'string', 'max:255'],
            'store_type' => ['nullable', 'string', 'in:' . implode(',', $this->availableStoreTypes())],
            'store_type_other' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'google_review_url' => ['nullable', 'url'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if (($validated['store_type'] ?? null) === 'Other' && empty(trim((string) ($validated['store_type_other'] ?? '')))) {
            return back()
                ->withErrors(['store_type_other' => 'Please specify the company or business type.'])
                ->withInput()
                ->throwResponse();
        }

        if (($validated['store_type'] ?? null) !== 'Other') {
            $validated['store_type_other'] = null;
        }

        return $validated;
    }

    protected function deleteStoreAssets(Store $store): void
    {
        if ($store->profile_photo_path && Storage::disk('public')->exists($store->profile_photo_path)) {
            Storage::disk('public')->delete($store->profile_photo_path);
        }

        if ($store->qr_code_path && Storage::disk('public')->exists($store->qr_code_path)) {
            Storage::disk('public')->delete($store->qr_code_path);
        }

        if ($store->qr_pdf_path && Storage::disk('public')->exists($store->qr_pdf_path)) {
            Storage::disk('public')->delete($store->qr_pdf_path);
        }
    }

    protected function storeProfilePhoto(Request $request, ?string $existingPath = null): ?string
    {
        if (!$request->hasFile('profile_photo')) {
            return $existingPath;
        }

        if ($existingPath && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        return $request->file('profile_photo')->store('store-photos', 'public');
    }

    protected function buildRankedStores(string $timeframe = 'monthly')
    {
        $fromDate = match ($timeframe) {
            'daily' => Carbon::today(),
            'weekly' => Carbon::now()->subDays(6)->startOfDay(),
            default => Carbon::now()->subDays(29)->startOfDay(),
        };

        return Store::query()
            ->withCount([
                'feedbacks' => fn ($query) => $query->where('created_at', '>=', $fromDate),
            ])
            ->withAvg([
                'feedbacks' => fn ($query) => $query->where('created_at', '>=', $fromDate),
            ], 'overall_rating')
            ->get()
            ->map(function ($store) {
                $store->average_rating = $store->feedbacks_avg_overall_rating
                    ? round((float) $store->feedbacks_avg_overall_rating, 1)
                    : null;

                return $store;
            })
            ->sortByDesc(fn ($store) => $store->average_rating ?? -1)
            ->values()
            ->map(function ($store, $index) {
                $store->rank_position = $store->feedbacks_count > 0 && !is_null($store->average_rating)
                    ? $index + 1
                    : null;

                return $store;
            });
    }

    protected function generateUniqueStoreNumber(): string
    {
        $nextNumber = Store::count() + 1;
        $storeNumber = 'Store-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        while (Store::where('store_number', $storeNumber)->exists()) {
            $nextNumber++;
            $storeNumber = 'Store-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        return $storeNumber;
    }

    protected function generateUniqueSlug(string $name, ?int $excludeStoreId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (Store::where('slug', $slug)
            ->when($excludeStoreId, fn ($query) => $query->where('store_id', '!=', $excludeStoreId))
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function ensureStorageDirectoriesExist(): void
    {
        $qrDir = storage_path('app/public/qr_codes');
        $pdfDir = storage_path('app/public/qr_pdfs');

        if (!File::exists($qrDir)) {
            File::makeDirectory($qrDir, 0755, true);
        }

        if (!File::exists($pdfDir)) {
            File::makeDirectory($pdfDir, 0755, true);
        }
    }

    protected function createQrAssets(Store $store, string $surveyUrl): array
    {
        $qrFileName = 'qr_codes/store_' . $store->store_id . '.svg';
        $qrFullPath = storage_path('app/public/' . $qrFileName);

        QrCode::format('svg')
            ->size(400)
            ->margin(2)
            ->generate($surveyUrl, $qrFullPath);

        $pdfFileName = 'qr_pdfs/store_' . $store->store_id . '.pdf';
        $pdfFullPath = storage_path('app/public/' . $pdfFileName);

        $pdf = Pdf::loadView('stores.qr-pdf', [
            'store' => $store,
            'qrImagePath' => $qrFullPath,
            'surveyUrl' => $surveyUrl,
        ]);
        $pdf->save($pdfFullPath);

        return [$qrFileName, $pdfFileName];
    }

    public function index()
    {
        $stores = $this->accessibleStoresQuery()->latest()->get();
        $questions = SurveyQuestion::orderBy('sort_order')->get();
        $licenseSummary = $this->licenseSummary();

        return view('stores.index', compact('stores', 'questions', 'licenseSummary'));
    }

    public function ranks()
    {
        $timeframe = request('timeframe', 'monthly');
        $stores = $this->buildRankedStores($timeframe)
            ->filter(fn ($store) => in_array((int) $store->store_id, $this->accessibleStoreIds(), true))
            ->values();

        return view('stores.ranks', compact('stores', 'timeframe'));
    }

    public function exportRanksPdf(Request $request)
    {
        $timeframe = $request->input('timeframe', 'monthly');
        $stores = $this->buildRankedStores($timeframe)
            ->filter(fn ($store) => in_array((int) $store->store_id, $this->accessibleStoreIds(), true))
            ->values();

        $pdf = Pdf::loadView('stores.ranks-pdf', [
            'stores' => $stores,
            'timeframe' => $timeframe,
            'generatedAt' => now(),
        ]);

        return $pdf->download('store_ranks_' . $timeframe . '_' . now()->format('Y_m_d_His') . '.pdf');
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->isDev() || $request->user()?->isSuperAdmin(), 403);
        $licenseSummary = $this->licenseSummary();

        if (! $licenseSummary['can_create_store']) {
            return redirect()
                ->route('stores.index')
                ->withErrors(['license' => $licenseSummary['message'] ?? 'Store creation is blocked by the current software license settings.']);
        }

        $validated = $this->validateStorePayload($request);

        $store = Store::create([
            'store_number' => $this->generateUniqueStoreNumber(),
            'name' => $validated['name'],
            'slug' => $this->generateUniqueSlug($validated['name']),
            'store_manager' => $validated['store_manager'] ?? null,
            'store_type' => $validated['store_type'] ?? null,
            'store_type_other' => $validated['store_type_other'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'profile_photo_path' => $this->storeProfilePhoto($request),
            'google_review_url' => $validated['google_review_url'] ?? null,
            'status' => $validated['status'],
            'owner_user_id' => $request->user()?->isSuperAdmin() ? $request->user()->id : null,
        ]);

        AppSetting::resetPublishedSurveyForNewEntity();

        $surveyUrl = route('survey.form', $store->slug);
        $this->ensureStorageDirectoriesExist();

        [$qrFileName, $pdfFileName] = $this->createQrAssets($store, $surveyUrl);

        $store->update([
            'qr_code_path' => $qrFileName,
            'qr_pdf_path' => $pdfFileName,
            'qr_url' => $surveyUrl,
        ]);

        $this->logActivity(
            'stores.create',
            'Created store ' . $store->name . '.',
            [
                'subject' => $store,
                'store' => $store,
            ]
        );

        return redirect()->route('stores.index')->with('success', 'Store created successfully.');
    }

    public function update(Request $request, Store $store)
    {
        $this->ensureStoreAccess($store);

        $validated = $this->validateStorePayload($request);

        $store->update([
            'name' => $validated['name'],
            'slug' => $this->generateUniqueSlug($validated['name'], $store->store_id),
            'store_manager' => $validated['store_manager'] ?? null,
            'store_type' => $validated['store_type'] ?? null,
            'store_type_other' => $validated['store_type_other'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'profile_photo_path' => $this->storeProfilePhoto($request, $store->profile_photo_path),
            'google_review_url' => $validated['google_review_url'] ?? null,
            'status' => $validated['status'],
        ]);

        $this->logActivity(
            'stores.update',
            'Updated store ' . $store->name . '.',
            [
                'subject' => $store,
                'store' => $store,
            ]
        );

        return redirect()->route('stores.index')->with('success', 'Store updated successfully.');
    }

    public function destroy(Store $store)
    {
        abort_unless(request()->user()?->isDev() || request()->user()?->isSuperAdmin(), 403);
        $this->ensureStoreAccess($store);

        $this->deleteStoreAssets($store);
        $deletedStoreName = $store->name;
        $deletedStoreId = $store->store_id;
        $store->delete();

        $this->logActivity(
            'stores.delete',
            'Deleted store ' . $deletedStoreName . '.',
            [
                'subject_type' => Store::class,
                'subject_id' => (string) $deletedStoreId,
                'store_id' => $deletedStoreId,
            ]
        );

        return redirect()->route('stores.index')->with('success', 'Store deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        abort_unless($request->user()?->isDev() || $request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'store_ids' => ['required', 'array', 'min:1'],
            'store_ids.*' => ['integer', 'exists:stores,store_id'],
        ]);

        $stores = Store::whereIn('store_id', $validated['store_ids'])
            ->whereIn('store_id', $this->accessibleStoreIds())
            ->get();

        $stores->each(function (Store $store) {
            $this->deleteStoreAssets($store);
            $store->delete();
        });

        $deletedCount = $stores->count();

        if ($deletedCount > 0) {
            $this->logActivity(
                'stores.bulk_delete',
                'Bulk deleted ' . $deletedCount . ' store records.',
                [
                    'metadata' => [
                        'store_ids' => $validated['store_ids'],
                    ],
                ]
            );
        }

        return redirect()
            ->route('stores.index')
            ->with('success', $deletedCount === 1 ? '1 store deleted successfully.' : $deletedCount . ' stores deleted successfully.');
    }
}


