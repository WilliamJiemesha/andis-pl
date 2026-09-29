@extends('layouts.app', ['title' => __('messages.incoming_check_detail')])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">{{ __('messages.incoming_check_detail') }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-stone-900">{{ $task->invoiceEntry?->invoice_number ?? '-' }}</h1>
            </div>
            <a class="rounded-lg border border-stone-200 px-3 py-2 text-xs font-medium text-stone-700 transition hover:bg-stone-100" href="{{ route('incoming-check-tasks.index') }}">{{ __('messages.back') }}</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.incoming_check_detail') }}</h2>
                <dl class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl bg-stone-50 p-4"><dt class="text-sm text-stone-500">{{ __('messages.vendor') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $task->invoiceEntry?->vendor ?? '-' }}</dd></div>
                    <div class="rounded-2xl bg-stone-50 p-4"><dt class="text-sm text-stone-500">{{ __('messages.linked_master_item') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $task->masterItem?->official_name ?? $task->invoiceEntry?->raw_item_name ?? '-' }}</dd></div>
                    <div class="rounded-2xl bg-stone-50 p-4"><dt class="text-sm text-stone-500">{{ __('messages.linked_master_item') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $task->masterItem?->official_name ?? '-' }}</dd></div>
                    <div class="rounded-2xl bg-stone-50 p-4"><dt class="text-sm text-stone-500">{{ __('messages.expected_quantity') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $task->expected_quantity }}</dd></div>
                    <div class="rounded-2xl bg-stone-50 p-4"><dt class="text-sm text-stone-500">{{ __('messages.checked_quantity') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $task->checked_quantity ?? '-' }}</dd></div>
                    <div class="rounded-2xl bg-stone-50 p-4"><dt class="text-sm text-stone-500">{{ __('messages.check_result') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $task->status === 'pending' ? __('messages.incoming_check_pending') : ($task->status === 'ok' ? __('messages.incoming_check_ok') : __('messages.incoming_check_mismatch')) }}</dd></div>
                    <div class="rounded-2xl bg-stone-50 p-4 md:col-span-2"><dt class="text-sm text-stone-500">{{ __('messages.notes') }}</dt><dd class="mt-2 text-stone-900">{{ $task->notes ?: __('messages.item_notes_empty') }}</dd></div>
                </dl>
            </section>

            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.incoming_check') }}</h2>
                <form class="mt-6 space-y-4" method="POST" action="{{ route('incoming-check-tasks.update', $task) }}">
                    @csrf
                    <div>
                        <label class="mb-2 block text-sm font-medium text-stone-700" for="checked_quantity">{{ __('messages.checked_quantity') }}</label>
                        <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" id="checked_quantity" name="checked_quantity" type="number" min="0" value="{{ old('checked_quantity', $task->checked_quantity ?? $task->expected_quantity) }}" required>
                        @error('checked_quantity')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <p class="rounded-2xl bg-stone-50 px-4 py-3 text-sm text-stone-500">
                        {{ __('messages.incoming_check_auto_status_help') }}
                    </p>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-stone-700" for="notes">{{ __('messages.notes') }}</label>
                        <textarea class="min-h-24 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" id="notes" name="notes">{{ old('notes', $task->notes) }}</textarea>
                    </div>
                    <button class="rounded-lg bg-rose-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-600" type="submit">{{ __('messages.save') }}</button>
                </form>
            </section>
        </div>
    </div>
@endsection
