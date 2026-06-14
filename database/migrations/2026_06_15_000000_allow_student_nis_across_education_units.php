<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('students'))->pluck('name');

        if ($indexes->contains('students_nis_unique')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropUnique('students_nis_unique');
            });
        }

        if (! $indexes->contains('students_nis_index')) {
            Schema::table('students', function (Blueprint $table) {
                $table->index('nis', 'students_nis_index');
            });
        }
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('students'))->pluck('name');

        if ($indexes->contains('students_nis_index')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex('students_nis_index');
            });
        }

        if (! $indexes->contains('students_nis_unique')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unique('nis');
            });
        }
    }
};
