<?php

namespace App\Support;

use App\Models\RentalContract;

class AdminProceedSteps
{
    public const STEP_NOTIS_PEMILIK = 'notis_pemilik';

    public const STEP_SURAT_NIAT = 'surat_niat';

    public const STEP_BORANG_JRP = 'borang_jrp';

    public const STEP_SURAT_JPPH = 'surat_jpph';

    public const STEP_SURAT_AGENSI = 'surat_agensi';

    /**
     * @return array<int, array{key: string, title: string, description: string, has_notes: bool}>
     */
    public static function definitions(): array
    {
        return [
            1 => [
                'key' => self::STEP_NOTIS_PEMILIK,
                'title' => 'Notis kepada pemilik premis',
                'description' => 'Keluarkan notis kepada pemilik premis untuk sambung kontrak penyewaan.',
                'has_notes' => false,
            ],
            2 => [
                'key' => self::STEP_SURAT_NIAT,
                'title' => 'Surat niat kepada pemilik premis',
                'description' => 'Keluarkan surat niat kepada pemilik premis untuk sewa pejabat baru atau pindah pejabat.',
                'has_notes' => false,
            ],
            3 => [
                'key' => self::STEP_BORANG_JRP,
                'title' => 'Borang JRP',
                'description' => 'Mengisi borang JRP yang berkenaan.',
                'has_notes' => false,
            ],
            4 => [
                'key' => self::STEP_SURAT_JPPH,
                'title' => 'Surat kepada JPPH',
                'description' => 'Keluarkan surat kepada JPPH untuk mohon penilaian kadar sewa dengan kepilkan salinan borang JRP.',
                'has_notes' => false,
            ],
            5 => [
                'key' => self::STEP_SURAT_AGENSI,
                'title' => 'Surat kepada agensi',
                'description' => 'Keluarkan surat kepada agensi berikut dengan kepilkan Borang JRP.',
                'has_notes' => true,
                'has_agency_checklist' => true,
            ],
        ];
    }

    /**
     * @return list<int>
     */
    public static function activeStepsFor(RentalContract $contract): array
    {
        return ApplicationCategories::stepSequence($contract->kategori_permohonan);
    }

    public static function isStepActive(RentalContract $contract, int $step): bool
    {
        return in_array(self::normalizeStep($step), self::activeStepsFor($contract), true);
    }

    public static function normalizeStep(int $step): int
    {
        return max(1, min(5, $step));
    }

    /**
     * @return array{key: string, title: string, description: string, has_notes: bool}|null
     */
    public static function definitionForStep(int $step): ?array
    {
        return self::definitions()[self::normalizeStep($step)] ?? null;
    }

    public static function keyForStep(int $step): string
    {
        $definition = self::definitionForStep($step);

        return $definition['key'] ?? self::STEP_NOTIS_PEMILIK;
    }

    /**
     * @return array<string, string>
     */
    public static function agencyDefinitions(): array
    {
        return [
            'kpksm' => 'Ketua Pegawai Keselamatan Kerajaan Malaysia',
            'bomba' => 'Jabatan Bomba dan Penyelamat Malaysia',
            'jktg' => 'Jabatan Ketua Pengarah Tanah dan Galian Persekutuan',
            'carian_rasmi' => 'Carian Rasmi terkini dari Pejabat Tanah Daerah',
            'surat_tawaran' => 'Surat tawaran dari Pemilik Premis',
            'pelan_lantai' => 'Pelan Lantai',
            'gambar_bangunan' => 'Gambar Bangunan Terkini',
        ];
    }

    /**
     * @return list<string>
     */
    public static function agenciesRequiringDate(): array
    {
        return [
            'kpksm',
            'bomba',
            'jktg',
            'carian_rasmi',
            'surat_tawaran',
        ];
    }

    public static function agencyRequiresDate(string $key): bool
    {
        return in_array($key, self::agenciesRequiringDate(), true);
    }

