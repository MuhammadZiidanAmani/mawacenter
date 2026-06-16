<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $studentIndexes = collect(Schema::getIndexes('students'))->pluck('name');
        Schema::table('students', function (Blueprint $table) use ($studentIndexes) {
            if (! $studentIndexes->contains('students_list_filter_index')) {
                $table->index(['academic_year_id', 'is_active', 'school_class_id'], 'students_list_filter_index');
            }

            if (! $studentIndexes->contains('students_name_index')) {
                $table->index('name', 'students_name_index');
            }
        });

        $feeTypeIndexes = collect(Schema::getIndexes('fee_types'))->pluck('name');
        Schema::table('fee_types', function (Blueprint $table) use ($feeTypeIndexes) {
            if (! $feeTypeIndexes->contains('fee_types_active_group_index')) {
                $table->index(['is_active', 'payment_group'], 'fee_types_active_group_index');
            }
        });
    }

    public function down(): void
    {
        $studentIndexes = collect(Schema::getIndexes('students'))->pluck('name');
        Schema::table('students', function (Blueprint $table) use ($studentIndexes) {
            if ($studentIndexes->contains('students_list_filter_index')) {
                $table->dropIndex('students_list_filter_index');
            }

            if ($studentIndexes->contains('students_name_index')) {
                $table->dropIndex('students_name_index');
            }
        });

        $feeTypeIndexes = collect(Schema::getIndexes('fee_types'))->pluck('name');
        Schema::table('fee_types', function (Blueprint $table) use ($feeTypeIndexes) {
            if ($feeTypeIndexes->contains('fee_types_active_group_index')) {
                $table->dropIndex('fee_types_active_group_index');
            }
        });
    }
};
