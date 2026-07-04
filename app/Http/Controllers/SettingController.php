<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AppSetting;
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
            'user' => auth()->user(),
            'transferSettings' => AppSetting::values([
                'transfer_bank_name' => '',
                'transfer_account_number' => '',
                'transfer_account_name' => '',
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge([
            'username' => $this->normalizeUsername((string) $request->input('username')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'max:100', 'confirmed'],
            'transfer_bank_name' => ['nullable', 'string', 'max:100'],
            'transfer_account_number' => ['nullable', 'string', 'max:50'],
            'transfer_account_name' => ['nullable', 'string', 'max:150'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        foreach (['transfer_bank_name', 'transfer_account_number', 'transfer_account_name'] as $key) {
            AppSetting::updateOrCreate(
                ['key' => $key],
                ['value' => trim((string) ($validated[$key] ?? ''))],
            );
        }

        return redirect()->route('settings.index')->with('success', 'Pengaturan akun berhasil disimpan.');
    }

    private function normalizeUsername(string $username): string
    {
        return str($username)
            ->lower()
            ->replaceMatches('/\s+/', '')
            ->value();
    }
}
