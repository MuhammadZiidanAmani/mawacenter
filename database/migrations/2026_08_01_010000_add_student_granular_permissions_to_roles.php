<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $studentPermissions = [
            'students.view',
            'students.create',
            'students.update',
            'students.import',
            'students.export',
            'students.movement',
            'students.alumni',
            'students.identity_cleanup',
        ];

        DB::table('roles')->orderBy('id')->get(['id', 'key', 'permissions'])->each(function ($role) use ($studentPermissions) {
            $permissions = json_decode((string) $role->permissions, true);
            $permissions = is_array($permissions) ? $permissions : [];

            if ($role->key === 'admin' || in_array('students', $permissions, true) || in_array('students.view', $permissions, true)) {
                $permissions = array_values(array_unique([...$permissions, ...$studentPermissions]));
            }

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        $studentGranular = [
            'students.create',
            'students.update',
            'students.import',
            'students.export',
            'students.movement',
            'students.alumni',
            'students.identity_cleanup',
        ];

        DB::table('roles')->orderBy('id')->get(['id', 'permissions'])->each(function ($role) use ($studentGranular) {
            $permissions = json_decode((string) $role->permissions, true);
            $permissions = is_array($permissions) ? $permissions : [];

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_diff($permissions, $studentGranular))),
                'updated_at' => now(),
            ]);
        });
    }
};
