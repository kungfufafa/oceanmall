<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

final class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $page = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        $items = $page->getCollection()->map(static fn (DatabaseNotification $notification): array => [
            'id' => $notification->id,
            'title' => (string) data_get($notification->data, 'title', 'Notifikasi'),
            'body' => (string) data_get($notification->data, 'body', ''),
            'url' => (string) data_get($notification->data, 'url', ''),
            'order_number' => data_get($notification->data, 'order_number'),
            'type' => data_get($notification->data, 'type'),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $items->values()->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'unread' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $record = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();
        $record->markAsRead();

        return response()->json(['message' => 'Dibaca.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }
}
