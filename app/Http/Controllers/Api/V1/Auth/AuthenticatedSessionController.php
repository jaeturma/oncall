<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Token login for the mobile app. Deliberately does not use `Auth::attempt()`
     * (a stateful-guard session login) since `/api/v1` is stateless token auth,
     * not the `web` session guard.
     *
     * Crucially, a correct password is not enough: `canUseMobile()` is checked
     * after credentials verify, so a back-office account (Admin/Accounting/
     * Budget/Cashier) can never obtain a mobile token even with valid
     * credentials — this is what makes `/api/v1` marketplace-only at the door,
     * on top of the `can:use-mobile` gate on every route behind it.
     */
    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials do not match our records.']]);
        }

        if (! $user->canUseMobile()) {
            throw ValidationException::withMessages(['email' => ['This account cannot sign in to the mobile app.']]);
        }

        return response()->json([
            'data' => new UserResource($user),
            'token' => $user->createToken('mobile')->plainTextToken,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
