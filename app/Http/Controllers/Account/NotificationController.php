<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

final class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20)
            ->through(fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'title' => (string) data_get($notification->data, 'title', 'Notifikasi'),
                'body' => (string) data_get($notification->data, 'body', ''),
                'url' => (string) data_get($notification->data, 'url', '#'),
                'order_number' => data_get($notification->data, 'order_number'),
                'type' => data_get($notification->data, 'type'),
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ]);

        return Inertia::render('account/notifications', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $record->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Semua notifikasi ditandai sudah dibaca.',
        ]);

        return back();
    }
}
