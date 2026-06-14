<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('school_classes'))->pluck('name');

        if ($indexes->contains('school_classes_name_level_unique')) {
            Schema::table('school_classes', function (Blueprint $table) {
                $table->dropUnique('school_classes_name_level_unique');
            });
        }

        if (! $indexes->contains('school_classes_unit_name_unique')) {
            Schema::table('school_classes', function (Blueprint $table) {
                $table->unique(['education_unit_id', 'name'], 'school_classes_unit_name_unique');
            });
        }
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('school_classes'))->pluck('name');

        if ($indexes->contains('school_classes_unit_name_unique')) {
            Schema::table('school_classes', function (Blueprint $table) {
                $table->dropUnique('school_classes_unit_name_unique');
            });
        }

        if (! $indexes->contains('school_classes_name_level_unique')) {
            Schema::table('school_classes', function (Blueprint $table) {
                $table->unique(['name', 'level'], 'school_classes_name_level_unique');
            });
        }
    }
};
