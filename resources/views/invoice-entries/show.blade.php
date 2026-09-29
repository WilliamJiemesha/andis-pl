@extends('layouts.app', ['title' => __('messages.invoice_entry_detail')])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">{{ __('messages.invoice_entry_detail') }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-stone-900">{{ $invoiceEntry->invoice_number }} · {{ $invoiceEntry->vendor }}</h1>
            </div>
            <a class="rounded-xl border border-stone-200 px-4 py-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100" href="{{ route('invoice-entries.index') }}">{{ __('messages.back') }}</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_0.95fr]">
            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.invoice_entry_detail') }}</h2>
                <dl class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl bg-stone-50 p-4"><dt class="text-sm text-stone-500">{{ __('messages.invoice_date') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $invoiceEntry->invoice_date?->format('Y-m-d') }}</dd></div>
                    <div class="rounded-2xl bg-stone-50 p-4"><dt class="text-sm text-stone-500">{{ __('messages.linked_master_item') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $invoiceEntry->masterItem?->official_name ?? $invoiceEntry->raw_item_name }}</dd></div>
                    <div class="rounded-2xl bg-stone-50 p-4"><dt class="text-sm text-stone-500">{{ __('messages.quantity') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $invoiceEntry->quantity }}</dd></div>
                    <div class="rounded-2xl bg-stone-50 p-4"><dt class="text-sm text-stone-500">{{ __('messages.expected_quantity') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $invoiceEntry->expected_quantity ?? '-' }}</dd></div>
                    @if ($canViewCostCode)
                        <div class="rounded-2xl bg-stone-50 p-4"><dt class="text-sm text-stone-500">{{ __('messages.cost_code') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $invoiceEntry->cost_code ?: '-' }}</dd></div>
                    @endif
                    <div class="rounded-2xl bg-stone-50 p-4 md:col-span-2"><dt class="text-sm text-stone-500">{{ __('messages.notes') }}</dt><dd class="mt-2 text-stone-900">{{ $invoiceEntry->notes ?: __('messages.item_notes_empty') }}</dd></div>
                </dl>
            </section>

            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.status') }}</h2>
                <div class="mt-6 space-y-4">
                    <div class="rounded-2xl bg-stone-50 px-4 py-3">
                        <p class="text-sm text-stone-500">{{ __('messages.status') }}</p>
                        <p class="mt-2 font-medium text-stone-900">{{ __('messages.invoice_status_matched') }}</p>
                    </div>
                    <div class="rounded-2xl bg-stone-50 px-4 py-3">
                        <p class="text-sm text-stone-500">{{ __('messages.linked_master_item') }}</p>
                        <p class="mt-2 font-medium text-stone-900">{{ $invoiceEntry->masterItem?->official_name ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl bg-stone-50 px-4 py-3">
                        <p class="text-sm text-stone-500">{{ __('messages.incoming_check') }}</p>
                        <p class="mt-2 font-medium text-stone-900">
                            @if ($invoiceEntry->incomingCheckTask)
                                {{ $invoiceEntry->incomingCheckTask->status === 'pending'
                                    ? __('messages.incoming_check_pending')
                                    : ($invoiceEntry->incomingCheckTask->status === 'ok'
                                        ? __('messages.incoming_check_ok')
                                        : __('messages.incoming_check_mismatch')) }}
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div class="rounded-2xl bg-stone-50 px-4 py-3">
                        <p class="text-sm text-stone-500">{{ __('messages.created_by') }}</p>
                        <p class="mt-2 font-medium text-stone-900">{{ $invoiceEntry->creator?->name ?? '-' }}</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
