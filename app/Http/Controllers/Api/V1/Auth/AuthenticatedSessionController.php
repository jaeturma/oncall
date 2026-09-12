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
     * Budget/Cashier — Verifier/Enforcement/Super Admin/Maintenance all
     * collapse into Admin per ADR-001) can never obtain a mobile token even
     * with valid credentials — this is what makes `/api/v1` marketplace-only
     * at the door, on top of the `can:use-mobile` gate on every route behind
     * it (Phase D).
     *
     * Account status (suspended) and capability restrictions are deliberately
     * NOT checked here, matching the web login: a suspended marketplace user
     * can still authenticate so they can reach `/api/v1/enforcement-cases`
     * and appeal (see `EnsureAccountIsActive`'s allow-list) — every other
     * route still blocks them. Narrower `RestrictedCapability` states are
     * enforced per-action by policies, not at login, same as on the web.
     */
    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials do not match our records.']]);
        }

        if (! $user->canUseMobile()) {
            throw ValidationException::withMessages(['email' => ['Your account is authorized for the Oncall Philippines web administration portal only.']]);
        }

        return response()->json([
            'data' => new UserResource($user),
            'token' => $user->createToken('mobile')->plainTextToken,
        ]);
    }

    /**
     * Revokes only the token used for this request (current-device logout).
     * Sanctum's per-token model (one named token per device/session) already
     * makes a future "log out of all devices" trivial to add — a single
     * `$request->user()->tokens()->delete()` — without any structural change
     * here, so no such endpoint is added until it's actually needed.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
