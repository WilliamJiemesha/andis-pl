@extends('layouts.app', ['title' => __('messages.dashboard')])

@section('content')
    <div class="space-y-8">
        <section>
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.task_summary') }}</h2>
                <span class="w-fit rounded-full bg-emerald-900/20 px-3 py-1 text-xs font-semibold text-emerald-300">{{ __('messages.status_ready') }}</span>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($taskCards as $taskCard)
                    <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-sm text-stone-500">{{ $taskCard['label'] }}</p>
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-stone-50 text-amber-300">
                                <i class="{{ $taskCard['icon'] }}"></i>
                            </span>
                        </div>
                        <p class="mt-3 text-3xl font-semibold text-stone-900">{{ $taskCard['count'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.recent_items') }}</h2>
            <div class="mt-6 space-y-4">
                @forelse ($recentIncomingItems as $invoiceEntry)
                    <div class="dashboard-recent-row rounded-2xl bg-stone-50 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-stone-900">{{ $invoiceEntry->masterItem?->alias_name ?? $invoiceEntry->masterItem?->official_name ?? '-' }}</p>
                            <p class="text-sm text-stone-500">{{ $invoiceEntry->vendor }} · {{ $invoiceEntry->invoice_date?->format('Y-m-d') }}</p>
                        </div>
                        <div class="shrink-0 text-right text-sm text-stone-500">
                            <div class="font-medium text-stone-900">{{ \App\Support\Currency::rupiah($invoiceEntry->masterItem?->selling_price) }}</div>
                            <div>{{ $invoiceEntry->cost_code ?: '-' }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">{{ __('messages.no_item_logs') }}</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
