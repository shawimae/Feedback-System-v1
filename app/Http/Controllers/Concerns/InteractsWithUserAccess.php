<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait InteractsWithUserAccess
{
    protected function currentUser(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    protected function accessibleStoresQuery(?User $user = null): Builder
    {
        $user ??= $this->currentUser();

        return Store::query()
            ->when(
                $user->isSuperAdmin(),
                fn (Builder $query) => $query->where('owner_user_id', $user->id)
            )
            ->when(
                $user->isAdmin(),
                fn (Builder $query) => $query->where('store_id', $user->assigned_store_id ?? 0)
            );
    }

    protected function accessibleStores(?User $user = null): Collection
    {
        return $this->accessibleStoresQuery($user)
            ->orderBy('name')
            ->get();
    }

    protected function accessibleStoreIds(?User $user = null): array
    {
        return $this->accessibleStoresQuery($user)->pluck('store_id')->all();
    }

    protected function ensureStoreAccess(Store $store, ?User $user = null): void
    {
        abort_unless(in_array((int) $store->store_id, $this->accessibleStoreIds($user), true), 403);
    }

    protected function scopeStoreIdQuery(Builder $query, string $column = 'store_id', ?User $user = null): Builder
    {
        $user ??= $this->currentUser();

        if ($user->isAdmin()) {
            $query->where($column, $user->assigned_store_id ?? 0);
        }

        return $query;
    }

    protected function normalizeSelectedStoreId(mixed $requestedStoreId, Collection $stores, bool $allowAll = true): ?string
    {
        $requested = $requestedStoreId !== null ? (string) $requestedStoreId : null;
        $firstStoreId = $stores->first() ? (string) $stores->first()->store_id : null;

        if ($requested === null || $requested === '') {
            return $allowAll ? null : $firstStoreId;
        }

        if ($allowAll && $requested === 'all') {
            return 'all';
        }

        return $stores->contains('store_id', (int) $requested)
            ? $requested
            : ($allowAll ? null : $firstStoreId);
    }
}
