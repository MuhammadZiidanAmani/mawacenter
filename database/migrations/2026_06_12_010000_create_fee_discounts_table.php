<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->enum('source_type', ['spp', 'fee_type']);
            $table->foreignId('fee_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->enum('discount_type', ['amount', 'percentage'])->default('amount');
            $table->unsignedBigInteger('discount_value');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['student_id', 'source_type', 'fee_type_id', 'is_active'], 'fee_discount_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_discounts');
    }
};
