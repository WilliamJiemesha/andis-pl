@extends('layouts.app', ['title' => __('messages.search_items')])

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.search_items') }}</h1>
            <p class="mt-2 text-sm text-stone-600">{{ __('messages.search_items_help') }}</p>
        </div>

        <form class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm" method="GET" action="{{ route('item-search.index') }}">
            <label class="mb-2 block text-sm font-medium text-stone-700" for="q">{{ __('messages.search') }}</label>
            <div class="flex gap-3">
                <input class="flex-1 rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="q" name="q" type="text" value="{{ $search }}" placeholder="{{ __('messages.search_master_items') }}">
                <button class="rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-stone-700" type="submit">{{ __('messages.search') }}</button>
            </div>
        </form>

        <div class="grid gap-4">
            @forelse ($items as $item)
                @php($latestIncoming = $item->incomingCheckTasks->first())
                @php($latestStockEntry = $item->invoiceEntries->first())
                @php($latestPrice = $item->priceHistories->first())
                @php($latestCost = $item->costHistories->first())
                <article class="rounded-2xl border border-stone-200 bg-white px-5 py-4 shadow-sm">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                <h2 class="truncate text-base font-semibold text-stone-900">{{ $item->official_name }}</h2>
                                <span class="text-sm text-stone-500">{{ $item->sku }} · {{ $item->barang }} · {{ $item->merk }} · {{ $item->tipe }}</span>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-stone-600">
                                <span>{{ __('messages.last_in') }}: {{ $latestStockEntry?->invoice_date?->format('Y-m-d') ?? '-' }}</span>
                                <span>{{ __('messages.last_price_update') }}: {{ $latestPrice?->effective_at?->format('Y-m-d') ?? '-' }}</span>
                                @if ($canViewCostCode)
                                    <span>{{ __('messages.last_cost') }}: {{ $latestCost?->cost_code ?: '-' }}</span>
                                @endif
                                <span>{{ __('messages.last_vendor') }}: {{ $latestCost?->vendor ?: '-' }}</span>
                                <span>{{ __('messages.latest_incoming_status') }}:
                                    {{ $latestIncoming
                                        ? ($latestIncoming->status === 'pending'
                                            ? __('messages.incoming_check_pending')
                                            : ($latestIncoming->status === 'ok'
                                                ? __('messages.incoming_check_ok')
                                                : __('messages.incoming_check_mismatch')))
                                        : '-' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 xl:shrink-0">
                            <div class="text-right">
                                <p class="text-xs font-medium uppercase tracking-[0.16em] text-stone-500">{{ __('messages.selling_price') }}</p>
                                <p class="text-lg font-semibold text-stone-900">{{ \App\Support\Currency::rupiah($item->selling_price) }}</p>
                            </div>
                            <a class="rounded-lg border border-stone-200 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100" href="{{ route('master-items.show', $item) }}">{{ __('messages.view') }}</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-stone-200 bg-white p-8 text-sm text-stone-500 shadow-sm">
                    {{ __('messages.no_master_items') }}
                </div>
            @endforelse
        </div>

        {{ $items->links() }}
    </div>
@endsection
