@extends('layouts.app', ['title' => __('messages.item_resolution_tickets')])

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.item_resolution_tickets') }}</h1>
            <p class="mt-2 text-sm text-stone-600">{{ __('messages.resolve_ticket') }}</p>
        </div>

        <form class="grid gap-4 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm md:grid-cols-[1fr_auto]" method="GET" action="{{ route('item-resolution-tickets.index') }}">
            <div>
                <label class="mb-2 block text-sm font-medium text-stone-700" for="q">{{ __('messages.search') }}</label>
                <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="q" name="q" type="text" value="{{ $search }}">
            </div>
            <div class="flex items-end">
                <div class="quick-filter">
                    <a class="{{ $status === 'open' ? 'active' : '' }}" href="{{ route('item-resolution-tickets.index', ['status' => 'open', 'q' => $search]) }}">{{ __('messages.ticket_status_open') }}</a>
                    <a class="{{ $status === 'resolved' ? 'active' : '' }}" href="{{ route('item-resolution-tickets.index', ['status' => 'resolved', 'q' => $search]) }}">{{ __('messages.ticket_status_resolved') }}</a>
                </div>
            </div>
        </form>

        <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
            <table class="responsive-table min-w-full text-sm">
                <thead class="bg-stone-50 text-left text-stone-600">
                    <tr>
                        <th class="px-6 py-4 font-medium">{{ __('messages.raw_item_name') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.vendor') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.status') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.linked_master_item') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td class="px-6 py-4 font-medium text-stone-900" data-label="{{ __('messages.raw_item_name') }}">{{ $ticket->raw_item_name }}</td>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.vendor') }}">{{ $ticket->vendor }}</td>
                            <td class="px-6 py-4" data-label="{{ __('messages.status') }}">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $ticket->status === 'resolved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $ticket->status === 'resolved' ? __('messages.ticket_status_resolved') : __('messages.ticket_status_open') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.linked_master_item') }}">{{ $ticket->resolvedMasterItem?->official_name ?? $ticket->invoiceEntry?->masterItem?->official_name ?? '-' }}</td>
                            <td class="px-6 py-4" data-label="{{ __('messages.actions') }}">
                                <a class="inline-flex size-9 items-center justify-center rounded-lg border border-stone-200 text-stone-700 transition hover:bg-stone-100" href="{{ route('item-resolution-tickets.show', $ticket) }}" title="{{ __('messages.view') }}">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-6 py-8 text-center text-stone-500" colspan="5">{{ __('messages.no_tickets') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $tickets->links() }}
    </div>
@endsection
