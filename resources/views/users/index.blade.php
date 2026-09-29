@extends('layouts.app', ['title' => __('messages.user_management')])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.user_management') }}</h1>
                <p class="mt-2 text-sm text-stone-600">{{ __('messages.user_management_help') }}</p>
            </div>
            <a class="inline-flex rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-500" href="{{ route('users.create') }}">
                {{ __('messages.create_user') }}
            </a>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($users as $user)
                <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-stone-900">{{ $user->name }}</h2>
                            <p class="mt-1 text-sm text-stone-500">{{ $user->email }}</p>
                        </div>
                        <a class="rounded-lg border border-stone-200 px-3 py-2 text-xs text-stone-700 transition hover:bg-stone-100" href="{{ route('users.edit', $user) }}">
                            {{ __('messages.edit') }}
                        </a>
                    </div>
                    <div class="mt-4 rounded-2xl bg-stone-50 px-4 py-3 text-sm text-stone-600">
                        {{ $user->roles->pluck('label')->join(', ') }}
                    </div>
                    <div class="mt-4 flex items-center justify-between rounded-2xl border border-stone-200 px-4 py-3">
                        <span class="text-sm text-stone-500">{{ __('messages.unread_notifications') }}</span>
                        <span class="text-lg font-semibold text-stone-900">{{ $user->unread_notifications_count }}</span>
                    </div>
                </article>
            @endforeach
        </div>

        <div>
            {{ $users->links() }}
        </div>
    </div>
@endsection
