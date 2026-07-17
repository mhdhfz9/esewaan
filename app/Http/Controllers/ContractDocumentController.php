<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractDocumentRequest;
use App\Models\ContractDocument;
use App\Models\RentalContract;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContractDocumentController extends Controller
{
    public function create(RentalContract $contract): View
    {
        $contract->load('premise');
        $user = request()->user();
        if ($user->isAdminNegeri() && $contract->premise?->negeri !== $user->negeri) {
            abort(403, 'Akses ditolak. Dokumen hanya boleh dimuat naik untuk kontrak dalam negeri anda.');
        }

        return view('documents.create', ['contract' => $contract]);
    }

    public function store(StoreContractDocumentRequest $request, RentalContract $contract): RedirectResponse
    {
        $file = $request->file('document');
        $path = $file->store('contract-documents/'.$contract->id, 'public');

        $safeName = $this->sanitizeFilename($file->getClientOriginalName());

        ContractDocument::query()->create([
            'contract_id' => $contract->id,
            'nama_fail' => $safeName,
            'path' => $path,
            'jenis' => $request->validated('jenis'),
            'user_id' => $request->user()->id,
        ]);

        ActivityLogger::log(
            $request->user(),
            'document_uploaded',
            'Dokumen '.$safeName.' ('.$request->validated('jenis').') dimuat naik.',
            ['contract_id' => $contract->id],
        );

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Dokumen berjaya dimuat naik.');
    }

    private function sanitizeFilename(string $name): string
    {
        $name = basename(str_replace(["\0", '..'], '', $name));
        $name = preg_replace('/[^\pL\pN._-]/u', '_', $name) ?? $name;

        return mb_substr($name, 0, 255);
    }
}
