@extends('layouts.app', ['title' => __('messages.items')])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.items') }}</h1>
                <p class="mt-2 text-sm text-stone-600">{{ __('messages.search_master_items') }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if (auth()->user()->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::INVOICE_HANDLER]))
                    <button class="rounded-lg border border-stone-200 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-100" data-open-modal="incoming-item-modal" type="button">{{ __('messages.open_incoming_form') }}</button>
                @endif
                <button class="rounded-lg border border-stone-200 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-100" data-open-modal="incoming-history-modal" type="button">{{ __('messages.open_history') }}</button>
                @if (auth()->user()->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::PRICE_HANDLER]))
                    <a class="rounded-lg bg-rose-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-600" href="{{ route('master-items.create') }}">
                        {{ __('messages.create_master_item') }}
                    </a>
                @endif
            </div>
        </div>

        <form class="items-filter rounded-2xl border border-stone-200 bg-white p-4 shadow-sm" method="GET" action="{{ route('master-items.index') }}">
            <div class="items-filter__search">
                <label class="sr-only" for="q">{{ __('messages.search') }}</label>
                <input class="w-full rounded-xl border border-stone-300 px-4 py-3 pr-11 text-sm outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-900/20" id="q" name="q" type="text" value="{{ $search }}" placeholder="{{ __('messages.search_master_items') }}">
                @if ($search !== '')
                    <a class="items-filter__clear" href="{{ route('master-items.index', $showInactive ? ['inactive' => 1] : []) }}" title="{{ __('messages.clear') }}">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </div>
            <label class="items-filter__checkbox">
                <input class="size-4 rounded border-stone-300 text-amber-500 focus:ring-amber-500" name="inactive" type="checkbox" value="1" @checked($showInactive)>
                <span>{{ __('messages.show_inactive') }}</span>
            </label>
            <button class="items-filter__submit" type="submit" title="{{ __('messages.search') }}">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
            <table class="items-table responsive-table min-w-full text-sm">
                <thead class="bg-stone-50 text-left text-stone-600">
                    <tr>
                        <th class="px-6 py-4 font-medium">{{ __('messages.alias_name') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.barang') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.merk') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.tipe') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.selling_price') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.status') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($masterItems as $masterItem)
                        <tr>
                            <td class="px-6 py-4" data-label="{{ __('messages.alias_name') }}">
                                <div class="font-semibold text-stone-900">{{ $masterItem->alias_name }}</div>
                                <div class="mt-1 text-xs text-stone-500">{{ $masterItem->official_name }} · {{ $masterItem->sku }}</div>
                            </td>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.barang') }}"><span class="items-table__pill">{{ $masterItem->barang }}</span></td>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.merk') }}">{{ $masterItem->merk }}</td>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.tipe') }}">{{ $masterItem->tipe }}</td>
                            <td class="px-6 py-4 font-medium text-stone-900" data-label="{{ __('messages.selling_price') }}">{{ \App\Support\Currency::rupiah($masterItem->selling_price) }}</td>
                            <td class="px-6 py-4" data-label="{{ __('messages.status') }}">
                                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ $masterItem->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">
                                    <span class="size-1.5 rounded-full {{ $masterItem->is_active ? 'bg-emerald-400' : 'bg-stone-400' }}"></span>
                                    {{ $masterItem->is_active ? __('messages.active') : __('messages.inactive') }}
                                </span>
                            </td>
                            <td class="px-6 py-4" data-label="{{ __('messages.actions') }}">
                                <div class="flex items-center gap-2">
                                    <a class="inline-flex size-9 items-center justify-center rounded-lg border border-stone-200 text-stone-700 transition hover:bg-stone-100" href="{{ route('master-items.show', $masterItem) }}" title="{{ __('messages.view') }}">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                    @if (auth()->user()->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::PRICE_HANDLER]))
                                        <a class="inline-flex size-9 items-center justify-center rounded-lg border border-stone-200 text-stone-700 transition hover:bg-stone-100" href="{{ route('master-items.edit', $masterItem) }}" title="{{ __('messages.edit') }}">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-6 py-8 text-center text-stone-500" colspan="7">{{ __('messages.no_master_items') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $masterItems->links() }}
    </div>

    @if (auth()->user()->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::INVOICE_HANDLER]))
        <div class="modal-shell" hidden id="incoming-item-modal">
            <div class="modal-panel modal-panel--wide rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.add_incoming') }}</h2>
                        <p class="mt-1 text-sm text-stone-600">{{ __('messages.cost_visibility_note') }}</p>
                    </div>
                    <button class="rounded-lg border border-stone-200 px-3 py-2 text-xs font-semibold text-stone-700" data-close-modal type="button">{{ __('messages.back') }}</button>
                </div>

                <form class="space-y-5" method="POST" action="{{ route('invoice-entries.store') }}">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-stone-700" for="incoming_date">{{ __('messages.invoice_date') }}</label>
                            <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" id="incoming_date" name="invoice_date" type="date" value="{{ old('invoice_date', now()->toDateString()) }}" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-stone-700" for="incoming_vendor">{{ __('messages.vendor') }}</label>
                            <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm uppercase outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" data-initial-focus data-uppercase id="incoming_vendor" name="vendor" type="text" value="{{ old('vendor') }}" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-stone-700" for="incoming_quantity">{{ __('messages.quantity') }}</label>
                            <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" id="incoming_quantity" min="1" name="quantity" type="number" value="{{ old('quantity') }}" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-stone-700" for="incoming_cost_code">{{ __('messages.cost_code') }}</label>
                            <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm uppercase outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" data-uppercase id="incoming_cost_code" name="cost_code" type="text" value="{{ old('cost_code') }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-stone-700" for="incoming_master_search">{{ __('messages.select_master_item') }}</label>
                            <div class="combo-box" data-master-combobox>
                                <input name="master_item_id" type="hidden" value="{{ old('master_item_id') }}">
                                <input class="combo-box__input" data-master-search id="incoming_master_search" placeholder="{{ __('messages.no_master_item_selected') }}" type="text" autocomplete="off">
                                <button class="combo-box__toggle" data-master-toggle type="button" aria-label="{{ __('messages.select_master_item') }}">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                                <div class="combo-box__menu hidden" data-master-menu>
                                    <button class="combo-box__option combo-box__option--create" data-master-option data-value="__create__" type="button">{{ __('messages.create_master_item_inline') }}</button>
                                    @foreach ($masterItemOptions as $masterItemOption)
                                        <button class="combo-box__option" data-master-option data-notes="{{ $masterItemOption->notes }}" data-value="{{ $masterItemOption->id }}" type="button">{{ $masterItemOption->alias_name }} · {{ $masterItemOption->official_name }}</button>
                                    @endforeach
                                </div>
                            </div>
                            @error('master_item_id')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="hidden rounded-2xl border border-stone-200 bg-stone-50 p-4 md:col-span-2" data-inline-master-form>
                            <input name="create_master_inline" type="hidden" value="0">
                            <div class="mb-4 text-sm font-semibold text-stone-900">{{ __('messages.create_master_item_inline') }}</div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-stone-700" for="new_master_barang">{{ __('messages.barang') }}</label>
                                    <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm uppercase outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" data-uppercase id="new_master_barang" name="new_master_barang" type="text" value="{{ old('new_master_barang') }}">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-stone-700" for="new_master_merk">{{ __('messages.merk') }}</label>
                                    <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm uppercase outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" data-uppercase id="new_master_merk" name="new_master_merk" type="text" value="{{ old('new_master_merk') }}">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-stone-700" for="new_master_tipe">{{ __('messages.tipe') }}</label>
                                    <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm uppercase outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" data-uppercase id="new_master_tipe" name="new_master_tipe" type="text" value="{{ old('new_master_tipe') }}">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-stone-700" for="new_master_alias_name">{{ __('messages.alias_name') }}</label>
                                    <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" id="new_master_alias_name" name="new_master_alias_name" type="text" value="{{ old('new_master_alias_name') }}">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-medium text-stone-700" for="new_master_notes">{{ __('messages.notes') }}</label>
                                    <textarea class="min-h-24 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" id="new_master_notes" name="new_master_notes">{{ old('new_master_notes') }}</textarea>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button class="rounded-lg bg-rose-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-600" data-save-inline-master type="button">{{ __('messages.save_and_select') }}</button>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-stone-700" for="incoming_notes">{{ __('messages.notes') }}</label>
                            <textarea class="min-h-24 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" id="incoming_notes" name="notes">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button class="rounded-lg bg-rose-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-600" type="submit">{{ __('messages.save') }}</button>
                        <button class="rounded-lg border border-stone-200 px-3 py-2 text-xs font-semibold text-stone-700" data-close-modal type="button">{{ __('messages.cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="modal-shell" hidden id="incoming-history-modal">
        <div class="modal-panel modal-panel--wide rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.incoming_history') }}</h2>
                    <p class="mt-1 text-sm text-stone-600">{{ __('messages.invoice_entry_list') }}</p>
                </div>
                <button class="rounded-lg border border-stone-200 px-3 py-2 text-xs font-semibold text-stone-700" data-close-modal type="button">{{ __('messages.back') }}</button>
            </div>

            <div class="space-y-3">
                @foreach ($incomingHistory as $historyItem)
                    <div class="rounded-2xl border border-stone-200 px-4 py-3">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-medium text-stone-900">{{ $historyItem->masterItem?->official_name ?? $historyItem->raw_item_name }}</p>
                                <p class="text-sm text-stone-500">{{ $historyItem->vendor }} · {{ $historyItem->invoice_date?->format('Y-m-d') }}</p>
                            </div>
                            <div class="text-right text-sm text-stone-500">
                                <div>{{ $historyItem->cost_code ?: '-' }}</div>
                                <div>{{ $historyItem->quantity }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
