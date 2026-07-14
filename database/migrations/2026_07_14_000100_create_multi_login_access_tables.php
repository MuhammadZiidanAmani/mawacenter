<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_unit_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('education_unit_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'education_unit_id'], 'education_unit_user_unique');
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 50)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'student_id'], 'guardian_student_unique');
        });

        Schema::create('guardian_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->json('bill_ids');
            $table->unsignedBigInteger('amount');
            $table->string('proof_path');
            $table->string('status', 30)->default('Pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        foreach (Role::DEFAULTS as $key => $name) {
            DB::table('roles')->updateOrInsert(
                ['key' => $key],
                [
                    'name' => $name,
                    'description' => 'Role bawaan sistem',
                    'permissions' => json_encode(Role::defaultPermissionsFor($key)),
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_transfer_requests');
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('education_unit_user');
    }
};
