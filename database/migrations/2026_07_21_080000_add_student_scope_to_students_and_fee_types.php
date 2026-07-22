<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'intake_status')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('intake_status', 30)->default('returning')->after('billing_start_date')->index();
            });

            DB::table('students')
                ->whereNull('intake_status')
                ->orWhere('intake_status', '')
                ->update(['intake_status' => 'returning']);
        }

        if (! Schema::hasColumn('fee_types', 'student_scope')) {
            Schema::table('fee_types', function (Blueprint $table) {
                $table->string('student_scope', 30)->default('all')->after('class_level')->index();
            });

            DB::table('fee_types')
                ->whereNull('student_scope')
                ->orWhere('student_scope', '')
                ->update(['student_scope' => 'all']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fee_types', 'student_scope')) {
            Schema::table('fee_types', function (Blueprint $table) {
                $table->dropIndex(['student_scope']);
                $table->dropColumn('student_scope');
            });
        }

        if (Schema::hasColumn('students', 'intake_status')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex(['intake_status']);
                $table->dropColumn('intake_status');
            });
        }
    }
};
