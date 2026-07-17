<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\MalaysianStates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $admin = $this->user();

        if ($admin?->isAdminNegeri() && filled($admin->negeri)) {
            $this->merge([
                'negeri' => $admin->negeri,
            ]);

            return;
        }

        if ($this->input('role') === 'admin_hq') {
            $this->merge([
                'negeri' => null,
            ]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $admin */
        $admin = $this->user();

        $allowedRoles = $admin->isAdminNegeri()
            ? ['admin_negeri']
            : ['admin_hq', 'admin_negeri'];

        $negeriRules = ['nullable', 'string', Rule::in(MalaysianStates::values())];

        if ($admin->isAdminNegeri()) {
            $negeriRules = ['required', 'string', Rule::in([$admin->negeri])];
        } elseif ($this->input('role') === 'admin_negeri') {
            $negeriRules = ['required', 'string', Rule::in(MalaysianStates::values())];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'role' => ['required', Rule::in($allowedRoles)],
            'negeri' => $negeriRules,
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
            'email.unique' => 'Emel ini telah didaftarkan.',
            'password.required' => 'Kata laluan diperlukan.',
            'password.min' => 'Kata laluan sekurang-kurangnya 8 aksara.',
            'password.confirmed' => 'Kata laluan tidak sama.',
            'negeri.required' => 'Negeri diperlukan untuk peranan Negeri.',
            'negeri.in' => 'Negeri tidak sah untuk pentadbir negeri anda.',
            'role.in' => 'Peranan tidak sah.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var User $admin */
            $admin = $this->user();

            if ($admin->isAdminNegeri() && $this->input('negeri') !== $admin->negeri) {
                $validator->errors()->add('negeri', 'Anda hanya boleh mendaftar pengguna untuk negeri '.$admin->negeri.'.');
            }
        });
    }

    public function resolvedNegeri(): ?string
    {
        /** @var User $admin */
        $admin = $this->user();

        if ($admin->isAdminNegeri()) {
            return $admin->negeri;
        }

        if ($this->validated('role') === 'admin_hq') {
            return null;
        }

        $negeri = $this->validated('negeri');

        return filled($negeri) ? $negeri : null;
    }
}
