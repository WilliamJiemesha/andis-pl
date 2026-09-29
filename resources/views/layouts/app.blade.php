<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#080d12">
        <meta name="color-scheme" content="dark">
        <title>{{ $title ?? __('messages.app_name') }}</title>
        <script src="https://kit.fontawesome.com/7e6c04cd6a.js" crossorigin="anonymous"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="theme-app">
        <div class="app-shell {{ auth()->check() ? 'app-shell--auth' : 'app-shell--guest' }}">
            @auth
                @php($user = auth()->user())
                @php($unreadNotifications = $user->notifications()->whereNull('read_at')->count())
                @php($priceChangeNotifications = $user->notifications()->whereNull('read_at')->whereIn('type', ['price_updated_up', 'price_updated_down'])->count())
                @php($resolvingNotifications = $user->notifications()->whereNull('read_at')->whereIn('type', ['price_review_created', 'incoming_mismatch'])->count())
                @php($updateNotifications = $user->notifications()->whereNull('read_at')->whereIn('type', ['incoming_checked'])->count())
                <aside class="app-sidebar flex h-screen flex-col" data-sidebar>
                    <div class="app-brand">
                        <div class="flex items-start justify-between gap-4">
                            <div class="app-brand-mark text-white">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="app-icon-button app-sidebar-close lg:hidden" data-sidebar-close title="{{ __('messages.close') ?? 'Close' }}" type="button">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                                <form class="app-locale-switch" method="POST" action="{{ route('language.switch', app()->getLocale() === 'en' ? 'id' : 'en') }}">
                                    @csrf
                                    <button class="app-locale-switch__track" type="submit">
                                        <span class="app-locale-switch__option {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</span>
                                        <span class="app-locale-switch__option {{ app()->getLocale() === 'id' ? 'active' : '' }}">ID</span>
                                        <span class="app-locale-switch__thumb {{ app()->getLocale() === 'id' ? 'is-right' : '' }}"></span>
                                    </button>
                                </form>
                                <a class="app-icon-button {{ $unreadNotifications > 0 ? 'app-icon-button--alert' : '' }}" href="{{ route('notifications.index') }}" title="{{ __('messages.notifications') }}">
                                    <i class="fa-regular fa-bell"></i>
                                    @if ($unreadNotifications > 0)
                                        <span class="app-badge">{{ $unreadNotifications }}</span>
                                    @endif
                                </a>
                            </div>
                        </div>
                        <div class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-rose-300">{{ __('messages.app_name') }}</div>
                        <div class="mt-2 text-sm text-stone-500">{{ $user->name }}</div>
                        <div class="mt-4 rounded-2xl border border-stone-200 px-4 py-3 text-sm">
                            <div class="font-semibold text-stone-900">{{ $unreadNotifications }} {{ __('messages.unread_notifications') }}</div>
                            <div class="mt-2 space-y-1 text-xs text-stone-500">
                                <div>- {{ $priceChangeNotifications }} {{ __('messages.notification_group_price_change') }}</div>
                                <div>- {{ $resolvingNotifications }} {{ __('messages.notification_group_resolving') }}</div>
                                <div>- {{ $updateNotifications }} {{ __('messages.notification_group_updates') }}</div>
                            </div>
                        </div>
                    </div>

                    @if ($user->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::SALES_USER, \App\Models\Role::INVOICE_HANDLER, \App\Models\Role::PRICE_HANDLER]))
                        <button class="app-search-card mb-4" data-open-modal="item-search-modal" type="button">
                            <span class="app-search-card__icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <span class="app-search-card__label">{{ __('messages.search_items') }}</span>
                        </button>
                    @endif

                    <div class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-stone-500">{{ __('messages.navigation') }}</div>
                    <nav class="grid gap-2 text-sm">
                        <a class="app-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="fa-solid fa-gauge-high"></i>
                            <span>{{ __('messages.dashboard') }}</span>
                        </a>

                        @if ($user->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::INVOICE_HANDLER, \App\Models\Role::PRICE_HANDLER]))
                            <a class="app-nav-link {{ request()->routeIs('master-items.*') ? 'active' : '' }}" href="{{ route('master-items.index') }}">
                                <i class="fa-solid fa-cubes-stacked"></i>
                                <span>{{ __('messages.items') }}</span>
                            </a>
                        @endif

                        @if ($user->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::PRICE_HANDLER]))
                            <a class="app-nav-link {{ request()->routeIs('price-review-tasks.*') ? 'active' : '' }}" href="{{ route('price-review-tasks.index') }}">
                                <i class="fa-solid fa-tags"></i>
                                <span>{{ __('messages.price_review') }}</span>
                            </a>
                        @endif

                        @if ($user->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::INCOMING_CHECKER]))
                            <a class="app-nav-link {{ request()->routeIs('incoming-check-tasks.*') ? 'active' : '' }}" href="{{ route('incoming-check-tasks.index') }}">
                                <i class="fa-solid fa-clipboard-check"></i>
                                <span>{{ __('messages.incoming_check') }}</span>
                            </a>
                        @endif
                    </nav>

                    <div class="mt-auto flex items-center justify-end gap-2 pt-6">
                        @if ($user->hasRole(\App\Models\Role::ADMIN))
                            <a class="app-icon-button" href="{{ route('users.index') }}" title="{{ __('messages.users') }}">
                                <i class="fa-regular fa-user"></i>
                            </a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="app-icon-button" title="{{ __('messages.logout') }}" type="submit">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </button>
                        </form>
                    </div>
                </aside>
                <button class="app-sidebar-overlay" data-sidebar-close hidden type="button" aria-label="Close sidebar"></button>
            @endauth

            <main class="app-main {{ auth()->check() ? 'app-main--auth' : 'app-main--guest' }}">
                @auth
                    <div class="app-topbar">
                        <div class="flex items-center gap-3">
                            <button class="app-icon-button lg:hidden" data-sidebar-open title="{{ __('messages.navigation') }}" type="button">
                                <i class="fa-solid fa-bars"></i>
                            </button>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-300">{{ __('messages.workspace') }}</div>
                                <div class="mt-1 text-2xl font-semibold text-stone-900">{{ $title ?? __('messages.app_name') }}</div>
                            </div>
                        </div>
                        <div class="text-sm text-stone-500">{{ now()->format('Y-m-d') }}</div>
                    </div>
                @endauth

                @if (session('status'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>

        @auth
            @if ($user->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::SALES_USER, \App\Models\Role::INVOICE_HANDLER, \App\Models\Role::PRICE_HANDLER]))
                <div class="modal-shell" hidden id="item-search-modal">
                    <div class="modal-panel modal-panel--search rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.search_items') }}</h2>
                                <p class="mt-1 text-sm text-stone-600">{{ __('messages.search_items_help') }}</p>
                            </div>
                            <button class="rounded-lg border border-stone-200 px-3 py-2 text-xs font-semibold text-stone-700" data-close-modal type="button">{{ __('messages.back') }}</button>
                        </div>

                        <form class="search-modal-form mb-5 flex gap-3" data-search-modal-form>
                            <input class="flex-1 rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" name="q" placeholder="{{ __('messages.search_master_items') }}" type="text">
                            <button class="rounded-lg bg-rose-700 px-4 py-3 text-xs font-semibold text-white transition hover:bg-rose-600" type="submit">{{ __('messages.search') }}</button>
                        </form>

                        <div class="search-modal-results space-y-3" data-search-results>
                            <div class="rounded-2xl border border-stone-200 px-4 py-6 text-sm text-stone-500">{{ __('messages.search_items_help') }}</div>
                        </div>
                    </div>
                </div>
            @endif
        @endauth
    </body>
</html>
