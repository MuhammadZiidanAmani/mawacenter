<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\EducationUnit;
use App\Models\FeeType;
use App\Models\SchoolClass;
use App\Models\SppSetting;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'settings' => AppSetting::values($this->defaults()),
            'setup' => [
                'units' => EducationUnit::where('is_active', true)->count(),
                'classes' => SchoolClass::where('is_active', true)->count(),
                'students' => Student::where('is_active', true)->count(),
                'fee_types' => FeeType::where('is_active', true)->count(),
                'spp_settings' => SppSetting::where('is_active', true)->count(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:150'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'school_phone' => ['nullable', 'string', 'max:30'],
            'school_email' => ['nullable', 'email', 'max:150'],
            'finance_officer' => ['nullable', 'string', 'max:150'],
            'receipt_footer' => ['nullable', 'string', 'max:300'],
            'default_payment_method' => ['required', Rule::in(['Cash', 'Transfer'])],
        ]);

        foreach ($validated as $key => $value) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()->route('settings.index')->with('success', 'Pengaturan aplikasi berhasil disimpan.');
    }

    private function defaults(): array
    {
        return [
            'school_name' => "MA'WA CENTER",
            'school_address' => '',
            'school_phone' => '',
            'school_email' => '',
            'finance_officer' => '',
            'receipt_footer' => 'Terima kasih atas pembayaran Anda.',
            'default_payment_method' => 'Cash',
        ];
    }
}
