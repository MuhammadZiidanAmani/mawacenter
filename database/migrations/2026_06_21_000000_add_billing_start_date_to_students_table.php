<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'billing_start_date')) {
            Schema::table('students', function (Blueprint $table) {
                $table->date('billing_start_date')->nullable()->after('entry_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'billing_start_date')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('billing_start_date');
            });
        }
    }
};
