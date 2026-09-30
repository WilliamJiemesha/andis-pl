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
                    <a class="inline-flex size-11 items-center justify-center rounded-xl border border-stone-200 text-stone-700 transition hover:bg-stone-100" href="{{ route('master-items.edit', $masterItem) }}" title="{{ __('messages.edit') }}">
                        <i class="fa-regular fa-pen-to-square"></i>
                    </a>
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_0.9fr]">
            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                @php($latestCost = $masterItem->costHistories->first())

                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.structured_identity') }}</h2>
                    <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold {{ $masterItem->is_active ? 'app-positive-badge' : 'border-stone-200 bg-stone-100 text-stone-600' }}">
                        <span class="size-1.5 rounded-full {{ $masterItem->is_active ? 'app-positive-dot' : 'bg-stone-400' }}"></span>
                        {{ $masterItem->is_active ? __('messages.active') : __('messages.inactive') }}
                    </span>
                </div>

                <dl class="mt-6 space-y-4">
                    <div class="rounded-2xl bg-stone-50 p-4">
                        <dt class="text-sm text-stone-500">{{ __('messages.alias_name') }}</dt>
                        <dd class="mt-2 font-medium text-stone-900">{{ $masterItem->alias_name }}</dd>
                    </div>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-2xl bg-stone-50 p-4">
                            <dt class="text-sm text-stone-500">{{ __('messages.barang') }}</dt>
                            <dd class="mt-2 font-medium text-stone-900">{{ $masterItem->barang }}</dd>
                        </div>
                        <div class="rounded-2xl bg-stone-50 p-4">
                            <dt class="text-sm text-stone-500">{{ __('messages.tipe') }}</dt>
                            <dd class="mt-2 font-medium text-stone-900">{{ $masterItem->tipe }}</dd>
                        </div>
                        <div class="rounded-2xl bg-stone-50 p-4">
                            <dt class="text-sm text-stone-500">{{ __('messages.merk') }}</dt>
                            <dd class="mt-2 font-medium text-stone-900">{{ $masterItem->merk }}</dd>
                        </div>
                    </div>
                    <div class="grid gap-4 {{ auth()->user()->canViewCostCode() ? 'md:grid-cols-2' : '' }}">
                        @if (auth()->user()->canViewCostCode())
                            <div class="rounded-2xl bg-stone-50 p-4">
                                <dt class="text-sm text-stone-500">{{ __('messages.purchase_code_short') }}</dt>
                                <dd class="mt-2 font-medium text-stone-900">{{ $latestCost?->cost_code ?: '-' }}</dd>
                            </div>
                        @endif
                        <div class="rounded-2xl bg-stone-50 p-4">
                            <dt class="text-sm text-stone-500">{{ __('messages.selling_price') }}</dt>
                            <dd class="mt-2 font-medium text-stone-900">{{ \App\Support\Currency::rupiah($masterItem->selling_price) }}</dd>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <div class="relative" data-info-popover>
                            <button class="inline-flex items-center gap-2 rounded-lg border border-stone-200 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-100" data-info-popover-toggle type="button" aria-expanded="false" title="{{ __('messages.sku') }}">
                                <i class="fa-solid fa-barcode"></i>
                                <span>{{ __('messages.sku') }}</span>
                            </button>
                            <div class="info-popover" data-info-popover-panel hidden>
                                <dl class="space-y-3 text-sm">
                                    <div class="flex items-center justify-between gap-6">
                                        <dt class="text-stone-500">{{ __('messages.sku') }}</dt>
                                        <dd class="font-medium text-stone-900">{{ $masterItem->sku }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </dl>
            </section>

            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.notes') }}</h2>
                    <div class="relative" data-info-popover>
                        <button class="inline-flex size-9 items-center justify-center rounded-lg border border-stone-200 text-stone-700 transition hover:bg-stone-100" data-info-popover-toggle type="button" aria-expanded="false" title="{{ __('messages.info') }}">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="info-popover" data-info-popover-panel hidden>
                            <dl class="space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-6">
                                    <dt class="text-stone-500">{{ __('messages.created_by') }}</dt>
                                    <dd class="font-medium text-stone-900">{{ $masterItem->creator?->name ?? '-' }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-6">
                                    <dt class="text-stone-500">{{ __('messages.updated_by') }}</dt>
                                    <dd class="font-medium text-stone-900">{{ $masterItem->updater?->name ?? '-' }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-6">
                                    <dt class="text-stone-500">{{ __('messages.created_at') }}</dt>
                                    <dd class="font-medium text-stone-900">{{ $masterItem->created_at?->format('Y-m-d H:i') }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-6">
                                    <dt class="text-stone-500">{{ __('messages.updated_at') }}</dt>
                                    <dd class="font-medium text-stone-900">{{ $masterItem->updated_at?->format('Y-m-d H:i') }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="mt-6 rounded-2xl bg-stone-50 p-4 text-sm leading-7 text-stone-600">
                    {{ $masterItem->notes ?: __('messages.item_notes_empty') }}
                </div>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
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

        </div>
    </div>
@endsection
