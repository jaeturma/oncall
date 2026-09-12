<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a fully suspended account from every authenticated route so a
 * suspension cannot be bypassed by calling a URL directly. Narrower
 * enforcement states (Warning, Under Review, Temporary Restriction) are
 * capability-scoped and handled by policies via User::isCapabilityRestricted().
 */
class EnsureAccountIsActive
{
    /**
     * Route names a suspended user may still reach (to read the case and appeal).
     *
     * @var list<string>
     */
    private const ALLOWED_WHILE_SUSPENDED = [
        'enforcement-cases.index',
        'enforcement-cases.show',
        'enforcement-cases.appeal',
        'logout',
        'api.enforcement-cases.index',
        'api.enforcement-cases.show',
        'api.enforcement-cases.appeal',
        'api.auth.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->status !== UserStatus::Suspended || $request->routeIs(self::ALLOWED_WHILE_SUSPENDED)) {
            return $next($request);
        }

        $message = 'Your account is suspended. Review the case details and submit an appeal if you disagree.';

        if ($request->is('api/*')) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->route('enforcement-cases.index')->with('status', $message);
    }
}
