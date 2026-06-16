<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->index(['status', 'remaining_amount', 'source_type', 'year', 'month'], 'bills_outstanding_lookup');
            $table->index(['student_id', 'source_type', 'year', 'month'], 'bills_student_period_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex('bills_outstanding_lookup');
            $table->dropIndex('bills_student_period_lookup');
        });
    }
};
