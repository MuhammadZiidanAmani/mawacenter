<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const DEFAULTS = [
        'admin' => 'Super Admin',
        'kasir' => 'Petugas',
        'bendahara' => 'Bendahara Unit',
        'orang_tua' => 'Wali Santri',
    ];

    public const PERMISSIONS = [
        'dashboard.view' => 'Dashboard',
        'students.view' => 'Manajemen Siswa',
        'payments.cash.create' => 'Transaksi Cash',
        'payments.transfer.submit_guardian' => 'Transfer Wali Santri',
        'payments.verify_transfer' => 'Verifikasi Transfer',
        'payments.view_own' => 'Riwayat Transaksi Sendiri',
        'payments.view_unit' => 'Riwayat Transaksi Unit',
        'bills.view' => 'Tagihan Semua Siswa',
        'bills.view_unit' => 'Tagihan Unit',
        'bills.view_guardian' => 'Tagihan Wali Santri',
        'reports.view' => 'Laporan Semua Unit',
        'reports.view_unit' => 'Laporan Unit',
        'reports.export' => 'Export Laporan',
        'master.manage' => 'Data Master',
        'users.manage' => 'Data User dan Role',
        'settings.view' => 'Pengaturan Akun',
    ];

    protected $fillable = ['key', 'name', 'description', 'permissions', 'is_active'];

    protected function casts(): array
    {
        return ['permissions' => 'array', 'is_active' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'key');
    }

    public static function options(bool $activeOnly = true): array
    {
        $query = static::query()->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $options = $query->pluck('name', 'key')->all();

        return $options ?: self::DEFAULTS;
    }

    public static function defaultPermissions(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    public static function defaultPermissionsFor(string $roleKey): array
    {
        return match ($roleKey) {
            'admin' => self::defaultPermissions(),
            'kasir' => ['payments.cash.create', 'payments.view_own', 'bills.view', 'settings.view'],
            'bendahara' => ['dashboard.view', 'payments.view_unit', 'bills.view_unit', 'reports.view_unit', 'settings.view'],
            'orang_tua' => ['payments.transfer.submit_guardian', 'bills.view_guardian', 'settings.view'],
            default => [],
        };
    }

    public function permissionLabels(): array
    {
        return collect($this->permissions ?? [])
            ->map(fn (string $permission) => self::PERMISSIONS[$permission] ?? null)
            ->filter()
            ->values()
            ->all();
    }
}
