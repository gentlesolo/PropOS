<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileNotificationController extends Controller
{
    /**
     * Paginated notification history for the authenticated agent.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', 'App\\Infrastructure\\Persistence\\Models\\User')
            ->latest()
            ->paginate($request->input('per_page', 30));

        return response()->json($notifications);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = Notification::where('notifiable_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['read' => true]);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('notifiable_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * Unread count — used for the badge on the tab icon.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('notifiable_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}
