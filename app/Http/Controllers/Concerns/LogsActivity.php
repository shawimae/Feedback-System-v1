<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Throwable;

trait LogsActivity
{
    protected function logActivity(string $action, string $description, array $context = []): void
    {
        $request = request();
        $user = $request?->user();
        $subject = $context['subject'] ?? null;
        $store = $context['store'] ?? null;

        try {
            ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'user_role' => $user?->role,
                'action' => $action,
                'description' => $description,
                'store_id' => $store?->store_id ?? $context['store_id'] ?? null,
                'subject_type' => $subject instanceof Model ? $subject::class : ($context['subject_type'] ?? null),
                'subject_id' => $subject instanceof Model ? ($subject->getKey() ?? null) : ($context['subject_id'] ?? null),
                'metadata' => $context['metadata'] ?? null,
                'route_name' => $request?->route()?->getName(),
                'method' => $request?->method(),
                'ip_address' => $request?->ip(),
            ]);
        } catch (Throwable) {
            // Keep the main request successful even if audit logging fails.
        }
    }
}
