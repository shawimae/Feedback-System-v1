<?php

namespace App\Providers;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.admin', function ($view) {
            if (!Auth::check()) {
                return;
            }

            $user = Auth::user();

            $adminNotificationsQuery = NotificationLog::query()
                ->with(['feedback.store'])
                ->where('channel', 'admin')
                ->when(
                    $user?->isAdmin(),
                    fn ($query) => $query->whereHas('feedback', fn ($feedbackQuery) => $feedbackQuery->where('store_id', $user->assigned_store_id ?? 0))
                );

            $adminNotifications = $adminNotificationsQuery
                ->latest()
                ->take(6)
                ->get();

            $view->with('adminNotifications', $adminNotifications);
            $view->with(
                'unreadAdminNotificationsCount',
                NotificationLog::query()
                    ->where('channel', 'admin')
                    ->where('is_read', false)
                    ->when(
                        $user?->isAdmin(),
                        fn ($query) => $query->whereHas('feedback', fn ($feedbackQuery) => $feedbackQuery->where('store_id', $user->assigned_store_id ?? 0))
                    )
                    ->count()
            );
        });
    }
}
