<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
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
