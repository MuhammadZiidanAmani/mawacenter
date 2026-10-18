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
use App\Models\GuardianTransferRequest;
use App\Models\OtherPayment;
use App\Models\OtherPaymentItem;
use App\Models\SchoolClass;
use App\Models\SppPayment;
use App\Models\SppPaymentCorrection;
use App\Models\SppPaymentItem;
use App\Models\Student;
use App\Models\Role;
use App\Models\User;
use App\Support\PerformanceCache;
use App\Support\PerformanceCacheInvalidator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(PerformanceCacheInvalidator::class, fn () => new PerformanceCacheInvalidator);
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

        foreach ($this->cacheSensitiveModels() as $model => $namespaces) {
            $model::saved(fn () => PerformanceCache::invalidate($namespaces));
            $model::deleted(fn () => PerformanceCache::invalidate($namespaces));
        }

        $this->app->terminating(fn () => app(PerformanceCacheInvalidator::class)->flush());
        Queue::after(fn () => app(PerformanceCacheInvalidator::class)->flush());
        Queue::exceptionOccurred(fn () => app(PerformanceCacheInvalidator::class)->flush());
        Queue::looping(fn () => app(PerformanceCacheInvalidator::class)->flush());
    }

    private function cacheSensitiveModels(): array
    {
        $billing = ['dashboard', 'bills', 'reports'];
        $structure = ['dashboard', 'bills', 'master-options', 'reports'];

        return [
            AcademicYear::class => $structure,
            AppSetting::class => ['dashboard', 'settings'],
            Bill::class => $billing,
            BillManualPayment::class => $billing,
            BillPaymentAllocation::class => $billing,
            EducationUnit::class => $structure,
            FeeDiscount::class => $structure,
            FeeType::class => $structure,
            GuardianTransferRequest::class => $billing,
            OtherPayment::class => $billing,
            OtherPaymentItem::class => $billing,
            Role::class => ['master-options'],
            SchoolClass::class => array_merge($structure, ['identity']),
            SppPayment::class => $billing,
            SppPaymentCorrection::class => $billing,
            SppPaymentItem::class => $billing,
            Student::class => array_merge($structure, ['identity']),
            User::class => ['master-options'],
        ];
    }
}
