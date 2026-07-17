<div
    id="confirm-action-modal"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirm-action-title"
    aria-describedby="confirm-action-message"
>
    <div class="glass-overlay fixed inset-0" data-confirm-dismiss></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="glass-modal w-full max-w-md overflow-hidden rounded-2xl">
            <div class="px-6 py-5">
                <div class="glass-subtle mb-4 flex h-11 w-11 items-center justify-center rounded-full text-slate-600" id="confirm-action-icon">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 id="confirm-action-title" class="text-lg font-semibold text-slate-900"></h3>
                <p id="confirm-action-message" class="mt-2 text-sm leading-relaxed text-slate-600"></p>
                <div id="confirm-action-details-wrap" class="glass-subtle mt-4 rounded-2xl px-4 py-3 hidden">
                    <dl class="space-y-3 text-sm">
                        <div id="confirm-action-withdrawal-reason-row" class="hidden">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Alasan Permohonan Tarik Semula</dt>
                            <dd id="confirm-action-withdrawal-reason" class="mt-0.5 whitespace-pre-wrap font-medium text-slate-900"></dd>
                        </div>
                    </dl>
                </div>
                <div id="confirm-action-reason-wrap" class="mt-4 hidden">
                    <label for="confirm-action-reason" id="confirm-action-reason-label" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Alasan Padam *
                    </label>
                    <textarea
                        id="confirm-action-reason"
                        rows="3"
                        maxlength="2000"
                        placeholder="Nyatakan sebab permohonan ini perlu dipadam..."
                        class="glass-input w-full resize-none rounded-xl px-3 py-2 text-sm placeholder:text-slate-400"
                    ></textarea>
                    <p id="confirm-action-reason-error" class="mt-1 hidden text-xs text-red-500">Alasan diperlukan (minimum 10 aksara).</p>
                </div>
            </div>
            <div class="glass-divider flex items-center justify-end gap-3 border-t px-6 py-4">
                <button
                    type="button"
                    id="confirm-action-cancel"
                    class="glass-btn-secondary rounded-xl px-4 py-2 text-sm font-medium"
                >
                    Batal
                </button>
                <button
                    type="button"
                    id="confirm-action-confirm"
                    class="glass-btn-primary rounded-xl px-4 py-2 text-sm font-medium"
                >
                    Sahkan
                </button>
            </div>
        </div>
    </div>
</div>

