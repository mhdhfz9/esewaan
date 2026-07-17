<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAdminProceedRequest;
use App\Models\RentalContract;
use App\Services\ActivityLogger;
use App\Services\SidebarNotificationService;
use App\Support\AdminProceedSteps;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminProceedController extends Controller
{
    public function show(Request $request, RentalContract $contract): View
    {
        $this->ensureAccessible($request, $contract);

        $contract->loadMissing(['premise', 'submittedBy']);
        app(SidebarNotificationService::class)->markContractAsViewed($request->user(), $contract);
        $proceedData = AdminProceedSteps::viewData($contract, (int) $request->query('step', 0));

        return view('application.tindakan', array_merge(
            $proceedData,
            [
                'contract' => $contract,
                'recipient' => $contract->submittedBy,
                'stepPanels' => AdminProceedSteps::stepPanelsFor($contract),
                'initialStep' => $proceedData['step'],
            ],
        ));
    }

    public function update(SaveAdminProceedRequest $request, RentalContract $contract): RedirectResponse|JsonResponse
    {
        $this->ensureAccessible($request, $contract);

        $contract->loadMissing(['premise', 'submittedBy']);

        $currentStep = AdminProceedSteps::normalizeStep((int) $request->validated('current_step'));
        $allInput = $request->validated('proceed_steps');
        $stepKey = AdminProceedSteps::keyForStep($currentStep);
        $stepInput = $allInput[$stepKey] ?? [];

        AdminProceedSteps::syncDraftFromInput($contract, $allInput);

        $contract = $contract->fresh(['premise', 'submittedBy']);

        if (AdminProceedSteps::shouldCompleteStepFromInput($stepInput, $stepKey)) {
            AdminProceedSteps::completeStepFromInput(
                $contract,
                $currentStep,
                $stepInput,
                $request->user()->id,
            );

            $contract = $contract->fresh(['premise', 'submittedBy']);

            ActivityLogger::log(
                $request->user(),
                'admin_proceed_step_completed',
                'Langkah tindakan '.$currentStep.' untuk premis '.$contract->premise?->nama_ptj.' disimpan.',
                ['contract_id' => $contract->id, 'step' => $currentStep],
            );
        }

        $nextStep = AdminProceedSteps::nextStep($contract, $currentStep) ?? $currentStep;

        $message = $contract->isProceedComplete()
            ? 'Semua langkah tindakan selesai. Permohonan sedia untuk dihantar ke HQ dari senarai status.'
            : 'Draf disimpan. Sila teruskan langkah seterusnya.';

        if ($request->wantsJson()) {
            return $this->jsonSaveResponse($contract, $message, $currentStep, $nextStep);
        }

        if ($contract->isProceedComplete()) {
            return redirect()
                ->route('status-permohonan.index')
                ->with('success', $message);
        }

        return redirect()
            ->route('admin-proceed.show', ['contract' => $contract, 'step' => $nextStep])
            ->with('success', $message);
    }

    private function jsonSaveResponse(RentalContract $contract, string $message, int $currentStep, ?int $nextStep): JsonResponse
    {
        $summary = AdminProceedSteps::progressSummary($contract);
        $progress = AdminProceedSteps::progressFor($contract);
        $steps = [];

        foreach (AdminProceedSteps::stepPanelsFor($contract) as $panel) {
            $steps[$panel['key']] = [
                'completed' => ($progress[$panel['key']]['completed'] ?? false) === true,
                'marked_complete' => ($progress[$panel['key']]['marked_complete'] ?? $progress[$panel['key']]['completed'] ?? false) === true,
                'confirmed_accurate' => ($progress[$panel['key']]['confirmed_accurate'] ?? false) === true,
                'confirmed_promis' => ($progress[$panel['key']]['confirmed_promis'] ?? false) === true,
                'notes' => $progress[$panel['key']]['notes'] ?? '',
                'agencies' => $progress[$panel['key']]['agencies'] ?? [],
                'agency_dates' => $progress[$panel['key']]['agency_dates'] ?? [],
            ];
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'progress' => $summary,
            'steps' => $steps,
            'allCompleted' => $contract->isProceedComplete(),
            'nextStep' => $nextStep,
            'currentStep' => $currentStep,
        ]);
    }

    private function ensureAccessible(Request $request, RentalContract $contract): void
    {
        $user = $request->user();

        if (! $user?->isAdminNegeri() || ! filled($user->negeri)) {
            abort(403, 'Akses ditolak.');
        }

        $contract->loadMissing('premise');

        if ($contract->premise?->negeri !== $user->negeri) {
            abort(403, 'Akses ditolak. Tindakan hanya boleh dilakukan untuk permohonan dalam negeri anda.');
        }

        if (! $contract->isPendingProceed()) {
            abort(403, 'Tindakan hanya tersedia untuk permohonan yang belum dihantar kepada HQ.');
        }
    }
}
