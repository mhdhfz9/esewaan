<?php

namespace App\Http\Requests;

use App\Models\RentalContract;
use App\Support\HqDraftAcknowledgements;
use Illuminate\Foundation\Http\FormRequest;

class AutosaveHqDraftAcknowledgementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var RentalContract|null $contract */
        $contract = $this->route('contract');

        return $user?->isAdminHq() === true
            && $contract?->isDrafDikembalikanHq() === true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'acknowledgements' => ['required', 'array'],
        ];

        foreach (HqDraftAcknowledgements::keys() as $key) {
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
