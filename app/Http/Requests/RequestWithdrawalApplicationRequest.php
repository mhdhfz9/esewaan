<?php

namespace App\Http\Requests;

use App\Models\RentalContract;
use Illuminate\Foundation\Http\FormRequest;

class RequestWithdrawalApplicationRequest extends FormRequest
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
            && $contract->canRequestWithdrawal();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'withdrawal_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'withdrawal_reason.required' => 'Sila nyatakan alasan permohonan tarik semula.',
            'withdrawal_reason.min' => 'Alasan mestilah sekurang-kurangnya 10 aksara.',
            'withdrawal_reason.max' => 'Alasan tidak boleh melebihi 2000 aksara.',
        ];
    }
}
