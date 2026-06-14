<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_types', function (Blueprint $table) {
            $table->string('payment_group', 30)->default('lain-lain')->after('academic_year_id');
        });

        DB::table('fee_types')
            ->where('code', 'DAFTAR-ULANG')
            ->orWhere('code', 'like', 'DAFTAR-ULANG-%')
            ->update(['payment_group' => 'daftar-ulang']);

        DB::table('fee_types')
            ->where('name', 'like', '%Laundry%')
            ->update(['payment_group' => 'laundry']);
    }

    public function down(): void
    {
        Schema::table('fee_types', function (Blueprint $table) {
            $table->dropColumn('payment_group');
        });
    }
};
