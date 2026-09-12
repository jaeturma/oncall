<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\AccountType;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    /**
     * Mobile registration. The `role` allow-list is the same hard-coded
     * SERVICE_FINDER/SERVICE_PROVIDER pair as the web registration form —
     * there is no way to self-register a back-office role from either
     * surface.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users'],
            'role' => ['required', 'in:SERVICE_FINDER,SERVICE_PROVIDER'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'sponsor_email' => ['nullable', 'email', 'exists:users,email'],
        ]);
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
        $user->refresh();

        return response()->json([
            'data' => new UserResource($user),
            'token' => $user->createToken('mobile')->plainTextToken,
        ], 201);
    }

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
