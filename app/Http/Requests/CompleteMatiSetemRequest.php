<?php

namespace App\Http\Requests;

use App\Models\RentalContract;
use App\Support\NegeriMatiSetemAcknowledgements;
use Illuminate\Foundation\Http\FormRequest;

class CompleteMatiSetemRequest extends FormRequest
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
        return [
            NegeriMatiSetemAcknowledgements::MATI_SETEM_LHDN => ['accepted'],
            NegeriMatiSetemAcknowledgements::EDARAN_PEMILIK => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            NegeriMatiSetemAcknowledgements::MATI_SETEM_LHDN.'.accepted' => 'Sila sahkan 3 salinan dokumen telah dimatikan setem di LHDN sebelum menekan Selesai.',
            NegeriMatiSetemAcknowledgements::EDARAN_PEMILIK.'.accepted' => 'Sila sahkan 1 salinan dokumen telah diedar kepada Pemilik Premis sebelum menekan Selesai.',
        ];
    }
}
