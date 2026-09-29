@extends('layouts.app', ['title' => __('messages.incoming_check_list')])

@section('content')
    @php($isPendingView = $status === 'pending')

    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.incoming_check_list') }}</h1>
            <p class="mt-2 text-sm text-stone-600">{{ __('messages.incoming_check_help') }}</p>
        </div>

        <div class="quick-filter">
            <a class="{{ $status === 'pending' ? 'active' : '' }}" href="{{ route('incoming-check-tasks.index', ['status' => 'pending']) }}">{{ __('messages.incoming_check_pending') }}</a>
            <a class="{{ $status === 'checked' ? 'active' : '' }}" href="{{ route('incoming-check-tasks.index', array_filter(['status' => 'checked', 'date' => $date])) }}">{{ __('messages.checked_items') }}</a>
            <a class="{{ $status === 'ok' ? 'active' : '' }}" href="{{ route('incoming-check-tasks.index', array_filter(['status' => 'ok', 'date' => $date])) }}">{{ __('messages.incoming_check_ok') }}</a>
            <a class="{{ $status === 'mismatch' ? 'active' : '' }}" href="{{ route('incoming-check-tasks.index', array_filter(['status' => 'mismatch', 'date' => $date])) }}">{{ __('messages.incoming_check_mismatch') }}</a>
            <a class="{{ $status === 'all' ? 'active' : '' }}" href="{{ route('incoming-check-tasks.index', array_filter(['status' => 'all', 'date' => $date])) }}">{{ __('messages.all_items') }}</a>
        </div>

        @if ($status !== 'pending')
            <form class="grid gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,220px)_auto]" method="GET" action="{{ route('incoming-check-tasks.index') }}">
                <input name="status" type="hidden" value="{{ $status }}">
                <div>
                    <label class="mb-2 block text-sm font-medium text-stone-700" for="date">{{ __('messages.invoice_date') }}</label>
                    <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-900/20" id="date" name="date" type="date" value="{{ $date }}">
                </div>
                <div class="flex items-end gap-2">
                    <button class="inline-flex size-11 items-center justify-center rounded-xl bg-amber-500 text-sm font-semibold text-stone-950 transition hover:bg-amber-400" title="{{ __('messages.filter') }}" type="submit">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    @if ($date)
                        <a class="inline-flex size-11 items-center justify-center rounded-xl border border-stone-200 text-sm font-semibold text-stone-700 transition hover:bg-stone-100" href="{{ route('incoming-check-tasks.index', ['status' => $status]) }}" title="{{ __('messages.clear') }}">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </div>
            </form>
        @endif

        <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
            <table class="responsive-table min-w-full text-sm">
                <thead class="bg-stone-50 text-left text-stone-600">
                    <tr>
                        <th class="px-6 py-4 font-medium">{{ __('messages.invoice_date') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.linked_master_item') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.vendor') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.expected_quantity') }}</th>
                        @unless ($isPendingView)
                            <th class="px-6 py-4 font-medium">{{ __('messages.checked_quantity') }}</th>
                            <th class="px-6 py-4 font-medium">{{ __('messages.status') }}</th>
                        @endunless
                        <th class="px-6 py-4 font-medium">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tasks as $task)
                        <tr>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.invoice_date') }}">{{ $task->invoiceEntry?->invoice_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-6 py-4 font-medium text-stone-900" data-label="{{ __('messages.linked_master_item') }}">{{ $task->masterItem?->official_name ?? '-' }}</td>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.vendor') }}">{{ $task->invoiceEntry?->vendor ?? '-' }}</td>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.expected_quantity') }}">{{ $task->expected_quantity }}</td>
                            @unless ($isPendingView)
                                <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.checked_quantity') }}">{{ $task->checked_quantity ?? '-' }}</td>
                                <td class="px-6 py-4" data-label="{{ __('messages.status') }}">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $task->status === 'pending' ? 'bg-stone-100 text-stone-700' : ($task->status === 'ok' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700') }}">
                                        {{ $task->status === 'pending' ? __('messages.incoming_check_pending') : ($task->status === 'ok' ? __('messages.incoming_check_ok') : __('messages.incoming_check_mismatch')) }}
                                    </span>
                                </td>
                            @endunless
                            <td class="px-6 py-4" data-label="{{ __('messages.actions') }}">
                                <div class="flex items-center gap-2">
                                    <a class="inline-flex size-9 items-center justify-center rounded-lg border border-stone-200 text-stone-700 transition hover:bg-stone-100" href="{{ route('incoming-check-tasks.show', $task) }}" title="{{ __('messages.view') }}">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                    @if (auth()->user()->hasRole(\App\Models\Role::ADMIN))
                                        <form method="POST" action="{{ route('incoming-check-tasks.update', $task) }}">
                                            @csrf
                                            <input name="checked_quantity" type="hidden" value="{{ $task->expected_quantity }}">
                                            <input name="notes" type="hidden" value="">
                                            <input name="redirect_to" type="hidden" value="index">
                                            <button class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-700 text-xs text-white transition hover:bg-rose-600" title="{{ __('messages.quick_confirm') }}" type="submit">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-6 py-8 text-center text-stone-500" colspan="{{ $isPendingView ? 5 : 7 }}">{{ __('messages.no_incoming_check_tasks') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $tasks->links() }}
    </div>
@endsection
