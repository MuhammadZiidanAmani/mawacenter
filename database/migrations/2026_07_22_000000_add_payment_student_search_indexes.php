<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('students'))->pluck('name');

        Schema::table('students', function (Blueprint $table) use ($indexes) {
            if (! $indexes->contains('students_payment_active_nis_index')) {
                $table->index(['is_active', 'nis'], 'students_payment_active_nis_index');
            }

            if (! $indexes->contains('students_payment_active_nisn_index')) {
                $table->index(['is_active', 'nisn'], 'students_payment_active_nisn_index');
            }

            if (! $indexes->contains('students_payment_active_name_index')) {
                $table->index(['is_active', 'name'], 'students_payment_active_name_index');
            }

            if (! $indexes->contains('students_payment_active_identity_index')) {
                $table->index(['is_active', 'identity_student_id'], 'students_payment_active_identity_index');
            }
        });
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('students'))->pluck('name');

        Schema::table('students', function (Blueprint $table) use ($indexes) {
            if ($indexes->contains('students_payment_active_nis_index')) {
                $table->dropIndex('students_payment_active_nis_index');
            }

            if ($indexes->contains('students_payment_active_nisn_index')) {
                $table->dropIndex('students_payment_active_nisn_index');
            }

            if ($indexes->contains('students_payment_active_name_index')) {
                $table->dropIndex('students_payment_active_name_index');
            }

            if ($indexes->contains('students_payment_active_identity_index')) {
                $table->dropIndex('students_payment_active_identity_index');
            }
        });
    }
};
