<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Services\Notification\UserNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private UserNotificationService $notifications) {}

    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->userNotifications()
            ->latest()
            ->paginate(15);

        return view('notifications.index', [
            'notifications' => $notifications,
            'unread' => $this->notifications->unreadCount($request->user()),
        ]);
    }

    public function read(Request $request, UserNotification $notification): RedirectResponse
    {
        Gate::authorize('update', $notification);

        $this->notifications->markRead($notification);

        $target = $notification->link ?: route('notifications.index');

        return redirect()->to($target);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->notifications->markAllRead($request->user());

        return back()->with('status', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread' => $this->notifications->unreadCount($request->user()),
        ]);
    }
}
