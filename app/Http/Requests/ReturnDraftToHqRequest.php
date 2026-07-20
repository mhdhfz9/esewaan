<?php

namespace App\Http\Requests;

use App\Models\RentalContract;
use Illuminate\Foundation\Http\FormRequest;

class ReturnDraftToHqRequest extends FormRequest
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

        return $contract?->isDrafPerjanjianLulus() === true
            && $contract->premise?->negeri === $user->negeri;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'draf_akhir_acknowledged' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'draf_akhir_acknowledged.accepted' => 'Sila tanda kotak pengesahan sebelum menghantar semula kepada Admin.',
        ];
    }
}