    /**
     * @return list<string>
     */
    public static function agencyKeys(): array
    {
        return array_keys(self::agencyDefinitions());
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, bool>
     */
    public static function normalizeAgenciesInput(array $input): array
    {
        $agencies = [];

        foreach (self::agencyKeys() as $key) {
            $agencies[$key] = filter_var($input[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return $agencies;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, bool>  $agencies
     * @return array<string, string|null>
     */
    public static function normalizeAgencyDatesInput(array $input, array $agencies): array
    {
        $dates = [];

        foreach (self::agenciesRequiringDate() as $key) {
            $raw = $input[$key] ?? null;
            $dates[$key] = ($agencies[$key] ?? false) && filled($raw)
                ? (string) $raw
                : null;
        }

        return $dates;
    }

    /**
     * @param  array<string, bool>  $agencies
     */
    public static function allAgenciesChecked(array $agencies): bool
    {
        foreach (self::agencyKeys() as $key) {
            if (($agencies[$key] ?? false) !== true) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, bool>  $agencies
     * @param  array<string, string|null>  $agencyDates
     */
    public static function requiredAgencyDatesFilled(array $agencies, array $agencyDates): bool
    {
        foreach (self::agenciesRequiringDate() as $key) {
            if (($agencies[$key] ?? false) === true && ! filled($agencyDates[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, array{completed: bool, marked_complete: bool, confirmed_accurate: bool, confirmed_promis: bool, completed_at: string|null, completed_by_user_id: int|null, notes: string|null, agencies: array<string, bool>, agency_dates: array<string, string|null>}>
     */
    public static function progressFor(RentalContract $contract): array
    {
        $stored = $contract->admin_proceed_progress ?? [];

        if (! is_array($stored)) {
            return [];
        }

        $progress = [];

        foreach (self::definitions() as $definition) {
            $key = $definition['key'];
            $entry = $stored[$key] ?? null;

            if (! is_array($entry)) {
                continue;
            }

            $progress[$key] = [
                'completed' => (bool) ($entry['completed'] ?? false),
                'marked_complete' => (bool) ($entry['marked_complete'] ?? $entry['completed'] ?? false),
                'confirmed_accurate' => (bool) ($entry['confirmed_accurate'] ?? false),
                'confirmed_promis' => (bool) ($entry['confirmed_promis'] ?? false),
                'completed_at' => $entry['completed_at'] ?? null,
                'completed_by_user_id' => isset($entry['completed_by_user_id']) ? (int) $entry['completed_by_user_id'] : null,
                'notes' => filled($entry['notes'] ?? null) ? (string) $entry['notes'] : null,
                'agencies' => $key === self::STEP_SURAT_AGENSI
                    ? self::normalizeAgenciesInput(is_array($entry['agencies'] ?? null) ? $entry['agencies'] : [])
                    : [],
                'agency_dates' => $key === self::STEP_SURAT_AGENSI
                    ? self::normalizeAgencyDatesInput(
                        is_array($entry['agency_dates'] ?? null) ? $entry['agency_dates'] : [],
                        self::normalizeAgenciesInput(is_array($entry['agencies'] ?? null) ? $entry['agencies'] : []),
                    )
                    : [],
            ];
        }

        return $progress;
    }

    public static function isStepCompleted(RentalContract $contract, int $step): bool
    {
        $key = self::keyForStep($step);
        $progress = self::progressFor($contract);

        return ($progress[$key]['completed'] ?? false) === true;
    }

    public static function shouldCompleteStepFromInput(array $stepInput, string $stepKey): bool
    {
        if (! filter_var($stepInput['completed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        if (! filter_var($stepInput['confirmed_accurate'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        if (! filter_var($stepInput['confirmed_promis'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        if ($stepKey === self::STEP_SURAT_AGENSI) {
            $agencies = self::normalizeAgenciesInput(is_array($stepInput['agencies'] ?? null) ? $stepInput['agencies'] : []);
            $agencyDates = self::normalizeAgencyDatesInput(
                is_array($stepInput['agency_dates'] ?? null) ? $stepInput['agency_dates'] : [],
                $agencies,
            );

            if (! self::allAgenciesChecked($agencies) || ! self::requiredAgencyDatesFilled($agencies, $agencyDates)) {
                return false;
            }
        }

        return true;
    }

    public static function isNavigable(RentalContract $contract, int $step): bool
    {
        $step = self::normalizeStep($step);

        if (! self::isStepActive($contract, $step)) {
            return false;
        }

        if (self::isStepCompleted($contract, $step)) {
            return true;
        }

        foreach (self::activeStepsFor($contract) as $activeStep) {
            if ($activeStep === $step) {
                return true;
            }

            if (! self::isStepCompleted($contract, $activeStep)) {
                return false;
            }
        }

        return false;
    }

    public static function firstIncompleteStep(RentalContract $contract): int
    {
        foreach (self::activeStepsFor($contract) as $step) {
            if (! self::isStepCompleted($contract, $step)) {
                return $step;
            }
        }

        $activeSteps = self::activeStepsFor($contract);

        return $activeSteps[array_key_last($activeSteps)] ?? 2;
    }

    public static function nextStep(RentalContract $contract, int $currentStep): ?int
    {
        $activeSteps = self::activeStepsFor($contract);
        $index = array_search(self::normalizeStep($currentStep), $activeSteps, true);

        if ($index === false || ! isset($activeSteps[$index + 1])) {
            return null;
        }

        return $activeSteps[$index + 1];
    }

    public static function previousStep(RentalContract $contract, int $currentStep): ?int
    {
        $activeSteps = self::activeStepsFor($contract);
        $index = array_search(self::normalizeStep($currentStep), $activeSteps, true);

        if ($index === false || $index === 0) {
            return null;
        }

        return $activeSteps[$index - 1];
    }

    public static function stepPosition(RentalContract $contract, int $step): int
    {
        $activeSteps = self::activeStepsFor($contract);
        $index = array_search(self::normalizeStep($step), $activeSteps, true);

        return $index === false ? 1 : $index + 1;
    }

    public static function totalActiveSteps(RentalContract $contract): int
    {
        return count(self::activeStepsFor($contract));
    }

    public static function completedCount(RentalContract $contract): int
    {
        $count = 0;

        foreach (self::activeStepsFor($contract) as $step) {
            if (self::isStepCompleted($contract, $step)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array<int, array{number: int, position: int, key: string, title: string, description: string, has_notes: bool, completed: bool, notes: string}>
     */
    public static function stepPanelsFor(RentalContract $contract): array
    {
        $progress = self::progressFor($contract);
        $panels = [];

        foreach (self::activeStepsFor($contract) as $index => $stepNumber) {
            $definition = self::definitionForStep($stepNumber);

            if ($definition === null) {
                continue;
            }

            $key = $definition['key'];

            $panels[] = [
                'number' => $stepNumber,
                'position' => $index + 1,
                'key' => $key,
                'title' => $definition['title'],
                'description' => $definition['description'],
                'has_notes' => true,
                'has_agency_checklist' => ($definition['has_agency_checklist'] ?? false) === true,
                'completed' => ($progress[$key]['completed'] ?? false) === true,
                'marked_complete' => ($progress[$key]['marked_complete'] ?? $progress[$key]['completed'] ?? false) === true,
                'confirmed_accurate' => ($progress[$key]['confirmed_accurate'] ?? false) === true,
                'confirmed_promis' => ($progress[$key]['confirmed_promis'] ?? false) === true,
                'notes' => (string) ($progress[$key]['notes'] ?? ''),
                'agencies' => $key === self::STEP_SURAT_AGENSI
                    ? ($progress[$key]['agencies'] ?? self::normalizeAgenciesInput([]))
                    : [],
                'agency_dates' => $key === self::STEP_SURAT_AGENSI
                    ? ($progress[$key]['agency_dates'] ?? self::normalizeAgencyDatesInput([], []))
                    : [],
            ];
        }

        return $panels;
    }

    /**
     * @param  array<string, array{completed?: bool|string|null, notes?: string|null, agencies?: array<string, mixed>}>  $input
     */
    public static function syncDraftFromInput(RentalContract $contract, array $input): void
    {
        $progress = $contract->admin_proceed_progress ?? [];

        if (! is_array($progress)) {
            $progress = [];
        }

        foreach (self::activeStepsFor($contract) as $stepNumber) {
            $key = self::keyForStep($stepNumber);

            if (! array_key_exists($key, $input)) {
                continue;
            }

            $stepInput = $input[$key];
            $existing = is_array($progress[$key] ?? null) ? $progress[$key] : [];
            $markedComplete = array_key_exists('completed', $stepInput)
                ? filter_var($stepInput['completed'] ?? false, FILTER_VALIDATE_BOOLEAN)
                : (bool) ($existing['marked_complete'] ?? $existing['completed'] ?? false);
            $isCompleted = self::shouldCompleteStepFromInput($stepInput, $key);

            $entry = [
                'completed' => $isCompleted,
                'marked_complete' => $markedComplete,
                'confirmed_accurate' => array_key_exists('confirmed_accurate', $stepInput)
                    ? filter_var($stepInput['confirmed_accurate'] ?? false, FILTER_VALIDATE_BOOLEAN)
                    : (bool) ($existing['confirmed_accurate'] ?? false),
                'confirmed_promis' => array_key_exists('confirmed_promis', $stepInput)
                    ? filter_var($stepInput['confirmed_promis'] ?? false, FILTER_VALIDATE_BOOLEAN)
                    : (bool) ($existing['confirmed_promis'] ?? false),
                'notes' => array_key_exists('notes', $stepInput)
                    ? (filled($stepInput['notes']) ? (string) $stepInput['notes'] : null)
                    : ($existing['notes'] ?? null),
            ];

            if ($isCompleted) {
                $entry['completed_at'] = $existing['completed_at'] ?? now()->toIso8601String();
                $entry['completed_by_user_id'] = $existing['completed_by_user_id'] ?? null;
            } else {
                $entry['completed_at'] = null;
                $entry['completed_by_user_id'] = null;
            }

            if ($key === self::STEP_SURAT_AGENSI) {
                $agencies = array_key_exists('agencies', $stepInput)
                    ? self::normalizeAgenciesInput(is_array($stepInput['agencies']) ? $stepInput['agencies'] : [])
                    : self::normalizeAgenciesInput(is_array($existing['agencies'] ?? null) ? $existing['agencies'] : []);

                $entry['agencies'] = $agencies;
                $entry['agency_dates'] = array_key_exists('agency_dates', $stepInput)
                    ? self::normalizeAgencyDatesInput(
                        is_array($stepInput['agency_dates']) ? $stepInput['agency_dates'] : [],
                        $agencies,
                    )
                    : self::normalizeAgencyDatesInput(
                        is_array($existing['agency_dates'] ?? null) ? $existing['agency_dates'] : [],
                        $agencies,
                    );
            }

            $progress[$key] = $entry;
        }

        $contract->update(['admin_proceed_progress' => $progress]);
    }

    /**
     * @param  array{notes?: string|null, agencies?: array<string, mixed>}  $stepInput
     */
    public static function completeStepFromInput(RentalContract $contract, int $stepNumber, array $stepInput, int $userId): void
    {
        $stepNumber = self::normalizeStep($stepNumber);
        $key = self::keyForStep($stepNumber);
        $progress = $contract->admin_proceed_progress ?? [];

        if (! is_array($progress)) {
            $progress = [];
        }

        $entry = [
            'completed' => true,
            'marked_complete' => true,
            'confirmed_accurate' => filter_var($stepInput['confirmed_accurate'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'confirmed_promis' => filter_var($stepInput['confirmed_promis'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'completed_at' => now()->toIso8601String(),
            'completed_by_user_id' => $userId,
            'notes' => filled($stepInput['notes'] ?? null) ? (string) $stepInput['notes'] : null,
        ];

        if ($key === self::STEP_SURAT_AGENSI) {
            $agencies = self::normalizeAgenciesInput(is_array($stepInput['agencies'] ?? null) ? $stepInput['agencies'] : []);
            $entry['agencies'] = $agencies;
            $entry['agency_dates'] = self::normalizeAgencyDatesInput(
                is_array($stepInput['agency_dates'] ?? null) ? $stepInput['agency_dates'] : [],
                $agencies,
            );
        }

        $progress[$key] = $entry;

        $contract->update(['admin_proceed_progress' => $progress]);
    }

    /**
     * @return array{completedCount: int, totalSteps: int, progressPercent: int, allCompleted: bool}
     */
    public static function progressSummary(RentalContract $contract): array
    {
        $totalSteps = self::totalActiveSteps($contract);
        $completedCount = self::completedCount($contract);

        return [
            'completedCount' => $completedCount,
            'totalSteps' => $totalSteps,
            'progressPercent' => $totalSteps > 0 ? (int) round(($completedCount / $totalSteps) * 100) : 0,
            'allCompleted' => self::allCompleted($contract),
        ];
    }

    public static function allCompleted(RentalContract $contract): bool
    {
        foreach (self::activeStepsFor($contract) as $step) {
            if (! self::isStepCompleted($contract, $step)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public static function viewData(RentalContract $contract, ?int $requestedStep = null): array
    {
        $requestedStep = self::normalizeStep((int) ($requestedStep ?? self::firstIncompleteStep($contract)));
        $step = self::isNavigable($contract, $requestedStep)
            ? $requestedStep
            : self::firstIncompleteStep($contract);

        $currentStep = self::definitionForStep($step);

        if ($currentStep === null) {
            abort(404);
        }

        return [
            'step' => $step,
            'currentStep' => $currentStep,
            'progress' => self::progressFor($contract),
            'activeSteps' => self::activeStepsFor($contract),
            'stepPosition' => self::stepPosition($contract, $step),
            'totalActiveSteps' => self::totalActiveSteps($contract),
            'allCompleted' => self::allCompleted($contract),
            'completedCount' => self::completedCount($contract),
            'previousStep' => self::previousStep($contract, $step),
            'nextStep' => self::nextStep($contract, $step),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function stepLabels(): array
    {
        return collect(self::definitions())
            ->mapWithKeys(fn (array $definition, int $step): array => [$step => $definition['title']])
            ->all();
    }

    /**
     * @return list<array{number: int, position: int, key: string, title: string, description: string, has_notes: bool, completed: bool, notes: string}>
     */
    public static function previewPanelsFor(?string $kategori): array
    {
        $panels = [];

        foreach (ApplicationCategories::stepSequence($kategori) as $index => $stepNumber) {
            $definition = self::definitionForStep($stepNumber);

            if ($definition === null) {
                continue;
            }

            $panels[] = [
                'number' => $stepNumber,
                'position' => $index + 1,
                'key' => $definition['key'],
                'title' => $definition['title'],
                'description' => $definition['description'],
                'has_notes' => $definition['has_notes'],
                'completed' => false,
                'notes' => '',
            ];
        }

        return $panels;
    }

    /**
     * @return array<string, mixed>
     */
    public static function previewViewData(?string $kategori = null): array
    {
        $kategori = $kategori ?? ApplicationCategories::BARU;
        $activeSteps = ApplicationCategories::stepSequence($kategori);
        $firstStep = $activeSteps[0] ?? 1;

        return [
            'step' => $firstStep,
            'initialStep' => $firstStep,
            'progress' => [],
            'activeSteps' => $activeSteps,
            'totalActiveSteps' => count($activeSteps),
            'completedCount' => 0,
            'stepPanels' => self::previewPanelsFor($kategori),
            'proceedPreview' => true,
        ];
    }
}
