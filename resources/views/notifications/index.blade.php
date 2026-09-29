@extends('layouts.app', ['title' => __('messages.notifications')])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.notifications') }}</h1>
                <p class="mt-2 text-sm text-stone-600">{{ __('messages.notifications_help') }}</p>
            </div>
            <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                @csrf
                <button class="rounded-lg border border-stone-200 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-100" type="submit">{{ __('messages.mark_all_read') }}</button>
            </form>
        </div>

        <div class="quick-filter">
            <a class="{{ $status === 'unread' ? 'active' : '' }}" href="{{ route('notifications.index', ['status' => 'unread']) }}">{{ __('messages.unread_notifications') }}</a>
            <a class="{{ $status === 'read' ? 'active' : '' }}" href="{{ route('notifications.index', ['status' => 'read']) }}">{{ __('messages.read_notifications') }}</a>
        </div>

        <div class="grid gap-4">
            @forelse ($notifications as $notification)
                <div class="notification-card rounded-2xl border p-4 shadow-sm transition hover:bg-white {{ $notification->isRead() ? 'opacity-80' : '' }}" data-notification-link="{{ route('notifications.show', $notification) }}" data-tone="{{ $notification->tone() }}">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="flex items-start gap-4">
                            <span class="notification-icon">
                                <i class="{{ $notification->icon() }}"></i>
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-semibold text-stone-900">{{ $notification->title }}</h2>
                                    <span class="rounded-full border border-stone-200 px-2 py-1 text-[11px] font-semibold text-stone-500">{{ $notification->isRead() ? __('messages.read_notifications') : __('messages.unread_notifications') }}</span>
                                </div>
                                <p class="mt-2 text-sm text-stone-600">{{ $notification->body }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col gap-3 md:items-end">
                            <span class="text-xs text-stone-500">{{ $notification->created_at?->format('Y-m-d H:i') }}</span>
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($notification->requiresAction() && $notification->resolvedUrl())
                                    <a class="rounded-lg bg-rose-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-600" data-notification-action href="{{ route('notifications.show', $notification) }}">{{ $notification->actionLabel() }}</a>
                                @endif
                                <a class="rounded-lg border border-stone-200 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-100" data-notification-action href="{{ route('notifications.show', $notification) }}">{{ __('messages.view_detail') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-stone-200 bg-white p-8 text-sm text-stone-500 shadow-sm">
                    {{ __('messages.no_notifications') }}
                </div>
            @endforelse
        </div>

        {{ $notifications->links() }}
    </div>
@endsection
