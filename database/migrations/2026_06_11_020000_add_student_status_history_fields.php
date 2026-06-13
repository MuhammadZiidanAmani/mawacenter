<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('entry_date')->nullable()->after('academic_year_id');
            $table->date('exit_date')->nullable()->after('entry_date');
            $table->string('inactive_reason')->nullable()->after('exit_date');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['entry_date', 'exit_date', 'inactive_reason']);
        });
    }
};
