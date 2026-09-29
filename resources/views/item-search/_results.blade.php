@forelse ($items as $item)
    @php($latestIncoming = $item->incomingCheckTasks->first())
    @php($latestStockEntry = $item->invoiceEntries->first())
    @php($latestPrice = $item->priceHistories->first())
    @php($latestCost = $item->costHistories->first())
    <div class="rounded-2xl border border-stone-200 px-4 py-3">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold text-stone-900">{{ $item->official_name }}</p>
                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-stone-500">
                    <span>{{ __('messages.sku') }}: {{ $item->sku }}</span>
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
            <div class="text-right">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-rose-300">{{ __('messages.selling_price') }}</p>
                <p class="text-lg font-semibold text-stone-900">{{ \App\Support\Currency::rupiah($item->selling_price) }}</p>
            </div>
        </div>
    </div>
@empty
    <div class="rounded-2xl border border-stone-200 px-4 py-6 text-sm text-stone-500">
        {{ __('messages.no_master_items') }}
    </div>
@endforelse
