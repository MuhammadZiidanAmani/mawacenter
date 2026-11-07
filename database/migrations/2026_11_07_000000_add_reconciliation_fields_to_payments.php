<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['spp_payments', 'other_payments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('reconciliation_status', 30)->nullable()->index();
                $table->timestamp('reconciliation_flagged_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['spp_payments', 'other_payments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex($tableName.'_reconciliation_status_index');
                $table->dropColumn(['reconciliation_status', 'reconciliation_flagged_at']);
            });
        }
    }
};
