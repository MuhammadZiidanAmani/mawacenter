<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('until_month');
            $table->string('status', 24)->default('pending');
            $table->string('phase', 32)->default('spp');
            $table->unsignedBigInteger('cursor_id')->default(0);
            $table->json('filters')->nullable();
            $table->json('student_ids')->nullable();
            $table->json('fee_type_ids')->nullable();
            $table->json('phase_data')->nullable();
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('processed_items')->default(0);
            $table->unsignedInteger('created_items')->default(0);
            $table->unsignedInteger('existing_items')->default(0);
            $table->unsignedInteger('skipped_items')->default(0);
            $table->unsignedInteger('refreshed_items')->default(0);
            $table->unsignedInteger('failed_items')->default(0);
            $table->unsignedTinyInteger('percent')->default(0);
            $table->string('message')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('audited_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'bill_sync_runs_status_created_lookup');
            $table->index(['user_id', 'status'], 'bill_sync_runs_user_status_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_sync_runs');
    }
};
