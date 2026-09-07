<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * /cpanel is staff operations for the storefront. Storefront customers
 * share the web guard, so an authenticated shopper must still be blocked.
 */
final class EnsureCpanelIsStaffOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isCpanelRequest($request)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (self::userIsStaff($user)) {
            return $next($request);
        }

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('dashboard');
    }

    public static function userIsStaff(mixed $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        try {
            return $user->hasPermissionTo('access_dashboard');
        } catch (\Throwable) {
            return false;
        }
    }

    private function isCpanelRequest(Request $request): bool
    {
        $prefix = trim((string) config('shopper.admin.prefix', 'cpanel'), '/');

        return $prefix !== '' && ($request->is($prefix) || $request->is($prefix.'/*'));
    }
}
