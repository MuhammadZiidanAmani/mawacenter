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
        $permissions = $this->permissionsForRoute($request, $routeName);

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

    private function permissionsForRoute(Request $request, string $routeName): array
    {
        return match (true) {
            $routeName === 'dashboard' => ['dashboard.view'],
            in_array($routeName, ['student-management.index', 'student-management.students.index', 'student-management.alumni.index', 'student-management.data-quality.index'], true) => ['students.view'],
            $routeName === 'student-management.students.create' => ['students.create'],
            $routeName === 'student-management.students.import' => ['students.import'],
            $routeName === 'student-management.students.edit' => ['students.update'],
            str_starts_with($routeName, 'student-management.students.class-alumni.') => ['students.alumni'],
            str_starts_with($routeName, 'student-management.class-transfer.') => ['students.movement'],
            str_starts_with($routeName, 'student-management.class-promotion.') => ['students.movement'],
            str_starts_with($routeName, 'student-management.identity-cleanup.') => ['students.identity_cleanup'],
            $routeName === 'master.index' && $request->query('tab') === 'students' => ['students.view'],
            $routeName === 'master.create' && $request->query('tab') === 'students' => ['students.create'],
            $routeName === 'master.students.store' => ['students.create'],
            $routeName === 'master.students.update' => ['students.update'],
            $routeName === 'master.students.export' => ['students.export'],
            in_array($routeName, ['master.students.template', 'master.students.import.preview', 'master.students.import'], true) => ['students.import'],
            str_starts_with($routeName, 'finance.transfer-verifications.') => ['payments.verify_transfer'],
            in_array($routeName, ['finance.payments.import', 'finance.spp.import.preview', 'finance.spp.import', 'finance.other.import.preview', 'finance.other.import', 'finance.spp.correct', 'finance.spp.update', 'finance.spp.destroy', 'finance.other.update', 'finance.other.destroy'], true) => ['payments.verify_transfer'],
            $routeName === 'finance.payments.store' => ['payments.cash.create'],
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
            $routeName === 'master.index' => $this->permissionsForMasterType((string) $request->query('tab', 'academic-years')),
            $routeName === 'master.create' => $this->permissionsForMasterType((string) $request->query('tab', 'academic-years')),
            in_array($routeName, ['master.data-roles.store', 'master.data-roles.update', 'master.data-users.store', 'master.data-users.update'], true) => ['users.manage'],
            $routeName === 'master.destroy' => $this->permissionsForMasterType((string) $request->route('type')),
            str_starts_with($routeName, 'master.') => ['master.manage'],
            str_starts_with($routeName, 'settings.') => ['settings.view'],
            default => [],
        };
    }

    private function permissionsForMasterType(string $type): array
    {
        return in_array($type, ['data-roles', 'data-users'], true)
            ? ['users.manage']
            : ['master.manage'];
    }
}
