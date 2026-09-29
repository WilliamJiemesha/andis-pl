@extends('layouts.app', ['title' => __('messages.price_review')])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">{{ __('messages.price_review') }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-stone-900">{{ $task->masterItem?->official_name ?? '-' }}</h1>
            </div>
            <a class="rounded-lg border border-stone-200 px-3 py-2 text-xs font-medium text-stone-700 transition hover:bg-stone-100" href="{{ route('price-review-tasks.index') }}">{{ __('messages.back') }}</a>
        </div>

        @php
            $costHistories = $task->masterItem?->costHistories ?? collect();
            $currentCostHistory = $task->costHistory ?: $costHistories->first();
            $previousCostHistory = $currentCostHistory
                ? $costHistories->first(fn ($history) => $history->id !== $currentCostHistory->id)
                : null;
            $previousSellingPrice = $task->current_selling_price ?? $task->masterItem?->selling_price;
            $currentSellingPrice = $task->masterItem?->selling_price;
            $currentBuyAmount = $currentCostHistory?->decoded_cost_amount;
            $shouldPrefillSellingPrice = $currentSellingPrice !== null
                && ($currentBuyAmount === null || (float) $currentBuyAmount < (float) $currentSellingPrice);
            $prefillSellingPrice = $shouldPrefillSellingPrice
                ? number_format((float) $currentSellingPrice, 0, ',', '.')
                : '';
        @endphp

        <div>
            <article class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.system_overview') }}</h2>
                        <p class="mt-2 text-sm text-stone-500">{{ $task->invoiceEntry?->vendor ?? $task->costHistory?->vendor ?? '-' }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $task->status === 'reviewed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ $task->status === 'reviewed' ? __('messages.reviewed_price_tasks') : __('messages.open_tickets') }}
                    </span>
                </div>

                <div class="mt-6 space-y-4">
                    <div class="rounded-2xl bg-stone-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-400">{{ __('messages.purchase_code_short') }}</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-[1fr_auto_1fr] sm:items-center">
                            <div>
                                <p class="text-xs text-stone-500">{{ __('messages.previous_pc') }}</p>
                                <p class="mt-1 font-semibold text-stone-900">{{ $previousCostHistory?->cost_code ?: '-' }}</p>
                            </div>
                            <div class="hidden text-stone-300 sm:block">&rarr;</div>
                            <div>
                                <p class="text-xs text-stone-500">{{ __('messages.current_pc') }}</p>
                                <p class="mt-1 font-semibold text-stone-900">{{ $currentCostHistory?->cost_code ?: '-' }}</p>
                            </div>
                        </div>
                    </div>

                    @if ($task->status === 'open')
                        <form class="space-y-4" method="POST" action="{{ route('price-review-tasks.review', $task) }}">
                            @csrf
                            <div class="rounded-2xl bg-stone-50 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-400">{{ __('messages.selling_price') }}</p>
                                <div class="mt-3 grid gap-3 sm:grid-cols-[1fr_auto_1fr] sm:items-center">
                                    <div>
                                        <p class="text-xs text-stone-500">{{ __('messages.previous_selling_price') }}</p>
                                        <p class="mt-1 font-semibold text-stone-900">{{ \App\Support\Currency::rupiah($previousSellingPrice) }}</p>
                                    </div>
                                    <div class="hidden text-stone-300 sm:block">&rarr;</div>
                                    <div>
                                        <label class="text-xs text-stone-500" for="new_selling_price">{{ __('messages.current_selling_price') }}</label>
                                        <input class="mt-1 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-900 outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" id="new_selling_price" name="new_selling_price" type="text" inputmode="numeric" value="{{ old('new_selling_price', $prefillSellingPrice) }}" data-currency-input>
                                        @error('new_selling_price')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>

                            <button class="rounded-lg bg-rose-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-600" type="submit">{{ __('messages.save') }}</button>
                        </form>
                    @else
                        <div class="rounded-2xl bg-stone-50 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-400">{{ __('messages.selling_price') }}</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-[1fr_auto_1fr] sm:items-center">
                                <div>
                                    <p class="text-xs text-stone-500">{{ __('messages.previous_selling_price') }}</p>
                                    <p class="mt-1 font-semibold text-stone-900">{{ \App\Support\Currency::rupiah($previousSellingPrice) }}</p>
                                </div>
                                <div class="hidden text-stone-300 sm:block">&rarr;</div>
                                <div>
                                    <p class="text-xs text-stone-500">{{ __('messages.current_selling_price') }}</p>
                                    <p class="mt-1 font-semibold text-stone-900">{{ \App\Support\Currency::rupiah($currentSellingPrice) }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </article>
        </div>
    </div>
@endsection
