<?php

namespace App\Http\Requests;

use App\Support\ApplicationCategories;
use App\Support\BuildingTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRentalApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isAdminNegeri() === true && filled($user->negeri);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kategori_permohonan' => ['required', Rule::in([ApplicationCategories::BARU])],
            'negeri' => ['required', 'string', 'max:100'],
            'nama_ptj' => ['required', 'string', 'max:255'],
            'alamat_penuh' => ['required', 'string', 'max:65535'],
            'nama_pemilik' => ['required', 'string', 'max:255'],
            'jenis_bangunan' => ['required', Rule::in(BuildingTypes::values())],
            'kadar_sewa' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'keluasan_mp' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'sah_sehingga' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kategori_permohonan.required' => 'Sila pilih kategori permohonan.',
            'kategori_permohonan.in' => 'Permohonan baharu mesti berkategori Baru.',
            'negeri.required' => 'Negeri diperlukan.',
            'nama_ptj.required' => 'Nama premis diperlukan.',
            'alamat_penuh.required' => 'Alamat penuh premis diperlukan.',
            'nama_pemilik.required' => 'Pemilik diperlukan.',
            'jenis_bangunan.required' => 'Jenis bangunan diperlukan.',
            'jenis_bangunan.in' => 'Jenis bangunan tidak sah.',
            'kadar_sewa.required' => 'Kadar sewa (RM) diperlukan.',
            'kadar_sewa.numeric' => 'Kadar sewa mesti nombor yang sah.',
            'kadar_sewa.min' => 'Kadar sewa tidak boleh kurang daripada 0.',
            'keluasan_mp.required' => 'Keluasan (mps) diperlukan.',
            'keluasan_mp.numeric' => 'Keluasan (mps) mesti nombor yang sah.',
            'keluasan_mp.min' => 'Keluasan (mps) tidak boleh kurang daripada 0.',
            'sah_sehingga.required' => 'Tarikh sah sehingga diperlukan.',
            'sah_sehingga.date' => 'Tarikh sah sehingga mesti tarikh yang sah.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kategori_permohonan' => ApplicationCategories::BARU,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $admin = $this->user();

            if ($this->input('negeri') !== $admin?->negeri) {
                $validator->errors()->add('negeri', 'Negeri mesti sepadan dengan negeri pentadbir anda.');
            }
        });
    }
}
