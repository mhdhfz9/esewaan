<?php

namespace App\Http\Requests;

use App\Models\RentalContract;
use App\Support\NegeriMatiSetemAcknowledgements;
use Illuminate\Foundation\Http\FormRequest;

class AutosaveNegeriMatiSetemAcknowledgementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var RentalContract|null $contract */
        $contract = $this->route('contract');

        if ($user?->isAdminNegeri() !== true || ! filled($user->negeri)) {
            return false;
        }

        $contract?->loadMissing('premise');

        return $contract?->isMatiSetem() === true
            && $contract->premise?->negeri === $user->negeri;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'acknowledgements' => ['required', 'array'],
        ];

        foreach (NegeriMatiSetemAcknowledgements::keys() as $key) {
            $rules["acknowledgements.{$key}"] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'acknowledgements.required' => 'Tiada pengesahan untuk disimpan.',
        ];
    }
}
