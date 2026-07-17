<?php

namespace App\Http\Requests;

use App\Models\RentalContract;
use Illuminate\Foundation\Http\FormRequest;

class DeleteRentalApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var RentalContract|null $contract */
        $contract = $this->route('contract');

        if (! $user?->isAdminNegeri() || ! filled($user->negeri) || ! $contract) {
            return false;
        }

        $contract->loadMissing('premise');

        return $contract->premise?->negeri === $user->negeri
            && $contract->canBeDeletedByAdminNegeri();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'delete_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'delete_reason.required' => 'Sila nyatakan alasan padam permohonan.',
            'delete_reason.min' => 'Alasan padam mestilah sekurang-kurangnya 10 aksara.',
            'delete_reason.max' => 'Alasan padam tidak boleh melebihi 2000 aksara.',
        ];
    }
}
