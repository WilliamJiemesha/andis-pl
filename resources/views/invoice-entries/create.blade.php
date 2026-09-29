@extends('layouts.app', ['title' => __('messages.create_invoice_entry')])

@section('content')
    <div class="space-y-6">
        <div class="mx-auto max-w-6xl rounded-3xl border border-stone-200 bg-white p-8 shadow-sm">
            <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.create_invoice_entry') }}</h1>
            <p class="mt-2 text-sm text-stone-600">{{ __('messages.cost_visibility_note') }}</p>

            <form class="mt-8 space-y-6" method="POST" action="{{ route('invoice-entries.store') }}">
                @csrf

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-stone-700" for="vendor">{{ __('messages.vendor') }}</label>
                        <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm uppercase outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="vendor" name="vendor" type="text" value="{{ old('vendor') }}" data-uppercase required>
                        @error('vendor')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-stone-700" for="invoice_date">{{ __('messages.invoice_date') }}</label>
                        <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="invoice_date" name="invoice_date" type="date" value="{{ old('invoice_date', now()->toDateString()) }}" required>
                        @error('invoice_date')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-stone-700" for="raw_item_name">{{ __('messages.raw_item_name') }}</label>
                        <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="raw_item_name" name="raw_item_name" type="text" value="{{ old('raw_item_name') }}" required>
                        @error('raw_item_name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-stone-700" for="quantity">{{ __('messages.quantity') }}</label>
                        <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity') }}" required>
                        @error('quantity')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    @if ($canViewCostCode)
                        <div>
                            <label class="mb-2 block text-sm font-medium text-stone-700" for="cost_code">{{ __('messages.cost_code') }}</label>
                            <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="cost_code" name="cost_code" type="text" value="{{ old('cost_code') }}">
                            @error('cost_code')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    @endif
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-stone-700" for="notes">{{ __('messages.notes') }}</label>
                    <textarea class="min-h-28 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="notes" name="notes">{{ old('notes') }}</textarea>
                    @error('notes')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                    <section class="rounded-3xl border border-stone-200 bg-stone-50 p-5">
                        <h2 class="text-lg font-semibold text-stone-900">{{ __('messages.master_item_match') }}</h2>
                        <p class="mt-2 text-sm text-stone-600">{{ __('messages.search_match_help') }}</p>
                        <div class="mt-4">
                            <label class="mb-2 block text-sm font-medium text-stone-700" for="master_item_id">{{ __('messages.select_master_item') }}</label>
                            <select class="w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="master_item_id" name="master_item_id">
                                <option value="">{{ __('messages.no_master_item_selected') }}</option>
                                @foreach ($masterItems as $masterItem)
                                    <option value="{{ $masterItem->id }}" @selected((string) old('master_item_id') === (string) $masterItem->id)>{{ $masterItem->official_name }}</option>
                                @endforeach
                            </select>
                            @error('master_item_id')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <label class="mt-4 flex items-start gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-700">
                            <input class="mt-0.5 size-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" name="create_resolution_ticket" type="checkbox" value="1" @checked(old('create_resolution_ticket'))>
                            <span>{{ __('messages.create_resolution_ticket') }}</span>
                        </label>
                        @error('create_resolution_ticket')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </section>

                    <section class="rounded-3xl border border-stone-200 bg-white p-5">
                        <h2 class="text-lg font-semibold text-stone-900">{{ __('messages.matching_suggestions') }}</h2>
                        <div class="mt-4 flex gap-3">
                            <input class="flex-1 rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" name="match" type="text" value="{{ $matchSearch }}" placeholder="{{ __('messages.raw_item_name') }}">
                            <button class="rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-stone-700" formaction="{{ route('invoice-entries.create') }}" formmethod="GET" type="submit">{{ __('messages.search') }}</button>
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse ($suggestedItems as $suggestedItem)
                                <div class="rounded-2xl bg-stone-50 px-4 py-3">
                                    <p class="font-medium text-stone-900">{{ $suggestedItem->official_name }}</p>
                                    <p class="text-sm text-stone-500">{{ $suggestedItem->barang }} · {{ $suggestedItem->merk }} · {{ $suggestedItem->tipe }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-stone-500">{{ $matchSearch !== '' ? __('messages.no_master_items') : __('messages.search_match_help') }}</p>
                            @endforelse
                        </div>
                    </section>
                </div>

                <div class="flex items-center gap-3">
                    <button class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500" type="submit">{{ __('messages.save') }}</button>
                    <a class="rounded-xl border border-stone-200 px-4 py-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100" href="{{ route('invoice-entries.index') }}">{{ __('messages.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
