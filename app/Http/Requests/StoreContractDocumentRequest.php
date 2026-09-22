<?php

namespace App\Http\Requests;

use App\Support\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

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
            'document' => [
                'required',
                File::types(['pdf', 'jpg', 'jpeg', 'png'])
                    ->max(UploadLimits::effectiveMaxKilobytes()),
            ],
            'jenis' => ['required', 'in:surat_tawaran,gambar_premis,lain'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $limit = UploadLimits::humanEffectiveLimit();

        return [
            'document.required' => 'Sila pilih fail untuk dimuat naik.',
            'document.max' => "Saiz fail tidak boleh melebihi {$limit}.",
            'document.mimes' => 'Hanya fail PDF, JPG atau PNG dibenarkan.',
        ];
    }
}
