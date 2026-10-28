<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasColumn('roles', 'permissions')) {
            return;
        }

        $granularPermissions = ['payments.import', 'payments.manage', 'bills.sync'];

        DB::table('roles')->orderBy('id')->get(['id', 'key', 'permissions'])->each(function ($role) use ($granularPermissions) {
            $permissions = json_decode((string) $role->permissions, true);
            $permissions = is_array($permissions) ? $permissions : [];

            if ($role->key === 'admin') {
                $permissions = array_values(array_unique([...$permissions, ...$granularPermissions]));
            } else {
                $permissions = array_values(array_diff($permissions, ['payments.verify_transfer']));
            }

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasColumn('roles', 'permissions')) {
            return;
        }

        $granularPermissions = ['payments.import', 'payments.manage', 'bills.sync'];

        DB::table('roles')->orderBy('id')->get(['id', 'permissions'])->each(function ($role) use ($granularPermissions) {
            $permissions = json_decode((string) $role->permissions, true);
            $permissions = is_array($permissions) ? $permissions : [];

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_diff($permissions, $granularPermissions))),
                'updated_at' => now(),
            ]);
        });
    }
};
