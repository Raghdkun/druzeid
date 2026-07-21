<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return inertia('cms/users/index', [
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'created_at'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'createdAt' => $user->created_at?->toIso8601String(),
                    'isSelf' => $user->id === request()->user()?->id,
                ]),
            'roles' => [User::ROLE_ADMIN, User::ROLE_EDITOR],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_EDITOR])],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(), // invited by an admin — no self-verify step
        ]);

        return back()->with('success', __('User created.'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_EDITOR])],
        ]);

        // Guard against an admin demoting themselves into a lockout, and
        // against removing the last admin.
        if ($user->id === $request->user()->id && $validated['role'] !== User::ROLE_ADMIN) {
            return back()->with('error', __('You cannot change your own role.'));
        }

        if ($user->isAdmin()
            && $validated['role'] !== User::ROLE_ADMIN
            && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return back()->with('error', __('At least one administrator is required.'));
        }

        $user->update($validated);

        return back()->with('success', __('User updated.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', __('You cannot delete your own account.'));
        }

        if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return back()->with('error', __('At least one administrator is required.'));
        }

        $user->delete();

        return back()->with('success', __('User deleted.'));
    }
}
