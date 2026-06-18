<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const DEFAULTS = [
        'admin' => 'Admin',
        'kasir' => 'Kasir',
        'bendahara' => 'Bendahara',
        'orang_tua' => 'Orang Tua',
    ];

    public const PERMISSIONS = [
        'dashboard' => 'Dashboard',
        'students' => 'Manajemen Siswa',
        'payments' => 'Pembayaran',
        'bills' => 'Tagihan',
        'reports' => 'Laporan',
        'master' => 'Data Master',
        'settings' => 'Pengaturan Akun',
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

    public function permissionLabels(): array
    {
        return collect($this->permissions ?? [])
            ->map(fn (string $permission) => self::PERMISSIONS[$permission] ?? null)
            ->filter()
            ->values()
            ->all();
    }
}
