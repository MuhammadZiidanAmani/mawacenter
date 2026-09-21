<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Services\AuditLogService;
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
        $before = [
            'user' => $this->userAuditSnapshot($user),
            'transfer_settings' => $this->transferSettingsSnapshot(),
        ];
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
            $user->must_reset_password = false;
        }

        $user->save();

        foreach (['transfer_bank_name', 'transfer_account_number', 'transfer_account_name'] as $key) {
            AppSetting::updateOrCreate(
                ['key' => $key],
                ['value' => trim((string) ($validated[$key] ?? ''))],
            );
        }
        app(AuditLogService::class)->recordOperation(
            'settings.update',
            ['password_changed' => filled($validated['password'] ?? null)],
            beforeValues: $before,
            afterValues: [
                'user' => $this->userAuditSnapshot($user->refresh()),
                'transfer_settings' => $this->transferSettingsSnapshot(),
            ],
            request: $request,
            subjectType: $user::class,
            subjectId: $user->id,
        );

        return redirect()->route('settings.index')->with('success', 'Pengaturan akun berhasil disimpan.');
    }

    private function userAuditSnapshot($user): array
    {
        return collect($user->getAttributes())
            ->except(['password', 'remember_token'])
            ->all();
    }

    private function transferSettingsSnapshot(): array
    {
        return array_merge([
            'transfer_bank_name' => '',
            'transfer_account_number' => '',
            'transfer_account_name' => '',
        ], AppSetting::query()
            ->whereIn('key', ['transfer_bank_name', 'transfer_account_number', 'transfer_account_name'])
            ->pluck('value', 'key')
            ->all());
    }

    private function normalizeUsername(string $username): string
    {
        return str($username)
            ->lower()
            ->replaceMatches('/\s+/', '')
            ->value();
    }
}
