@extends('layouts.app', ['title' => __('messages.price_review')])

@section('content')
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.price_review') }}</h1>
                <p class="mt-2 text-sm text-stone-600">{{ __('messages.price_review_help') }}</p>
            </div>
            @if ($status === \App\Models\PriceReviewTask::STATUS_OPEN && $tasks->isNotEmpty())
                <div class="flex items-center gap-2" data-price-review-toolbar>
                    <button class="inline-flex size-10 items-center justify-center rounded-lg border border-stone-200 text-stone-700 transition hover:bg-stone-100" data-price-review-edit-toggle type="button" title="{{ __('messages.edit') }}">
                        <i class="fa-regular fa-pen-to-square"></i>
                    </button>
                    <button class="hidden rounded-lg bg-amber-500 px-3 py-2 text-xs font-semibold text-stone-950 transition hover:bg-amber-400" data-price-review-submit type="submit" form="price-review-batch-form">
                        {{ __('messages.save') }}
                    </button>
                    <button class="hidden rounded-lg border border-stone-200 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-100" data-price-review-cancel type="button">
                        {{ __('messages.cancel') }}
                    </button>
                </div>
            @endif
        </div>

        <div class="quick-filter">
            <a class="{{ $status === 'open' ? 'active' : '' }}" href="{{ route('price-review-tasks.index', ['status' => 'open']) }}">{{ __('messages.open_tickets') }}</a>
            <a class="{{ $status === 'reviewed' ? 'active' : '' }}" href="{{ route('price-review-tasks.index', ['status' => 'reviewed']) }}">{{ __('messages.reviewed_price_tasks') }}</a>
        </div>

        <form id="price-review-batch-form" method="POST" action="{{ route('price-review-tasks.batch-review') }}" data-price-review-batch-form>
            @csrf
            <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
                <table class="responsive-table min-w-full text-sm">
                    <thead class="bg-stone-50 text-left text-stone-600">
                        <tr>
                            <th class="px-6 py-4 font-medium">{{ __('messages.official_name') }}</th>
                            <th class="px-6 py-4 font-medium">{{ __('messages.vendor') }}</th>
                            <th class="px-6 py-4 font-medium">{{ __('messages.cost_code') }}</th>
                            <th class="px-6 py-4 font-medium">
                                <span data-price-review-normal>{{ __('messages.selling_price') }}</span>
                                <span class="hidden" data-price-review-edit>{{ __('messages.previous_selling_price_short') }}</span>
                            </th>
                            <th class="px-6 py-4 font-medium" data-price-review-normal>{{ __('messages.status') }}</th>
                            <th class="px-6 py-4 font-medium" data-price-review-normal>{{ __('messages.actions') }}</th>
                            <th class="hidden px-6 py-4 font-medium" data-price-review-edit>{{ __('messages.next_selling_price_short') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tasks as $task)
                            <tr>
                                <td class="px-6 py-4 font-medium text-stone-900" data-label="{{ __('messages.official_name') }}">{{ $task->masterItem?->alias_name ?? $task->masterItem?->official_name ?? '-' }}</td>
                                <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.vendor') }}">{{ $task->invoiceEntry?->vendor ?? $task->costHistory?->vendor ?? '-' }}</td>
                                <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.cost_code') }}">{{ $task->costHistory?->cost_code ?: '-' }}</td>
                                <td class="px-6 py-4 text-stone-600" data-label="{{ __('messages.selling_price') }}">{{ \App\Support\Currency::rupiah($task->current_selling_price) }}</td>
                                <td class="px-6 py-4" data-label="{{ __('messages.status') }}" data-price-review-normal>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $task->status === 'reviewed' ? 'app-positive-badge' : 'bg-amber-50 text-amber-700' }}">
                                        {{ $task->status === 'reviewed' ? __('messages.reviewed_price_tasks') : __('messages.open_tickets') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4" data-label="{{ __('messages.actions') }}" data-price-review-normal>
                                    <a class="inline-flex size-9 items-center justify-center rounded-lg border border-stone-200 text-stone-700 transition hover:bg-stone-100" href="{{ route('price-review-tasks.show', $task) }}" title="{{ __('messages.view') }}">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                </td>
                                <td class="hidden px-6 py-4" data-label="{{ __('messages.next_selling_price_short') }}" data-price-review-edit>
                                    @if ($task->status === \App\Models\PriceReviewTask::STATUS_OPEN)
                                        <input class="w-full min-w-36 rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-900/20" name="reviews[{{ $task->id }}][new_selling_price]" type="text" inputmode="numeric" placeholder="{{ __('messages.skip_if_blank') }}" data-currency-input>
                                    @else
                                        <span class="text-stone-500">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-6 py-8 text-center text-stone-500" colspan="7">{{ __('messages.no_price_review_tasks') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        {{ $tasks->links() }}
    </div>
@endsection
