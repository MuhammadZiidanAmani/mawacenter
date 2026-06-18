<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $this->renameRole('bendahara_perunit', 'bendahara', 'Bendahara');
        $this->renameRole('wali_murid_siswa', 'orang_tua', 'Orang Tua');

        foreach ([
            'admin' => 'Admin',
            'kasir' => 'Kasir',
            'bendahara' => 'Bendahara',
            'orang_tua' => 'Orang Tua',
        ] as $key => $name) {
            DB::table('roles')->updateOrInsert(
                ['key' => $key],
                [
                    'name' => $name,
                    'description' => 'Role bawaan sistem',
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $this->renameRole('bendahara', 'bendahara_perunit', 'Bendahara Perunit');
        $this->renameRole('orang_tua', 'wali_murid_siswa', 'Wali Murid/Siswa');
    }

    private function renameRole(string $from, string $to, string $name): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->where('role', $from)->update(['role' => $to]);
        }

        $fromExists = DB::table('roles')->where('key', $from)->exists();
        $toExists = DB::table('roles')->where('key', $to)->exists();

        if ($fromExists && ! $toExists) {
            DB::table('roles')->where('key', $from)->update([
                'key' => $to,
                'name' => $name,
                'updated_at' => now(),
            ]);

            return;
        }

        if ($fromExists && $toExists) {
            DB::table('roles')->where('key', $from)->delete();
            DB::table('roles')->where('key', $to)->update([
                'name' => $name,
                'updated_at' => now(),
            ]);
        }
    }
};
