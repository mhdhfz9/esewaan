<?php

namespace App\Http\Requests;

use App\Models\RentalContract;
use App\Support\ApplicationCategories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFollowUpApplicationRequest extends FormRequest
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

        return $contract->isHqApproved()
            && ! $contract->isSuperseded()
            && ! $contract->hasPendingFollowUp()
            && $contract->premise?->negeri === $user->negeri;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kategori_permohonan' => ['required', Rule::in([ApplicationCategories::PINDAH, ApplicationCategories::LANJUTAN])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kategori_permohonan.required' => 'Sila pilih kategori permohonan susulan.',
            'kategori_permohonan.in' => 'Kategori permohonan susulan mesti pindah atau lanjutan.',
        ];
    }
}
