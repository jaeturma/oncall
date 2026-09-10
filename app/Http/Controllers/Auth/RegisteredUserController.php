<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
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
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'role' => UserRole::from($data['role']), 'password' => $data['password'], 'sponsor_user_id' => $sponsor?->id]);
        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
