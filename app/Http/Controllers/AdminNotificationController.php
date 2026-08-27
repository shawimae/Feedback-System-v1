<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsActivity;
use App\Models\NotificationLog;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    use LogsActivity;

    public function markAsRead(Request $request, NotificationLog $notification)
    {
        if ($notification->channel === 'admin' && !$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            $this->logActivity(
                'notifications.read',
                'Marked one admin notification as read.'
            );
        }

        return redirect()->back();
    }

    public function markAllAsRead(Request $request)
    {
        NotificationLog::query()
            ->where('channel', 'admin')
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $this->logActivity(
            'notifications.read_all',
            'Marked all admin notifications as read.'
        );

        return redirect()->back();
    }
}
