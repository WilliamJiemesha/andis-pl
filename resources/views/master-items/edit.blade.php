@extends('layouts.app', ['title' => __('messages.edit_master_item')])

@section('content')
    <div class="mx-auto max-w-5xl rounded-3xl border border-stone-200 bg-white p-8 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.edit_master_item') }}</h1>
                <p class="mt-2 text-sm text-stone-600">{{ $masterItem->official_name }}</p>
            </div>
            <a class="rounded-xl border border-stone-200 px-4 py-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100" href="{{ route('master-items.index') }}">
                {{ __('messages.back') }}
            </a>
        </div>

        <form class="mt-8" method="POST" action="{{ route('master-items.update', $masterItem) }}">
            @method('PUT')
            @php($showSellingPrice = true)
            @include('master-items._form')
        </form>
    </div>
@endsection
