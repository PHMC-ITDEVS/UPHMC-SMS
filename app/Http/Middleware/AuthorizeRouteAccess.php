<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;

class AuthorizeRouteAccess
{
    protected array $routePermissions = [
        'account.' => Permission::MANAGE_USERS->value,
        'department.' => Permission::MANAGE_USERS->value,
        'position.' => Permission::MANAGE_USERS->value,
        'role.' => Permission::MANAGE_ROLES->value,
        'audit-trail.' => Permission::MANAGE_ROLES->value,
        'phonebook.' => Permission::MANAGE_CONTACTS->value,
        'contact_group.' => Permission::MANAGE_GROUPS->value,
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($this->isExemptRoute($request) || $this->isAdmin($user)) {
            return $next($request);
        }

        $requiredPermission = $this->resolvePermissionFromRoute($request->route()?->getName());

        if ($requiredPermission === null) {
            return $next($request);
        }

        if ($user->isAbleTo($requiredPermission)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => 0,
                'message' => 'You do not have access to this page.',
            ], 403);
        }

        return redirect()->route('dashboard')->with('message', [
            'type' => 'error',
            'text' => 'You do not have access to that page.',
        ]);
    }

    protected function isAdmin($user): bool
    {
        return strtolower((string) $user->role_name) === 'admin';
    }

    protected function isExemptRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return true;
        }

        return in_array($routeName, [
            'dashboard',
            'search.autocomplete',
            'profile.index',
            'profile.update',
            'logout',
        ], true);
    }

    protected function resolvePermissionFromRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        if (str_starts_with($routeName, 'sms.')) {
            return $this->resolveSmsPermission($routeName);
        }

        foreach ($this->routePermissions as $prefix => $permission) {
            if (str_starts_with($routeName, $prefix)) {
                return $permission;
            }
        }

        return null;
    }

    protected function resolveSmsPermission(string $routeName): string
    {
        $viewRoutes = [
            'sms.index',
            'sms.list',
            'sms.get',
        ];

        if (in_array($routeName, $viewRoutes, true)) {
            return Permission::VIEW_SMS_LOGS->value;
        }

        return Permission::SEND_SMS->value;
    }
}
