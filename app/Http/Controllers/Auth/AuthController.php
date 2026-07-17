<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\MalaysianStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! $request->user()?->isActive()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => ['Akaun anda telah dinyahaktifkan. Sila hubungi pentadbir.'],
            ]);
        }

        $request->session()->regenerate();

        ActivityLogger::log(
            $request->user(),
            'login',
            'Pengguna log masuk ke sistem.',
        );

        return redirect($request->user()->homeRoute());
    }

    public function showRegisterForm(): View
    {
        $admin = auth()->user();

        return view('auth.register', [
            'negeriList' => MalaysianStates::all(),
            'lockNegeri' => $admin?->isAdminNegeri() ?? false,
            'lockedNegeri' => $admin?->negeri,
        ]);
    }

    public function storeUser(RegisterRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => $request->validated('role'),
            'negeri' => $request->resolvedNegeri(),
        ]);

        ActivityLogger::log(
            $user,
            'user_created',
            'Akaun didaftarkan oleh pentadbir.',
            ['role' => $user->role],
            $request->user(),
        );

        return redirect()->route('users.index')->with('success', 'Pengguna berjaya didaftarkan.');
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($request->user()) {
            ActivityLogger::log(
                $request->user(),
                'logout',
                'Pengguna log keluar dari sistem.',
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
