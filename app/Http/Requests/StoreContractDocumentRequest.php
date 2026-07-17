<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user?->isAdmin()) {
            return false;
        }
        if ($user->isAdminHq()) {
            return true;
        }
        $contract = $this->route('contract');
        $contract->loadMissing('premise');

        return $user->isAdminNegeri() && $contract->premise?->negeri === $user->negeri;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'jenis' => ['required', 'in:surat_tawaran,gambar_premis,lain'],
        ];
    }
}
