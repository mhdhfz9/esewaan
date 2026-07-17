<?php

namespace App\Http\Requests;

use App\Support\MalaysianStates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'negeri' => ['nullable', 'string', Rule::in(MalaysianStates::values())],
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
            'password.confirmed' => 'Kata laluan tidak sama.',
            'password.min' => 'Kata laluan sekurang-kurangnya 8 aksara.',
        ];
    }
}
