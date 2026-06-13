<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@mawacenter.id')],
            [
                'name' => env('ADMIN_NAME', 'Administrator MAWA Center'),
                'password' => env('ADMIN_PASSWORD', 'mawacenter123'),
                'email_verified_at' => now(),
            ],
        );
    }
}
