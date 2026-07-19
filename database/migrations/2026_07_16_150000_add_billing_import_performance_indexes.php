<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('bills', 'bills_scope_period_lookup', ['source_type', 'year', 'month', 'remaining_amount', 'status']);
        $this->addIndex('bills', 'bills_student_scope_lookup', ['student_id', 'status', 'remaining_amount', 'source_type', 'year', 'month']);
        $this->addIndex('fee_types', 'fee_types_spp_lookup', ['payment_group', 'is_active', 'education_unit_id', 'academic_year_id', 'school_class_id']);
        $this->addIndex('other_payments', 'other_payments_student_fee_date_lookup', ['student_id', 'fee_type_id', 'transaction_at']);
        $this->addIndex('guardian_transfer_requests', 'guardian_transfers_user_created_lookup', ['user_id', 'created_at']);
    }

    public function down(): void
    {
        $this->dropIndex('guardian_transfer_requests', 'guardian_transfers_user_created_lookup');
        $this->dropIndex('other_payments', 'other_payments_student_fee_date_lookup');
        $this->dropIndex('fee_types', 'fee_types_spp_lookup');
        $this->dropIndex('bills', 'bills_student_scope_lookup');
        $this->dropIndex('bills', 'bills_scope_period_lookup');
    }

    private function addIndex(string $tableName, string $indexName, array $columns): void
    {
        $indexes = collect(Schema::getIndexes($tableName))->pluck('name');

        if (! $indexes->contains($indexName)) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName, $columns) {
                $table->index($columns, $indexName);
            });
        }
    }

    private function dropIndex(string $tableName, string $indexName): void
    {
        $indexes = collect(Schema::getIndexes($tableName))->pluck('name');

        if ($indexes->contains($indexName)) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};
