<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->notifications()->latest();
        $status = $request->string('status')->value() ?: 'unread';

        if ($status === 'unread') {
            $query->whereNull('read_at');
        }

        if ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        return view('notifications.index', [
            'notifications' => $query->paginate(15)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Request $request, AppNotification $notification): View|RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (! $notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }

        if ($notification->resolvedUrl()) {
            return redirect($notification->resolvedUrl());
        }

        return view('notifications.show', [
            'notification' => $notification,
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return redirect()
            ->route('notifications.index')
            ->with('status', __('messages.notifications_marked_read'));
    }
}
