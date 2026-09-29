@extends('layouts.app', ['title' => __('messages.notifications')])

@section('content')
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ $notification->title }}</h1>
                <p class="mt-2 text-sm text-stone-500">{{ $notification->created_at?->format('Y-m-d H:i') }}</p>
            </div>
            <a class="rounded-xl border border-stone-200 px-4 py-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100" href="{{ route('notifications.index') }}">{{ __('messages.back') }}</a>
        </div>

        <div class="rounded-3xl border border-stone-200 bg-white p-6 text-sm leading-7 text-stone-700 shadow-sm">
            {{ $notification->body }}
        </div>
    </div>
@endsection
