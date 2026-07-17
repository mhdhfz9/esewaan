<?php

namespace App\Http\Requests;

use App\Models\RentalContract;
use App\Support\BuildingTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AutosaveRentalApplicationRequest extends FormRequest
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

        return $contract->isPendingProceed()
            && $contract->premise?->negeri === $user->negeri;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'negeri' => ['sometimes', 'string', 'max:100'],
            'nama_ptj' => ['sometimes', 'string', 'max:255'],
            'alamat_penuh' => ['sometimes', 'string', 'max:65535'],
            'nama_pemilik' => ['sometimes', 'string', 'max:255'],
            'jenis_bangunan' => ['nullable', Rule::in(BuildingTypes::values())],
            'kadar_sewa' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'keluasan_mp' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'sah_sehingga' => ['sometimes', 'date'],
            'remark' => ['nullable', 'string', 'max:65535'],
            'proceed_steps' => ['sometimes', 'array'],
            'proceed_steps.*.completed' => ['sometimes', 'boolean'],
            'proceed_steps.*.confirmed_accurate' => ['sometimes', 'boolean'],
            'proceed_steps.*.confirmed_promis' => ['sometimes', 'boolean'],
            'proceed_steps.*.notes' => ['nullable', 'string', 'max:2000'],
            'proceed_steps.*.agencies' => ['sometimes', 'array'],
            'proceed_steps.*.agencies.*' => ['sometimes', 'boolean'],
            'proceed_steps.*.agency_dates' => ['sometimes', 'array'],
            'proceed_steps.*.agency_dates.*' => ['nullable', 'date'],
        ];
    }
}
