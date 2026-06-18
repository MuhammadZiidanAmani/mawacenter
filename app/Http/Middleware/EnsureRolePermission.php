<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRolePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = (string) $request->route()?->getName();
        $permission = $this->permissionForRoute($routeName);

        if (! $permission || $routeName === 'logout') {
            return $next($request);
        }

        if ($request->user()?->hasPermission($permission)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki hak akses ke menu ini.');
    }

    private function permissionForRoute(string $routeName): ?string
    {
        return match (true) {
            $routeName === 'dashboard' => 'dashboard',
            str_starts_with($routeName, 'student-management.') => 'students',
            str_starts_with($routeName, 'finance.spp.'),
            str_starts_with($routeName, 'finance.other.') => 'payments',
            str_starts_with($routeName, 'finance.bills.') => 'bills',
            str_starts_with($routeName, 'reports.') => 'reports',
            str_starts_with($routeName, 'master.') => 'master',
            str_starts_with($routeName, 'settings.') => 'settings',
            default => null,
        };
    }
}
