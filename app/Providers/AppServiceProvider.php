<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\Bill;
use App\Models\BillManualPayment;
use App\Models\BillPaymentAllocation;
use App\Models\EducationUnit;
use App\Models\FeeDiscount;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\OtherPaymentItem;
use App\Models\SchoolClass;
use App\Models\SppPayment;
use App\Models\SppPaymentCorrection;
use App\Models\SppPaymentItem;
use App\Models\Student;
use App\Support\PerformanceCache;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $username = Str::lower(trim((string) $request->input('username')));

            return Limit::perMinute(5)->by($username.'|'.$request->ip());
        });

        foreach ($this->cacheSensitiveModels() as $model) {
            $model::saved(fn () => PerformanceCache::bust());
            $model::deleted(fn () => PerformanceCache::bust());
        }
    }

    private function cacheSensitiveModels(): array
    {
        return [
            AcademicYear::class,
            AppSetting::class,
            Bill::class,
            BillManualPayment::class,
            BillPaymentAllocation::class,
            EducationUnit::class,
            FeeDiscount::class,
            FeeType::class,
            OtherPayment::class,
            OtherPaymentItem::class,
            SchoolClass::class,
            SppPayment::class,
            SppPaymentCorrection::class,
            SppPaymentItem::class,
            Student::class,
        ];
    }
}
