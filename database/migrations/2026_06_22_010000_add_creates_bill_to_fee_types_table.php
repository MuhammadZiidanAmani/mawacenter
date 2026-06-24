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
            $table->boolean('creates_bill')->default(true)->after('period');
        });

        DB::table('fee_types')
            ->where(function ($query) {
                $query->where('payment_group', 'laundry')->orWhere('name', 'like', '%Laundry%');
            })
            ->update(['creates_bill' => false]);
    }

    public function down(): void
    {
        Schema::table('fee_types', function (Blueprint $table) {
            $table->dropColumn('creates_bill');
        });
    }
};
