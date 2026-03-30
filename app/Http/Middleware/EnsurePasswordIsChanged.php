<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        $allowedRoutes = [
            'profile.index',
            'profile.update',
            'password.update',
            'logout',
        ];

        if ($request->route()?->named($allowedRoutes)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 0,
                'message' => 'Password change required before continuing.',
            ], 423);
        }

        return redirect()->route('profile.index');
    }
}
