<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spp_payment_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spp_payment_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('old_paid_amount');
            $table->unsignedBigInteger('new_paid_amount');
            $table->unsignedBigInteger('refund_amount');
            $table->string('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spp_payment_corrections');
    }
};
