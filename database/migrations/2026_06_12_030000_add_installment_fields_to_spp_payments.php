<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spp_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('spp_payments', 'paid_amount')) {
                $table->unsignedBigInteger('paid_amount')->default(0)->after('total_amount');
            }

            if (!Schema::hasColumn('spp_payments', 'remaining_amount')) {
                $table->unsignedBigInteger('remaining_amount')->default(0)->after('paid_amount');
            }

            if (!Schema::hasColumn('spp_payments', 'payment_status')) {
                $table->string('payment_status', 30)->default('Belum Lunas')->after('remaining_amount');
            }
        });

        Schema::table('spp_payment_items', function (Blueprint $table) {
            if (!Schema::hasColumn('spp_payment_items', 'paid_amount')) {
                $table->unsignedBigInteger('paid_amount')->default(0)->after('total_amount');
            }

            if (!Schema::hasColumn('spp_payment_items', 'remaining_amount')) {
                $table->unsignedBigInteger('remaining_amount')->default(0)->after('paid_amount');
            }

            if (!Schema::hasColumn('spp_payment_items', 'payment_status')) {
                $table->string('payment_status', 30)->default('Belum Lunas')->after('remaining_amount');
            }

            $table->index(['student_id', 'year', 'month']);
        });

        DB::table('spp_payments')->update([
            'paid_amount' => DB::raw('total_amount'),
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);

        DB::table('spp_payment_items')->update([
            'paid_amount' => DB::raw('total_amount'),
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);
    }

    public function down(): void
    {
        Schema::table('spp_payment_items', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'remaining_amount', 'payment_status']);
        });

        Schema::table('spp_payments', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'remaining_amount', 'payment_status']);
        });
    }
};