<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spp_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('spp_payments', 'operator_user_id')) {
                $table->foreignId('operator_user_id')->nullable()->after('operator_name')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('other_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('other_payments', 'operator_user_id')) {
                $table->foreignId('operator_user_id')->nullable()->after('operator_name')->constrained('users')->nullOnDelete();
            }
        });

        $this->backfillOperatorUsers();
    }

    public function down(): void
    {
        Schema::table('spp_payments', function (Blueprint $table) {
            if (Schema::hasColumn('spp_payments', 'operator_user_id')) {
                $table->dropConstrainedForeignId('operator_user_id');
            }
        });

        Schema::table('other_payments', function (Blueprint $table) {
            if (Schema::hasColumn('other_payments', 'operator_user_id')) {
                $table->dropConstrainedForeignId('operator_user_id');
            }
        });
    }

    private function backfillOperatorUsers(): void
    {
        foreach (['spp_payments', 'other_payments'] as $table) {
            $operatorNames = DB::table($table)
                ->whereNull('operator_user_id')
                ->whereNotNull('operator_name')
                ->where('operator_name', '!=', '')
                ->distinct()
                ->pluck('operator_name');

            foreach ($operatorNames as $operatorName) {
                $userId = DB::table('users')->where('name', $operatorName)->value('id');
                if ($userId) {
                    DB::table($table)
                        ->whereNull('operator_user_id')
                        ->where('operator_name', $operatorName)
                        ->update(['operator_user_id' => $userId]);
                }
            }

            $remainingCount = DB::table($table)
                ->whereNull('operator_user_id')
                ->whereNotNull('operator_name')
                ->where('operator_name', '!=', '')
                ->count();

            if ($remainingCount < 1) {
                continue;
            }

            $cashierUserIds = DB::table('users')
                ->whereIn('role', ['admin', 'kasir'])
                ->pluck('id');

            if ($cashierUserIds->count() === 1) {
                DB::table($table)
                    ->whereNull('operator_user_id')
                    ->whereNotNull('operator_name')
                    ->where('operator_name', '!=', '')
                    ->update(['operator_user_id' => $cashierUserIds->first()]);
            }
        }
    }
};
