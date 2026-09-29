@csrf

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <label class="mb-2 block text-sm font-medium text-stone-700" for="name">{{ __('messages.name') }}</label>
        <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="name" name="name" type="text" value="{{ old('name', $user->name ?? '') }}" required>
        @error('name')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-stone-700" for="email">{{ __('messages.email') }}</label>
        <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required>
        @error('email')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6">
    <label class="mb-2 block text-sm font-medium text-stone-700" for="password">{{ __('messages.password') }}</label>
    <input class="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" id="password" name="password" type="password" {{ isset($user) ? '' : 'required' }}>
    <p class="mt-2 text-sm text-stone-500">{{ isset($user) ? __('messages.password_hint_edit') : __('messages.password_hint_create') }}</p>
    @error('password')
        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
    @enderror
</div>

<fieldset class="mt-6">
    <legend class="text-sm font-medium text-stone-700">{{ __('messages.assigned_roles') }}</legend>
    <div class="mt-3 grid gap-3 md:grid-cols-2">
        @foreach ($roles as $role)
            <label class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                <input
                    class="size-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500"
                    name="roles[]"
                    type="checkbox"
                    value="{{ $role->id }}"
                    @checked(in_array($role->id, old('roles', isset($user) ? $user->roles->pluck('id')->all() : []), true))
                >
                <span>{{ $role->label }}</span>
            </label>
        @endforeach
    </div>
    @error('roles')
        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
    @enderror
</fieldset>

<div class="mt-8 flex items-center gap-3">
    <button class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500" type="submit">
        {{ __('messages.save') }}
    </button>
    <a class="rounded-xl border border-stone-200 px-4 py-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100" href="{{ route('users.index') }}">
        {{ __('messages.cancel') }}
    </a>
</div>
