<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()
                ->with('roles')
                ->withCount(['notifications as unread_notifications_count' => fn ($builder) => $builder->whereNull('read_at')])
                ->orderBy('name')
                ->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => Role::query()->orderBy('label')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
        $user->syncRoles($validated['roles']);

        return redirect()
            ->route('users.index')
            ->with('status', __('messages.user_created'));
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::query()->orderBy('label')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->syncRoles($validated['roles']);

        return redirect()
            ->route('users.index')
            ->with('status', __('messages.user_updated'));
    }
}
