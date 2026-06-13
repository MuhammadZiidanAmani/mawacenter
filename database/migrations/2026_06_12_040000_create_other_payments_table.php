<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_type_id')->constrained()->restrictOnDelete();
            $table->dateTime('transaction_at');
            $table->string('payment_method', 30);
            $table->string('status', 30);
            $table->unsignedBigInteger('original_amount');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('paid_amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_payments');
    }
};
