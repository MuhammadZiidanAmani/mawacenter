<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 100)->nullable()->unique()->after('name');
        });

        $usedUsernames = [];

        DB::table('users')->select(['id', 'email'])->orderBy('id')->each(function (object $user) use (&$usedUsernames): void {
            $base = Str::of(Str::before($user->email, '@'))
                ->lower()
                ->replaceMatches('/[^a-z0-9_-]+/', '-')
                ->trim('-_')
                ->limit(80, '')
                ->value();
            $base = $base !== '' ? $base : 'user-'.$user->id;
            $username = $base;

            if (isset($usedUsernames[$username])) {
                $username = $base.'-'.$user->id;
            }

            $usedUsernames[$username] = true;
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 100)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
