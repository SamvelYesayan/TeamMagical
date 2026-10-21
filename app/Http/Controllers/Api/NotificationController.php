<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Users can only ever see and change their own notifications. */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = AppNotification::query()->where('user_id', $request->user()->id)->orderByDesc('id');

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        return NotificationResource::collection($query->paginate(min(50, max(1, (int) $request->input('per_page', 20)))));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => AppNotification::query()->where('user_id', $request->user()->id)->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = AppNotification::query()->where('user_id', $request->user()->id)->findOrFail($id);
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return response()->json(['message' => 'OK']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        AppNotification::query()->where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'OK']);
    }
}
