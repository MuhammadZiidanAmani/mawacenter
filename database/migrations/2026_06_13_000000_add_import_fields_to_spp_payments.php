<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spp_payments', function (Blueprint $table) {
            $table->string('operator_name')->nullable()->after('status');
            $table->string('import_source')->nullable()->after('operator_name');
            $table->string('import_key', 64)->nullable()->unique()->after('import_source');
        });

        Schema::table('spp_payment_items', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::table('spp_payment_items', function (Blueprint $table) {
            $table->unique(['student_id', 'year', 'month']);
        });

        Schema::table('spp_payments', function (Blueprint $table) {
            $table->dropUnique(['import_key']);
            $table->dropColumn(['operator_name', 'import_source', 'import_key']);
        });
    }
};
