<?php

namespace App\Http\Requests;

use App\Models\ContractDocument;
use App\Models\RentalContract;
use Illuminate\Foundation\Http\FormRequest;

class DownloadContractDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var RentalContract|null $contract */
        $contract = $this->route('contract');
        /** @var ContractDocument|null $document */
        $document = $this->route('document');

        if ($user?->isAdmin() !== true || $document === null || $contract === null) {
            return false;
        }

        if ($document->contract_id !== $contract->id) {
            return false;
        }

        if ($user->isAdminHq()) {
            return true;
        }

        $contract->loadMissing('premise');

        return $user->isAdminNegeri()
            && filled($user->negeri)
            && $contract->premise?->negeri === $user->negeri;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
