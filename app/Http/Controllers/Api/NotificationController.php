<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /**
     * List the authenticated user's notifications with an unread count.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()->paginate(20);

        return response()->json([
            'data' => collect($notifications->items())->map(fn ($notification): array => [
                'id' => $notification->id,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                ...$notification->data,
            ]),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a single notification (belonging to the user) as read.
     */
    public function markRead(Request $request, string $id): Response
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->noContent();
    }

    /**
     * Mark all of the user's notifications as read.
     */
    public function markAllRead(Request $request): Response
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->noContent();
    }
}
