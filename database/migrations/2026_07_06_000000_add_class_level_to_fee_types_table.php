<?php

use App\Support\ClassLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('fee_types', 'class_level')) {
            Schema::table('fee_types', function (Blueprint $table) {
                $table->string('class_level', 50)->nullable()->after('school_class_id')->index();
            });
        }

        DB::table('fee_types')
            ->join('school_classes', 'school_classes.id', '=', 'fee_types.school_class_id')
            ->select('fee_types.id', 'school_classes.name', 'school_classes.level')
            ->orderBy('fee_types.id')
            ->get()
            ->each(function ($feeType): void {
                DB::table('fee_types')->where('id', $feeType->id)->update([
                    'class_level' => ClassLevel::key($feeType->level ?: $feeType->name),
                    'school_class_id' => null,
                ]);
            });

        $groups = DB::table('fee_types')
            ->whereNotNull('class_level')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($feeType) => implode('|', [
                $feeType->name,
                $feeType->payment_group,
                $feeType->education_unit_id,
                $feeType->academic_year_id,
                $feeType->class_level,
                $feeType->amount,
                $feeType->period,
                $feeType->creates_bill,
                $feeType->is_active,
            ]));

        foreach ($groups as $group) {
            if ($group->count() < 2) {
                continue;
            }

            $canonical = $group->first();
            $duplicates = $group->skip(1)->pluck('id');

            foreach (['other_payments', 'other_payment_items', 'fee_discounts'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->whereIn('fee_type_id', $duplicates)->update(['fee_type_id' => $canonical->id]);
                }
            }

            if (Schema::hasTable('bills')) {
                DB::table('bills')->whereIn('fee_type_id', $duplicates)->orderBy('id')->get()->each(function ($bill) use ($canonical): void {
                    $periodKey = $canonical->period === 'Sekali Bayar'
                        ? 'once'
                        : ($canonical->period === 'Tahunan' ? $bill->academic_year_id : $bill->year.'|'.$bill->month);
                    $generationKey = hash('sha256', "fee|{$bill->student_id}|{$canonical->id}|{$periodKey}");
                    $existingBill = DB::table('bills')
                        ->where('generation_key', $generationKey)
                        ->where('id', '!=', $bill->id)
                        ->first();

                    if ($existingBill && Schema::hasTable('bill_payment_allocations')) {
                        DB::table('bill_payment_allocations')
                            ->where('bill_id', $bill->id)
                            ->orderBy('id')
                            ->get()
                            ->each(function ($allocation) use ($existingBill): void {
                                $alreadyExists = DB::table('bill_payment_allocations')
                                    ->where('bill_id', $existingBill->id)
                                    ->where('payment_type', $allocation->payment_type)
                                    ->where('payment_id', $allocation->payment_id)
                                    ->exists();

                                if ($alreadyExists) {
                                    DB::table('bill_payment_allocations')->where('id', $allocation->id)->delete();

                                    return;
                                }

                                DB::table('bill_payment_allocations')
                                    ->where('id', $allocation->id)
                                    ->update(['bill_id' => $existingBill->id]);
                            });
                    }

                    if ($existingBill) {
                        DB::table('bills')->where('id', $bill->id)->delete();

                        return;
                    }

                    DB::table('bills')->where('id', $bill->id)->update([
                        'fee_type_id' => $canonical->id,
                        'generation_key' => $generationKey,
                    ]);
                });
            }

            DB::table('fee_types')->whereIn('id', $duplicates)->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fee_types', 'class_level')) {
            Schema::table('fee_types', function (Blueprint $table) {
                $table->dropIndex(['class_level']);
                $table->dropColumn('class_level');
            });
        }
    }
};
