@extends('layouts.app', ['title' => __('messages.login')])

@section('content')
    <div class="login-shell">
        <section class="login-hero">
            <div class="max-w-xl">
                <p class="login-eyebrow">{{ __('messages.app_name') }}</p>
                <h1 class="login-title">{{ __('messages.welcome_back') }}</h1>
                <p class="login-copy">{{ __('messages.sign_in_message') }}</p>
            </div>
        </section>

        <section class="login-panel">
            <div class="mb-6 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.login') }}</h2>
                    <p class="mt-1 text-sm text-stone-500">{{ __('messages.language') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('language.switch', 'id') }}">
                        @csrf
                        <button class="login-locale-button {{ app()->getLocale() === 'id' ? 'active' : '' }}" type="submit">ID</button>
                    </form>
                    <form method="POST" action="{{ route('language.switch', 'en') }}">
                        @csrf
                        <button class="login-locale-button {{ app()->getLocale() === 'en' ? 'active' : '' }}" type="submit">EN</button>
                    </form>
                </div>
            </div>

            <form class="space-y-5" method="POST" action="{{ route('login.store') }}">
                @csrf

                <div>
                    <label class="mb-2 block text-sm font-medium text-stone-700" for="email">{{ __('messages.email') }}</label>
                    <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-stone-700" for="password">{{ __('messages.password') }}</label>
                    <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" id="password" name="password" type="password" required>
                </div>

                <label class="flex items-center gap-3 text-sm text-stone-600">
                    <input class="size-4 rounded border-stone-300 text-rose-600 focus:ring-rose-500" name="remember" type="checkbox" value="1">
                    <span>{{ __('messages.remember_me') }}</span>
                </label>

                <button class="w-full rounded-xl bg-rose-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-600" type="submit">
                    {{ __('messages.login') }}
                </button>
            </form>
        </section>
    </div>
@endsection
