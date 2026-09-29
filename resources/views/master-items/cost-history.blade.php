@extends('layouts.app', ['title' => __('messages.view_cost_history')])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.view_cost_history') }}</h1>
                <p class="mt-2 text-sm text-stone-600">{{ $masterItem->official_name }}</p>
            </div>
            <a class="rounded-xl border border-stone-200 px-4 py-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100" href="{{ route('master-items.show', $masterItem) }}">{{ __('messages.back') }}</a>
        </div>

        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-stone-600">{{ __('messages.cost_history_help') }}</p>
            <div class="mt-6 space-y-3">
                @forelse ($masterItem->costHistories as $history)
                    <div class="rounded-2xl bg-stone-50 px-4 py-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <p class="font-medium text-stone-900">{{ $history->recorded_at?->format('Y-m-d H:i') }}</p>
                            <p class="text-stone-500">{{ $history->vendor }}</p>
                        </div>
                        <p class="mt-2 text-stone-600">{{ $history->cost_code ?: '-' }}</p>
                        <p class="mt-2 text-stone-500">{{ $history->invoiceEntry?->invoice_number ?? '-' }} · {{ $history->recorder?->name ?? '-' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">{{ __('messages.no_cost_history') }}</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
