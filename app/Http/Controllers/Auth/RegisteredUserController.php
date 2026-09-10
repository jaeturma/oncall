<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AccountType;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users'], 'phone' => ['nullable', 'string', 'max:30', 'unique:users'], 'role' => ['required', 'in:SERVICE_FINDER,SERVICE_PROVIDER'], 'password' => ['required', 'confirmed', Password::defaults()], 'sponsor_email' => ['nullable', 'email', 'exists:users,email']]);
        $sponsor = filled($data['sponsor_email'] ?? null) ? User::where('email', $data['sponsor_email'])->first() : null;
        $role = UserRole::from($data['role']);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $role,
            'password' => $data['password'],
            'sponsor_user_id' => $sponsor?->id,
            'account_type_id' => $this->defaultAccountTypeId($role),
        ]);
        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('dashboard');
    }

    /**
     * Baseline account type for a new registration: the role-specific slug if an
     * admin has configured one, otherwise the first active type, otherwise none.
     */
    private function defaultAccountTypeId(UserRole $role): ?int
    {
        $preferredSlug = match ($role) {
            UserRole::ServiceProvider => 'service-provider',
            UserRole::ServiceFinder => 'service-finder',
            default => null,
        };

        return AccountType::query()
            ->where('active', true)
            ->when($preferredSlug, fn ($query) => $query->orderByRaw('slug = ? desc', [$preferredSlug]))
            ->orderBy('id')
            ->value('id');
    }
}
