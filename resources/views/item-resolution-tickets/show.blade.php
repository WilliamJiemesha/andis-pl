@extends('layouts.app', ['title' => __('messages.ticket_detail')])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">{{ __('messages.ticket_detail') }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-stone-900">{{ $ticket->raw_item_name }}</h1>
            </div>
            <a class="rounded-xl border border-stone-200 px-4 py-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100" href="{{ route('item-resolution-tickets.index') }}">{{ __('messages.back') }}</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.ticket_detail') }}</h2>
                <dl class="mt-6 space-y-4 text-sm">
                    <div class="rounded-2xl bg-stone-50 px-4 py-3"><dt class="text-stone-500">{{ __('messages.vendor') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $ticket->vendor }}</dd></div>
                    <div class="rounded-2xl bg-stone-50 px-4 py-3"><dt class="text-stone-500">{{ __('messages.status') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $ticket->status === 'resolved' ? __('messages.ticket_status_resolved') : __('messages.ticket_status_open') }}</dd></div>
                    <div class="rounded-2xl bg-stone-50 px-4 py-3"><dt class="text-stone-500">{{ __('messages.linked_master_item') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $ticket->resolvedMasterItem?->official_name ?? $ticket->invoiceEntry?->masterItem?->official_name ?? '-' }}</dd></div>
                    @if ($ticket->status === 'resolved')
                        <div class="rounded-2xl bg-stone-50 px-4 py-3"><dt class="text-stone-500">{{ __('messages.resolution_type') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $ticket->resolution_type === 'created_new' ? __('messages.resolution_created_new') : __('messages.resolution_linked_existing') }}</dd></div>
                        <div class="rounded-2xl bg-stone-50 px-4 py-3"><dt class="text-stone-500">{{ __('messages.resolved_by') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $ticket->resolver?->name ?? '-' }}</dd></div>
                        <div class="rounded-2xl bg-stone-50 px-4 py-3"><dt class="text-stone-500">{{ __('messages.resolved_at') }}</dt><dd class="mt-2 font-medium text-stone-900">{{ $ticket->resolved_at?->format('Y-m-d H:i') }}</dd></div>
                        <div class="rounded-2xl bg-stone-50 px-4 py-3"><dt class="text-stone-500">{{ __('messages.resolution_note') }}</dt><dd class="mt-2 text-stone-900">{{ $ticket->resolution_note ?: __('messages.item_notes_empty') }}</dd></div>
                    @endif
                </dl>
            </section>

            <section class="space-y-6">
                <article class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.matching_suggestions') }}</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($suggestedItems as $suggestedItem)
                            <div class="rounded-2xl bg-stone-50 px-4 py-3">
                                <p class="font-medium text-stone-900">{{ $suggestedItem->official_name }}</p>
                                <p class="text-sm text-stone-500">{{ $suggestedItem->barang }} · {{ $suggestedItem->merk }} · {{ $suggestedItem->tipe }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-stone-500">{{ __('messages.no_master_items') }}</p>
                        @endforelse
                    </div>
                </article>

                @if ($ticket->status === 'open')
                    <article class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.link_existing_item') }}</h2>
                        <form class="mt-6 space-y-4" method="POST" action="{{ route('item-resolution-tickets.link', $ticket) }}">
                            @csrf
                            <div>
                                <label class="mb-2 block text-sm font-medium text-stone-700" for="master_item_id">{{ __('messages.select_master_item') }}</label>
                                <select class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="master_item_id" name="master_item_id" required>
                                    <option value="">{{ __('messages.select_master_item') }}</option>
                                    @foreach ($masterItems as $masterItem)
                                        <option value="{{ $masterItem->id }}">{{ $masterItem->official_name }}</option>
                                    @endforeach
                                </select>
                                @error('master_item_id')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-stone-700" for="link_resolution_note">{{ __('messages.resolution_note') }}</label>
                                <textarea class="min-h-24 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="link_resolution_note" name="resolution_note">{{ old('resolution_note') }}</textarea>
                            </div>
                            <button class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500" type="submit">{{ __('messages.link_existing_item') }}</button>
                        </form>
                    </article>

                    <article class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-stone-900">{{ __('messages.create_new_master_item') }}</h2>
                        <form class="mt-6 space-y-4" method="POST" action="{{ route('item-resolution-tickets.create-master-item', $ticket) }}">
                            @csrf
                            <div class="grid gap-4 md:grid-cols-3">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-stone-700" for="barang">{{ __('messages.barang') }}</label>
                                    <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="barang" name="barang" type="text" value="{{ old('barang') }}" required>
                                    @error('barang')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-stone-700" for="merk">{{ __('messages.merk') }}</label>
                                    <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="merk" name="merk" type="text" value="{{ old('merk') }}" required>
                                    @error('merk')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-stone-700" for="tipe">{{ __('messages.tipe') }}</label>
                                    <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="tipe" name="tipe" type="text" value="{{ old('tipe') }}" required>
                                    @error('tipe')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                                    @error('official_name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-[1fr_auto]">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-stone-700" for="selling_price">{{ __('messages.selling_price') }}</label>
                                    <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="selling_price" name="selling_price" type="number" min="0" step="0.01" value="{{ old('selling_price') }}">
                                </div>
                                <div class="flex items-end">
                                    <label class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                                        <input class="size-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                                        <span>{{ __('messages.active') }}</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-stone-700" for="notes">{{ __('messages.notes') }}</label>
                                <textarea class="min-h-24 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="notes" name="notes">{{ old('notes') }}</textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-stone-700" for="create_resolution_note">{{ __('messages.resolution_note') }}</label>
                                <textarea class="min-h-24 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="create_resolution_note" name="resolution_note">{{ old('resolution_note') }}</textarea>
                            </div>
                            <button class="rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-stone-700" type="submit">{{ __('messages.create_new_master_item') }}</button>
                        </form>
                    </article>
                @endif
            </section>
        </div>
    </div>
@endsection
