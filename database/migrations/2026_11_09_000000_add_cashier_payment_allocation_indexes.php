<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $sppIndexes = collect(Schema::getIndexes('spp_payments'))->pluck('name');
        Schema::table('spp_payments', function (Blueprint $table) use ($sppIndexes): void {
            if (! $sppIndexes->contains('spp_payments_student_status_date_index')) {
                $table->index(['student_id', 'status', 'transaction_at'], 'spp_payments_student_status_date_index');
            }
        });

        $otherIndexes = collect(Schema::getIndexes('other_payments'))->pluck('name');
        Schema::table('other_payments', function (Blueprint $table) use ($otherIndexes): void {
            if (! $otherIndexes->contains('other_payments_student_status_date_index')) {
                $table->index(['student_id', 'status', 'transaction_at'], 'other_payments_student_status_date_index');
            }
        });

        $allocationIndexes = collect(Schema::getIndexes('bill_payment_allocations'))->pluck('name');
        Schema::table('bill_payment_allocations', function (Blueprint $table) use ($allocationIndexes): void {
            if (! $allocationIndexes->contains('bill_allocations_bill_created_index')) {
                $table->index(['bill_id', 'created_at'], 'bill_allocations_bill_created_index');
            }
        });
    }

    public function down(): void
    {
        $sppIndexes = collect(Schema::getIndexes('spp_payments'))->pluck('name');
        Schema::table('spp_payments', function (Blueprint $table) use ($sppIndexes): void {
            if ($sppIndexes->contains('spp_payments_student_status_date_index')) {
                $table->dropIndex('spp_payments_student_status_date_index');
            }
        });

        $otherIndexes = collect(Schema::getIndexes('other_payments'))->pluck('name');
        Schema::table('other_payments', function (Blueprint $table) use ($otherIndexes): void {
            if ($otherIndexes->contains('other_payments_student_status_date_index')) {
                $table->dropIndex('other_payments_student_status_date_index');
            }
        });

        $allocationIndexes = collect(Schema::getIndexes('bill_payment_allocations'))->pluck('name');
        Schema::table('bill_payment_allocations', function (Blueprint $table) use ($allocationIndexes): void {
            if ($allocationIndexes->contains('bill_allocations_bill_created_index')) {
                $table->dropIndex('bill_allocations_bill_created_index');
            }
        });
    }
};
