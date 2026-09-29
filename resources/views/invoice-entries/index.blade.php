@extends('layouts.app', ['title' => __('messages.invoice_entry_list')])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.invoice_entry_list') }}</h1>
                <p class="mt-2 text-sm text-stone-600">{{ __('messages.search_master_for_invoice') }}</p>
            </div>
            @if (auth()->user()->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::INVOICE_HANDLER]))
                <a class="inline-flex rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500" href="{{ route('invoice-entries.create') }}">
                    {{ __('messages.create_invoice_entry') }}
                </a>
            @endif
        </div>

        <form class="grid gap-4 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm md:grid-cols-[1fr_180px_auto_auto]" method="GET" action="{{ route('invoice-entries.index') }}">
            <div>
                <label class="mb-2 block text-sm font-medium text-stone-700" for="q">{{ __('messages.search') }}</label>
                <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="q" name="q" type="text" value="{{ $search }}">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-stone-700" for="status">{{ __('messages.status') }}</label>
                <select class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="status" name="status">
                    <option value="">{{ __('messages.filter') }}</option>
                    <option value="matched" @selected($status === 'matched')>{{ __('messages.invoice_status_matched') }}</option>
                    <option value="needs_resolution" @selected($status === 'needs_resolution')>{{ __('messages.invoice_status_needs_resolution') }}</option>
                </select>
            </div>
            <div class="flex items-end">
                <button class="w-full rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-stone-700" type="submit">{{ __('messages.filter') }}</button>
            </div>
            <div class="flex items-end">
                <a class="w-full rounded-xl border border-stone-200 px-4 py-3 text-center text-sm font-medium text-stone-700 transition hover:bg-stone-100" href="{{ route('invoice-entries.index') }}">{{ __('messages.clear') }}</a>
            </div>
        </form>

        <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
            <table class="responsive-table min-w-full text-sm">
                <thead class="bg-stone-50 text-left text-stone-600">
                    <tr>
                        <th class="px-6 py-4 font-medium">{{ __('messages.invoice_number') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.vendor') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.raw_item_name') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.linked_master_item') }}</th>
                        @if ($canViewCostCode)
                            <th class="px-6 py-4 font-medium">{{ __('messages.cost_code') }}</th>
                        @endif
                        <th class="px-6 py-4 font-medium">{{ __('messages.status') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoiceEntries as $invoiceEntry)
                        <tr>
                            <td class="px-6 py-4 font-medium text-stone-900" data-label="{{ __('messages.invoice_number') }}">{{ $invoiceEntry->invoice_number }}</td>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.vendor') }}">{{ $invoiceEntry->vendor }}</td>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.raw_item_name') }}">{{ $invoiceEntry->raw_item_name }}</td>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.linked_master_item') }}">{{ $invoiceEntry->masterItem?->official_name ?? '-' }}</td>
                            @if ($canViewCostCode)
                                <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.cost_code') }}">{{ $invoiceEntry->cost_code ?: '-' }}</td>
                            @endif
                            <td class="px-6 py-4" data-label="{{ __('messages.status') }}">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $invoiceEntry->status === 'matched' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $invoiceEntry->status === 'matched' ? __('messages.invoice_status_matched') : __('messages.invoice_status_needs_resolution') }}
                                </span>
                            </td>
                            <td class="px-6 py-4" data-label="{{ __('messages.actions') }}">
                                <a class="inline-flex size-9 items-center justify-center rounded-lg border border-stone-200 text-stone-700 transition hover:bg-stone-100" href="{{ route('invoice-entries.show', $invoiceEntry) }}" title="{{ __('messages.view') }}">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-6 py-8 text-center text-stone-500" colspan="{{ $canViewCostCode ? 7 : 6 }}">{{ __('messages.no_invoice_entries') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $invoiceEntries->links() }}
    </div>
@endsection
