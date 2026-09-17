<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\MalaysianStates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $admin */
        $admin = $this->user();
        /** @var User $target */
        $target = $this->route('user');

        return $admin?->isAdmin() && $target->isVisibleToAdmin($admin);
    }

    protected function prepareForValidation(): void
    {
        /** @var User $admin */
        $admin = $this->user();

        if ($admin->isAdminNegeri() && filled($admin->negeri)) {
            $this->merge([
                'negeri' => $admin->negeri,
            ]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');
        /** @var User $admin */
        $admin = $this->user();

        $allowedRoles = $admin->isAdminNegeri()
            ? ['admin_negeri']
            : ['admin_hq', 'admin_negeri'];

        $selectedRole = $target->is($admin)
            ? $target->role
            : (string) $this->input('role', $target->role);

        $negeriRules = ['nullable', 'string', Rule::in(MalaysianStates::values())];

        if ($admin->isAdminNegeri()) {
            $negeriRules = ['required', 'string', Rule::in([$admin->negeri])];
        } elseif ($selectedRole === 'admin_negeri') {
            $negeriRules = ['required', 'string', Rule::in(MalaysianStates::values())];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target->id)],
            'negeri' => $negeriRules,
            'role' => [
                Rule::requiredIf(fn () => ! $target->is($this->user())),
                Rule::in($allowedRoles),
            ],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama diperlukan.',
            'email.required' => 'Emel diperlukan.',
            'email.unique' => 'Emel ini telah digunakan.',
            'negeri.in' => 'Negeri tidak sah.',
            'role.required' => 'Peranan diperlukan.',
            'role.in' => 'Peranan tidak sah.',
            'password.confirmed' => 'Kata laluan tidak sama.',
            'password.min' => 'Kata laluan sekurang-kurangnya 8 aksara.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var User $target */
            $target = $this->route('user');
            /** @var User $admin */
            $admin = $this->user();

            if ($this->filled('password') && $admin->is($target)) {
                $validator->errors()->add('password', 'Gunakan halaman Profil Saya untuk menukar kata laluan anda.');
            }

            if ($target->is($admin) && $this->filled('role') && $this->input('role') !== $target->role) {
                $validator->errors()->add('role', 'Anda tidak boleh menukar peranan sendiri.');
            }

            if ($admin->isAdminNegeri() && $this->input('negeri') !== $admin->negeri) {
                $validator->errors()->add('negeri', 'Anda hanya boleh mengurus pengguna negeri '.$admin->negeri.'.');
            }

            if ($admin->isAdminHq() && ! $target->is($admin) && $target->isAdminHq() && $this->input('role') !== 'admin_hq') {
                $remainingAdmins = User::query()
                    ->where('role', 'admin_hq')
                    ->whereKeyNot($target->getKey())
                    ->exists();

                if (! $remainingAdmins) {
                    $validator->errors()->add('role', 'Sekurang-kurangnya satu Ibu Pejabat mesti kekal dalam sistem.');
                }
            }
        });
    }

    public function resolvedRole(): string
    {
        /** @var User $target */
        $target = $this->route('user');

        if ($target->is($this->user())) {
            return $target->role;
        }

        return $this->validated('role');
    }

    public function resolvedNegeri(): ?string
    {
        /** @var User $admin */
        $admin = $this->user();

        if ($admin->isAdminNegeri()) {
            return $admin->negeri;
        }

        if ($this->resolvedRole() === 'admin_hq') {
            return null;
        }

        $negeri = $this->validated('negeri');

        return filled($negeri) ? $negeri : null;
    }
}
