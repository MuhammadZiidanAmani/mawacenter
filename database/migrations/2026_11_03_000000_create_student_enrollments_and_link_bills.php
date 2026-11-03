<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('education_unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_class_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('source_enrollment_id')->nullable();
            $table->string('intake_status', 30)->default('returning');
            $table->string('status', 30)->default('planned');
            $table->date('entry_date')->nullable();
            $table->date('exit_date')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['student_id', 'education_unit_id', 'academic_year_id'],
                'student_enrollments_student_unit_year_unique'
            );
            $table->index(['education_unit_id', 'academic_year_id', 'status'], 'student_enrollments_scope_index');
            $table->index(['student_id', 'status'], 'student_enrollments_student_status_index');
            $table->foreign('source_enrollment_id', 'student_enrollments_source_foreign')
                ->references('id')
                ->on('student_enrollments')
                ->nullOnDelete();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->foreignId('student_enrollment_id')
                ->nullable()
                ->after('student_id')
                ->constrained('student_enrollments')
                ->restrictOnDelete();
            $table->string('payment_group_snapshot', 30)->nullable()->after('fee_type_id');
            $table->string('fee_name_snapshot')->nullable()->after('payment_group_snapshot');
            $table->string('academic_year_name_snapshot')->nullable()->after('fee_name_snapshot');
            $table->string('class_level_snapshot', 50)->nullable()->after('class_name');
            $table->string('intake_status_snapshot', 30)->nullable()->after('class_level_snapshot');
            $table->index(['student_enrollment_id', 'status'], 'bills_enrollment_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex('bills_enrollment_status_index');
            $table->dropConstrainedForeignId('student_enrollment_id');
            $table->dropColumn([
                'payment_group_snapshot',
                'fee_name_snapshot',
                'academic_year_name_snapshot',
                'class_level_snapshot',
                'intake_status_snapshot',
            ]);
        });

        Schema::dropIfExists('student_enrollments');
    }
};
