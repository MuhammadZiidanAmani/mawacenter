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
        $permissions = $this->permissionsForRoute($routeName);

        if ($permissions === [] || $routeName === 'logout') {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if ($request->user()?->hasPermission($permission)) {
                return $next($request);
            }
        }

        if ($request->user()?->isSuperAdmin()) {
            return $next($request);
        }

        if ($routeName === 'dashboard') {
            $user = $request->user();
            $redirect = match (true) {
                $user?->isPetugas() => route('finance.payments.index'),
                $user?->isBendaharaUnit() => route('finance.bills.index'),
                $user?->isGuardian() => route('finance.bills.index'),
                default => null,
            };
            if ($redirect) {
                return redirect($redirect);
            }
        }
        abort(403, 'Anda tidak memiliki hak akses ke menu ini.');
    }

    private function permissionsForRoute(string $routeName): array
    {
        return match (true) {
            $routeName === 'dashboard' => ['dashboard.view'],
            str_starts_with($routeName, 'student-management.') => ['students.view'],
            str_starts_with($routeName, 'finance.transfer-verifications.') => ['payments.verify_transfer'],
            in_array($routeName, ['finance.payments.import', 'finance.spp.import.preview', 'finance.spp.import', 'finance.other.import.preview', 'finance.other.import', 'finance.spp.correct', 'finance.spp.update', 'finance.spp.destroy', 'finance.other.update', 'finance.other.destroy'], true) => ['payments.verify_transfer'],
            in_array($routeName, ['finance.spp.store', 'finance.other.store', 'finance.spp.create', 'finance.other.create', 'finance.spp.months', 'finance.spp.quote', 'finance.other.months', 'finance.other.quote'], true) => ['payments.cash.create'],
            str_starts_with($routeName, 'finance.spp.'),
            str_starts_with($routeName, 'finance.other.'),
            str_starts_with($routeName, 'finance.payments.') => ['payments.cash.create', 'payments.view_unit'],
            $routeName === 'finance.bills.sync' => ['payments.verify_transfer'],
            $routeName === 'finance.bills.transfer' => ['payments.transfer.submit_guardian'],
            str_starts_with($routeName, 'finance.bills.') => ['bills.view', 'bills.view_unit', 'bills.view_guardian'],
            str_starts_with($routeName, 'guardian.') => ['bills.view_guardian', 'payments.transfer.submit_guardian'],
            str_starts_with($routeName, 'reports.export.') || $routeName === 'reports.export' => ['reports.export'],
            str_starts_with($routeName, 'reports.') => ['reports.view', 'reports.view_unit'],
            str_starts_with($routeName, 'master.') => ['master.manage', 'users.manage'],
            str_starts_with($routeName, 'settings.') => ['settings.view'],
            default => [],
        };
    }
}
