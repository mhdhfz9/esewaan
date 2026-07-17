<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Services\ActivityLogger;
use App\Support\MalaysianStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = auth()->user();

        return view('profile.show', [
            'user' => $user,
        ]);
    }

    public function edit(): View
    {
        $user = auth()->user();

        return view('profile.edit', [
            'user' => $user,
            'negeriList' => MalaysianStates::all(),
            'canEditNegeri' => false,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $passwordChanged = filled($data['password'] ?? null);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            ...($passwordChanged ? ['password' => $data['password']] : []),
        ]);

        ActivityLogger::log(
            $user,
            'profile_updated',
            'Profil dikemaskini oleh pengguna.',
            performedBy: $user,
        );

        if ($passwordChanged) {
            ActivityLogger::log(
                $user,
                'password_reset',
                'Kata laluan dikemaskini oleh pengguna.',
                performedBy: $user,
            );
        }

        return redirect()
            ->route('profile.show')
            ->with('success', 'Profil anda berjaya dikemaskini.');
    }
}
