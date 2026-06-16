<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('spp_settings')) {
            DB::table('spp_settings')->orderBy('id')->each(function ($setting) {
                $code = 'SPP-'.$setting->education_unit_id;
                $suffix = 2;
                while (DB::table('fee_types')->where('code', $code)->exists()) {
                    $code = 'SPP-'.$setting->education_unit_id.'-'.$suffix++;
                }

                DB::table('fee_types')->insert([
                    'education_unit_id' => $setting->education_unit_id,
                    'school_class_id' => null,
                    'academic_year_id' => null,
                    'payment_group' => 'spp',
                    'code' => $code,
                    'name' => 'SPP',
                    'amount' => $setting->amount,
                    'period' => 'Bulanan',
                    'is_active' => $setting->is_active,
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ]);
            });

            Schema::drop('spp_settings');
        }
    }

    public function down(): void
    {
        Schema::create('spp_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('education_unit_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('fee_types')
            ->where('payment_group', 'spp')
            ->whereNull('school_class_id')
            ->orderBy('id')
            ->each(function ($feeType) {
                DB::table('spp_settings')->updateOrInsert(
                    ['education_unit_id' => $feeType->education_unit_id],
                    [
                        'amount' => $feeType->amount,
                        'is_active' => $feeType->is_active,
                        'created_at' => $feeType->created_at,
                        'updated_at' => $feeType->updated_at,
                    ],
                );
            });

        DB::table('fee_types')->where('payment_group', 'spp')->delete();
    }
};
