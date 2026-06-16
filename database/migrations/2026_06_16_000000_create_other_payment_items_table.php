<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('other_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_type_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedBigInteger('original_amount');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('remaining_amount')->default(0);
            $table->string('payment_status', 30)->default('Belum Lunas');
            $table->timestamps();
            $table->index(['student_id', 'fee_type_id', 'year', 'month'], 'other_payment_period_index');
        });

        DB::table('other_payments')
            ->join('fee_types', 'fee_types.id', '=', 'other_payments.fee_type_id')
            ->where('fee_types.payment_group', 'laundry')
            ->where('fee_types.period', 'Bulanan')
            ->select('other_payments.*')
            ->orderBy('other_payments.id')
            ->each(function ($payment) {
                $date = CarbonImmutable::parse($payment->transaction_at);
                DB::table('other_payment_items')->insert([
                    'other_payment_id' => $payment->id,
                    'student_id' => $payment->student_id,
                    'fee_type_id' => $payment->fee_type_id,
                    'year' => $date->year,
                    'month' => $date->month,
                    'original_amount' => $payment->original_amount,
                    'discount_amount' => $payment->discount_amount,
                    'total_amount' => $payment->total_amount,
                    'paid_amount' => $payment->status === 'Diterima' ? $payment->paid_amount : 0,
                    'remaining_amount' => $payment->remaining_amount,
                    'payment_status' => $payment->payment_status,
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_payment_items');
    }
};
