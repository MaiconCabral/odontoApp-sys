<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StaffNotificationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /** Alerts shown under the bell; older ones stay in the table. */
    private const LIMIT = 20;

    /** Lists the latest alerts of the signed-in user and how many are unread. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        return StaffNotificationResource::collection($user->notifications()->limit(self::LIMIT)->get())
            ->additional(['meta' => ['unreadCount' => $user->unreadNotifications()->count()]]);
    }

    /** Marks one alert of the signed-in user as read. */
    public function read(Request $request, string $notification): Response
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return response()->noContent();
    }

    /** Marks every alert of the signed-in user as read. */
    public function readAll(Request $request): Response
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
