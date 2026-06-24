<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('students'))->pluck('name');
        if ($indexes->contains('students_nisn_unique')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropUnique('students_nisn_unique');
            });
        }

        Schema::table('students', function (Blueprint $table) {
            $table->index('nisn', 'students_nisn_index');
            $table->foreignId('identity_student_id')
                ->nullable()
                ->after('id')
                ->constrained('students')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('students')->whereNotNull('identity_student_id')->update(['nisn' => null]);

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('identity_student_id');
            $table->dropIndex('students_nisn_index');
            $table->unique('nisn');
        });
    }
};
