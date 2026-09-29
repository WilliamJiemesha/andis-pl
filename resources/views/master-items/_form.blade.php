@csrf

<div class="rounded-2xl bg-stone-50 px-5 py-4 text-sm text-stone-600">
    {{ __('messages.generated_official_name') }}
</div>

<div class="mt-6 grid gap-6 md:grid-cols-3">
    <div>
        <label class="mb-2 block text-sm font-medium text-stone-700" for="barang">{{ __('messages.barang') }}</label>
        <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm uppercase outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="barang" name="barang" type="text" value="{{ old('barang', $masterItem->barang ?? '') }}" data-uppercase required>
        @error('barang')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-stone-700" for="merk">{{ __('messages.merk') }}</label>
        <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm uppercase outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="merk" name="merk" type="text" value="{{ old('merk', $masterItem->merk ?? '') }}" data-uppercase required>
        @error('merk')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-stone-700" for="tipe">{{ __('messages.tipe') }}</label>
        <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm uppercase outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="tipe" name="tipe" type="text" value="{{ old('tipe', $masterItem->tipe ?? '') }}" data-uppercase required>
        @error('tipe')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
        @error('official_name')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 grid gap-6 md:grid-cols-2">
    <div>
        <label class="mb-2 block text-sm font-medium text-stone-700" for="alias_name">{{ __('messages.alias_name') }}</label>
        <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="alias_name" name="alias_name" type="text" value="{{ old('alias_name', $masterItem->alias_name ?? '') }}" required>
        @error('alias_name')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 grid gap-6 md:grid-cols-[1fr_auto]">
    @if ($showSellingPrice ?? false)
        <div>
            <label class="mb-2 block text-sm font-medium text-stone-700" for="selling_price">{{ __('messages.selling_price') }}</label>
            <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="selling_price" name="selling_price" type="text" inputmode="numeric" value="{{ old('selling_price', isset($masterItem) && $masterItem->selling_price !== null ? number_format((float) $masterItem->selling_price, 0, ',', '.') : '') }}" data-currency-input>
            @error('selling_price')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    @else
        <div>
            <label class="mb-2 block text-sm font-medium text-stone-700" for="notes">{{ __('messages.notes') }}</label>
            <textarea class="min-h-32 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="notes" name="notes">{{ old('notes', $masterItem->notes ?? '') }}</textarea>
            @error('notes')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div class="flex items-end">
        <label class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
            <input class="size-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" name="is_active" type="checkbox" value="1" @checked(old('is_active', $masterItem->is_active ?? true))>
            <span>{{ __('messages.active') }}</span>
        </label>
    </div>
</div>

@if ($showSellingPrice ?? false)
    <div class="mt-6">
        <label class="mb-2 block text-sm font-medium text-stone-700" for="notes">{{ __('messages.notes') }}</label>
        <textarea class="min-h-32 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="notes" name="notes">{{ old('notes', $masterItem->notes ?? '') }}</textarea>
        @error('notes')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>
@endif

<div class="mt-8 flex items-center gap-3">
    <button class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500" type="submit">
        {{ __('messages.save') }}
    </button>
    <a class="rounded-xl border border-stone-200 px-4 py-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100" href="{{ route('master-items.index') }}">
        {{ __('messages.cancel') }}
    </a>
</div>
