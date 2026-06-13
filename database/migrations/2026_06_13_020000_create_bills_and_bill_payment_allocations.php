<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('name');
            $table->date('end_date')->nullable()->after('start_date');
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->restrictOnDelete();
            $table->enum('source_type', ['spp', 'fee_type', 'manual']);
            $table->foreignId('fee_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->string('generation_key', 64)->unique();
            $table->string('title');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('original_amount');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('remaining_amount');
            $table->string('status', 30)->default('Belum Dibayar');
            $table->string('unit_name')->nullable();
            $table->string('class_name')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'status', 'due_date']);
            $table->index(['source_type', 'fee_type_id', 'year', 'month']);
        });

        Schema::create('bill_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->string('payment_type', 30);
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('amount');
            $table->timestamps();
            $table->unique(['bill_id', 'payment_type', 'payment_id'], 'bill_payment_unique');
            $table->index(['payment_type', 'payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payment_allocations');
        Schema::dropIfExists('bills');
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
