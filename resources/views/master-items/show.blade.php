@extends('layouts.app', ['title' => $masterItem->official_name])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">{{ __('messages.master_item_detail') }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-stone-900">{{ $masterItem->official_name }}</h1>
            </div>

            <div class="flex items-center gap-3">
                <a class="rounded-xl border border-stone-200 px-4 py-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100" href="{{ auth()->user()->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::INVOICE_HANDLER, \App\Models\Role::PRICE_HANDLER]) ? route('master-items.index') : route('item-search.index') }}">
                    {{ __('messages.back') }}
                </a>
                @if (auth()->user()->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::PRICE_HANDLER]))
                    <a class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500" href="{{ route('master-items.edit', $masterItem) }}">
                        {{ __('messages.edit') }}
                    </a>
                @endif
                @if (auth()->user()->hasRole(\App\Models\Role::ADMIN))
                    <form method="POST" action="{{ route('master-items.destroy', $masterItem) }}">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-xl border border-rose-200 px-4 py-3 text-sm font-medium text-rose-700 transition hover:bg-rose-50" onclick="return confirm('{{ __('messages.delete_confirm') }}')" type="submit">
                            {{ __('messages.delete') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_0.9fr]">
            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.structured_identity') }}</h2>
                <dl class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl bg-stone-50 p-4">
                        <dt class="text-sm text-stone-500">{{ __('messages.barang') }}</dt>
                        <dd class="mt-2 font-medium text-stone-900">{{ $masterItem->barang }}</dd>
                    </div>
                    <div class="rounded-2xl bg-stone-50 p-4">
                        <dt class="text-sm text-stone-500">{{ __('messages.merk') }}</dt>
                        <dd class="mt-2 font-medium text-stone-900">{{ $masterItem->merk }}</dd>
                    </div>
                    <div class="rounded-2xl bg-stone-50 p-4">
                        <dt class="text-sm text-stone-500">{{ __('messages.tipe') }}</dt>
                        <dd class="mt-2 font-medium text-stone-900">{{ $masterItem->tipe }}</dd>
                    </div>
                    <div class="rounded-2xl bg-stone-50 p-4">
                        <dt class="text-sm text-stone-500">{{ __('messages.alias_name') }}</dt>
                        <dd class="mt-2 font-medium text-stone-900">{{ $masterItem->alias_name }}</dd>
                    </div>
                    <div class="rounded-2xl bg-stone-50 p-4">
                        <dt class="text-sm text-stone-500">{{ __('messages.sku') }}</dt>
                        <dd class="mt-2 font-medium text-stone-900">{{ $masterItem->sku }}</dd>
                    </div>
                    <div class="rounded-2xl bg-stone-50 p-4">
                        <dt class="text-sm text-stone-500">{{ __('messages.selling_price') }}</dt>
                        <dd class="mt-2 font-medium text-stone-900">{{ \App\Support\Currency::rupiah($masterItem->selling_price) }}</dd>
                    </div>
                    <div class="rounded-2xl bg-stone-50 p-4 md:col-span-2">
                        <dt class="text-sm text-stone-500">{{ __('messages.status') }}</dt>
                        <dd class="mt-2">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $masterItem->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">
                                {{ $masterItem->is_active ? __('messages.active') : __('messages.inactive') }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.notes') }}</h2>
                <div class="mt-6 rounded-2xl bg-stone-50 p-4 text-sm leading-7 text-stone-600">
                    {{ $masterItem->notes ?: __('messages.item_notes_empty') }}
                </div>

                <dl class="mt-6 space-y-4 text-sm">
                    <div class="flex items-center justify-between gap-4 rounded-2xl bg-stone-50 px-4 py-3">
                        <dt class="text-stone-500">{{ __('messages.created_by') }}</dt>
                        <dd class="font-medium text-stone-900">{{ $masterItem->creator?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 rounded-2xl bg-stone-50 px-4 py-3">
                        <dt class="text-stone-500">{{ __('messages.updated_by') }}</dt>
                        <dd class="font-medium text-stone-900">{{ $masterItem->updater?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 rounded-2xl bg-stone-50 px-4 py-3">
                        <dt class="text-stone-500">{{ __('messages.created_at') }}</dt>
                        <dd class="font-medium text-stone-900">{{ $masterItem->created_at?->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 rounded-2xl bg-stone-50 px-4 py-3">
                        <dt class="text-stone-500">{{ __('messages.updated_at') }}</dt>
                        <dd class="font-medium text-stone-900">{{ $masterItem->updated_at?->format('Y-m-d H:i') }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.price_history') }}</h2>
                    <a class="text-sm font-medium text-emerald-700" href="{{ route('master-items.price-history', $masterItem) }}">{{ __('messages.view_price_history') }}</a>
                </div>
                <div class="mt-6 space-y-3">
                    @forelse ($masterItem->priceHistories as $history)
                        <div class="rounded-2xl bg-stone-50 px-4 py-3 text-sm">
                            <p class="font-medium text-stone-900">{{ $history->effective_at?->format('Y-m-d H:i') }}</p>
                                <p class="text-stone-500">{{ \App\Support\Currency::rupiah($history->previous_price) }} -> {{ \App\Support\Currency::rupiah($history->new_price) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-stone-500">{{ __('messages.no_price_history') }}</p>
                    @endforelse
                </div>
            </section>

                @if (auth()->user()->canViewCostCode())
                    <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.cost_history') }}</h2>
                        <a class="text-sm font-medium text-emerald-700" href="{{ route('master-items.cost-history', $masterItem) }}">{{ __('messages.view_cost_history') }}</a>
                    </div>
                    <div class="mt-6 space-y-3">
                        @forelse ($masterItem->costHistories as $history)
                            <div class="rounded-2xl bg-stone-50 px-4 py-3 text-sm">
                                <p class="font-medium text-stone-900">{{ $history->recorded_at?->format('Y-m-d H:i') }}</p>
                                <p class="text-stone-500">{{ $history->cost_code ?: '-' }} · {{ $history->vendor ?: '-' }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-stone-500">{{ __('messages.no_cost_history') }}</p>
                        @endforelse
                    </div>
                </section>
            @endif

            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.latest_activity') }}</h2>
                <div class="mt-6 space-y-3">
                    @forelse ($masterItem->incomingCheckTasks as $task)
                        <div class="rounded-2xl bg-stone-50 px-4 py-3 text-sm">
                            <p class="font-medium text-stone-900">
                                {{ $task->status === 'pending'
                                    ? __('messages.incoming_check_pending')
                                    : ($task->status === 'ok'
                                        ? __('messages.incoming_check_ok')
                                        : __('messages.incoming_check_mismatch')) }}
                            </p>
                            <p class="text-stone-500">{{ $task->checked_at?->format('Y-m-d H:i') ?? '-' }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-stone-500">{{ __('messages.no_incoming_check_tasks') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
