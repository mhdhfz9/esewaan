<?php

namespace App\Http\Requests;

use App\Models\RentalContract;
use Illuminate\Foundation\Http\FormRequest;

class SignAgreementRequest extends FormRequest
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
        return [
            'terima_dokumen_acknowledged' => ['accepted'],
            'perjanjian_ditandatangani_acknowledged' => ['accepted'],
            'salinan_promis_acknowledged' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $message = 'Sila tanda kesemua kotak pengesahan sebelum menekan Selesai.';

        return [
            'terima_dokumen_acknowledged.accepted' => $message,
            'perjanjian_ditandatangani_acknowledged.accepted' => $message,
            'salinan_promis_acknowledged.accepted' => $message,
        ];
    }
}
