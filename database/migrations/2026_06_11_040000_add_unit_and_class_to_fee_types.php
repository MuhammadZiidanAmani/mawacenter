<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_types', function (Blueprint $table) {
            $table->foreignId('education_unit_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->foreignId('school_class_id')->nullable()->after('education_unit_id')->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fee_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_class_id');
            $table->dropConstrainedForeignId('education_unit_id');
        });
    }
};
