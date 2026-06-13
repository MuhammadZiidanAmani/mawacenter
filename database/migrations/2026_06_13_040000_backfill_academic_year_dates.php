<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('academic_years')->whereNull('start_date')->orWhereNull('end_date')->orderBy('id')->get()->each(function ($year) {
            if (! preg_match('/^(\d{4})\/(\d{4})$/', $year->name, $matches)) {
                return;
            }

            DB::table('academic_years')->where('id', $year->id)->update([
                'start_date' => $year->start_date ?? $matches[1].'-07-01',
                'end_date' => $year->end_date ?? $matches[2].'-06-30',
            ]);
        });
    }

    public function down(): void
    {
        //
    }
};
