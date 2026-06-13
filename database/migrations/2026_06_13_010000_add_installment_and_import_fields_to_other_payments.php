<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('total_amount')->default(0)->after('discount_amount');
            $table->unsignedBigInteger('remaining_amount')->default(0)->after('paid_amount');
            $table->string('payment_status', 30)->default('Belum Lunas')->after('remaining_amount');
            $table->string('operator_name')->nullable()->after('status');
            $table->string('import_source')->nullable()->after('operator_name');
            $table->string('import_key', 64)->nullable()->unique()->after('import_source');
        });

        DB::table('other_payments')->update([
            'total_amount' => DB::raw('original_amount - discount_amount'),
            'remaining_amount' => DB::raw('CASE WHEN original_amount - discount_amount > paid_amount THEN original_amount - discount_amount - paid_amount ELSE 0 END'),
            'payment_status' => DB::raw("CASE WHEN paid_amount >= original_amount - discount_amount THEN 'Lunas' ELSE 'Belum Lunas' END"),
        ]);
    }

    public function down(): void
    {
        Schema::table('other_payments', function (Blueprint $table) {
            $table->dropUnique(['import_key']);
            $table->dropColumn(['total_amount', 'remaining_amount', 'payment_status', 'operator_name', 'import_source', 'import_key']);
        });
    }
};
