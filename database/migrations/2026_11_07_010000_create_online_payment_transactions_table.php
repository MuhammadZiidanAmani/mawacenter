<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('merchant_order_id', 50)->unique();
            $table->json('bill_ids');
            $table->string('payment_method', 2);
            $table->string('payment_method_name')->nullable();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('service_fee')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('allocated_amount')->default(0);
            $table->string('status', 30)->default('Memproses');
            $table->string('result_code', 2)->nullable();
            $table->string('reconciliation_status')->nullable();
            $table->string('provider_reference')->nullable()->unique();
            $table->text('payment_url')->nullable();
            $table->string('va_number')->nullable();
            $table->text('qr_string')->nullable();
            $table->text('app_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('provider_response')->nullable();
            $table->json('callback_payload')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('callback_received_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['student_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_payment_transactions');
    }
};
