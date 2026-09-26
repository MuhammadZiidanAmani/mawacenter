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
                $table->string('transfer_proof_file_id')->nullable()->after('transfer_proof_path');
                $table->json('transfer_proof_metadata')->nullable()->after('transfer_proof_file_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['spp_payments', 'other_payments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['transfer_proof_file_id', 'transfer_proof_metadata']);
            });
        }
    }
};
