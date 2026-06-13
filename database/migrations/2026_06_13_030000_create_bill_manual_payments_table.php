<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_manual_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->restrictOnDelete();
            $table->dateTime('transaction_at');
            $table->string('payment_method', 30);
            $table->unsignedBigInteger('paid_amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_manual_payments');
    }
};
