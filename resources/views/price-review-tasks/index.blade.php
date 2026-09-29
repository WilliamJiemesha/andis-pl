@extends('layouts.app', ['title' => __('messages.price_review')])

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.price_review') }}</h1>
            <p class="mt-2 text-sm text-stone-600">{{ __('messages.price_review_help') }}</p>
        </div>

        <div class="quick-filter">
            <a class="{{ $status === 'open' ? 'active' : '' }}" href="{{ route('price-review-tasks.index', ['status' => 'open']) }}">{{ __('messages.open_tickets') }}</a>
            <a class="{{ $status === 'reviewed' ? 'active' : '' }}" href="{{ route('price-review-tasks.index', ['status' => 'reviewed']) }}">{{ __('messages.reviewed_price_tasks') }}</a>
        </div>

        <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
            <table class="responsive-table min-w-full text-sm">
                <thead class="bg-stone-50 text-left text-stone-600">
                    <tr>
                        <th class="px-6 py-4 font-medium">{{ __('messages.official_name') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.vendor') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.cost_code') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.selling_price') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.status') }}</th>
                        <th class="px-6 py-4 font-medium">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tasks as $task)
                        <tr>
                            <td class="px-6 py-4 font-medium text-stone-900" data-label="{{ __('messages.official_name') }}">{{ $task->masterItem?->official_name ?? '-' }}</td>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.vendor') }}">{{ $task->invoiceEntry?->vendor ?? $task->costHistory?->vendor ?? '-' }}</td>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.cost_code') }}">{{ $task->costHistory?->cost_code ?: '-' }}</td>
                            <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.selling_price') }}">{{ \App\Support\Currency::rupiah($task->current_selling_price) }}</td>
                            <td class="px-6 py-4" data-label="{{ __('messages.status') }}">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $task->status === 'reviewed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $task->status === 'reviewed' ? __('messages.reviewed_price_tasks') : __('messages.open_tickets') }}
                                </span>
                            </td>
                            <td class="px-6 py-4" data-label="{{ __('messages.actions') }}">
                                <a class="inline-flex size-9 items-center justify-center rounded-lg border border-stone-200 text-stone-700 transition hover:bg-stone-100" href="{{ route('price-review-tasks.show', $task) }}" title="{{ __('messages.view') }}">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-6 py-8 text-center text-stone-500" colspan="6">{{ __('messages.no_price_review_tasks') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $tasks->links() }}
    </div>
@endsection
