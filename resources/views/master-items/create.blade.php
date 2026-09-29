@extends('layouts.app', ['title' => __('messages.create_master_item')])

@section('content')
    <div class="mx-auto max-w-5xl rounded-3xl border border-stone-200 bg-white p-8 shadow-sm">
        <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.create_master_item') }}</h1>
        <p class="mt-2 text-sm text-stone-600">{{ __('messages.structured_identity') }}</p>

        <form class="mt-8" method="POST" action="{{ route('master-items.store') }}">
            @php($showSellingPrice = false)
            @include('master-items._form')
        </form>
    </div>
@endsection
