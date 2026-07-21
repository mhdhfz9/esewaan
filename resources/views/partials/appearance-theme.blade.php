<style>
    /* Font size scales rem-based Tailwind utilities */
    html[data-font-size="sm"] { font-size: 14px; }
    html[data-font-size="md"] { font-size: 16px; }
    html[data-font-size="lg"] { font-size: 18px; }
    html[data-font-size="xl"] { font-size: 20px; }

    /* High contrast */
    html[data-contrast="high"] .glass-card,
    html[data-contrast="high"] .glass-panel,
    html[data-contrast="high"] .glass-header,
    html[data-contrast="high"] .glass-subtle,
    html[data-contrast="high"] .glass-panel-muted {
        background: #ffffff;
        border-color: #0f172a;
        box-shadow: none;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }

    html[data-contrast="high"] .glass-input,
    html[data-contrast="high"] .liquid-bg input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
    html[data-contrast="high"] .liquid-bg select,
    html[data-contrast="high"] .liquid-bg textarea {
        border-color: #0f172a !important;
        background: #ffffff !important;
    }

    html[data-contrast="high"] .liquid-bg {
        background: #ffffff;
        background-image: none;
    }

    html[data-contrast="high"] .liquid-bg-mesh {
        display: none;
    }

    html[data-contrast="high"] body,
    html[data-contrast="high"] .text-slate-500,
    html[data-contrast="high"] .text-slate-600,
    html[data-contrast="high"] .text-gray-500,
    html[data-contrast="high"] .text-gray-600 {
        color: #0f172a !important;
    }

    /* Dark mode — override glass surfaces & common text utilities */
    html[data-theme="dark"] .liquid-bg {
        background-color: #0b1220;
        background-image:
            radial-gradient(ellipse 95% 70% at 0% 0%, rgba(56, 189, 248, 0.08) 0%, transparent 55%),
            radial-gradient(ellipse 80% 60% at 100% 0%, rgba(99, 102, 241, 0.07) 0%, transparent 52%),
            linear-gradient(165deg, #0b1220 0%, #111827 45%, #0f172a 100%);
        color: #e2e8f0;
    }

    html[data-theme="dark"] .liquid-orb-1 {
        background: radial-gradient(circle, rgba(56, 189, 248, 0.16) 0%, transparent 70%);
    }

    html[data-theme="dark"] .liquid-orb-2 {
        background: radial-gradient(circle, rgba(99, 102, 241, 0.14) 0%, transparent 72%);
    }

    html[data-theme="dark"] .liquid-orb-3 {
        background: radial-gradient(circle, rgba(148, 163, 184, 0.12) 0%, transparent 68%);
        opacity: 0.35;
    }

    html[data-theme="dark"] .glass-header {
        background: rgba(15, 23, 42, 0.92);
        border-bottom-color: rgba(148, 163, 184, 0.22);
        box-shadow: 0 4px 22px rgba(0, 0, 0, 0.35);
    }

    html[data-theme="dark"] .glass-card,
    html[data-theme="dark"] .glass,
    html[data-theme="dark"] .glass-panel,
    html[data-theme="dark"] .glass-subtle,
    html[data-theme="dark"] .glass-table {
        background: rgba(15, 23, 42, 0.88);
        border-color: rgba(148, 163, 184, 0.28);
        box-shadow:
            0 8px 28px rgba(0, 0, 0, 0.35),
            inset 0 1px 0 rgba(255, 255, 255, 0.05);
        color: #e2e8f0;
    }

    html[data-theme="dark"] .glass-panel-muted {
        background: rgba(2, 6, 23, 0.72);
        border-color: rgba(71, 85, 105, 0.65);
        color: #e2e8f0;
    }

    /* Tables: dark header + bright labels (fixes low-contrast thead) */
    html[data-theme="dark"] .glass-table thead {
        background: #0f172a;
        border-bottom: 1px solid rgba(148, 163, 184, 0.35);
    }

    html[data-theme="dark"] .glass-table thead th {
        color: #f8fafc !important;
    }

    html[data-theme="dark"] .glass-table tbody td {
        color: #e2e8f0;
        border-color: rgba(71, 85, 105, 0.45);
    }

    html[data-theme="dark"] .glass-table tbody tr {
        border-color: rgba(71, 85, 105, 0.45);
    }

    html[data-theme="dark"] .glass-divider,
    html[data-theme="dark"] .glass-divider-soft,
    html[data-theme="dark"] .border-b,
    html[data-theme="dark"] .border-slate-200,
    html[data-theme="dark"] .border-slate-300,
    html[data-theme="dark"] .border-gray-200 {
        border-color: rgba(100, 116, 139, 0.45) !important;
    }

    html[data-theme="dark"] .divide-gray-50 > :not([hidden]) ~ :not([hidden]),
    html[data-theme="dark"] .divide-slate-100 > :not([hidden]) ~ :not([hidden]),
    html[data-theme="dark"] .divide-y > :not([hidden]) ~ :not([hidden]) {
        border-color: rgba(71, 85, 105, 0.5);
    }

    html[data-theme="dark"] .glass-row-neutral:hover,
    html[data-theme="dark"] .glass-row-hover:hover {
        background: rgba(51, 65, 85, 0.55);
    }

    html[data-theme="dark"] .glass-row-pending,
    html[data-theme="dark"] .glass-row-withdrawal-pending {
        background: rgba(120, 53, 15, 0.35);
    }

    html[data-theme="dark"] .glass-row-pending:hover,
    html[data-theme="dark"] .glass-row-withdrawal-pending:hover {
        background: rgba(146, 64, 14, 0.45);
    }

    html[data-theme="dark"] .glass-row-ready-hq {
        background: rgba(6, 78, 59, 0.35);
    }

    html[data-theme="dark"] .glass-row-ready-hq:hover {
        background: rgba(6, 95, 70, 0.45);
    }

    html[data-theme="dark"] .glass-row-hq-review,
    html[data-theme="dark"] .bg-slate-50,
    html[data-theme="dark"] .bg-slate-50\/80,
    html[data-theme="dark"] .bg-gray-50 {
        background: rgba(30, 41, 59, 0.65) !important;
    }

    html[data-theme="dark"] .glass-row-hq-review:hover {
        background: rgba(51, 65, 85, 0.75);
    }

    html[data-theme="dark"] .bg-white\/40,
    html[data-theme="dark"] .bg-white\/50,
    html[data-theme="dark"] .bg-white\/60,
    html[data-theme="dark"] .bg-white\/70 {
        background-color: rgba(15, 23, 42, 0.65) !important;
    }

    html[data-theme="dark"] .hover\:bg-slate-100:hover,
    html[data-theme="dark"] .hover\:bg-white\/60:hover {
        background-color: rgba(51, 65, 85, 0.85) !important;
    }

    html[data-theme="dark"] .glass-modal {
        background: rgba(15, 23, 42, 0.96);
        border-color: rgba(148, 163, 184, 0.28);
        color: #e2e8f0;
    }

    html[data-theme="dark"] .glass-btn-secondary {
        background: rgba(30, 41, 59, 0.95);
        border-color: rgba(148, 163, 184, 0.45);
        color: #f1f5f9;
        box-shadow: none;
    }

    html[data-theme="dark"] .glass-btn-secondary:hover:not(:disabled) {
        background: rgba(51, 65, 85, 0.98);
        border-color: rgba(148, 163, 184, 0.65);
    }

    html[data-theme="dark"] .glass-input,
    html[data-theme="dark"] .liquid-bg input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="image"]):not([type="range"]):not([type="color"]),
    html[data-theme="dark"] .liquid-bg select,
    html[data-theme="dark"] .liquid-bg textarea {
        background: rgba(2, 6, 23, 0.92) !important;
        border-color: rgba(100, 116, 139, 0.65) !important;
        color: #f1f5f9 !important;
        box-shadow: none !important;
    }

    html[data-theme="dark"] .glass-header h1,
    html[data-theme="dark"] .text-slate-900,
    html[data-theme="dark"] .text-slate-800,
    html[data-theme="dark"] .text-gray-900,
    html[data-theme="dark"] .text-gray-800 {
        color: #f8fafc !important;
    }

    html[data-theme="dark"] .text-slate-700,
    html[data-theme="dark"] .text-gray-700 {
        color: #f1f5f9 !important;
    }

    html[data-theme="dark"] .text-slate-600,
    html[data-theme="dark"] .text-slate-500,
    html[data-theme="dark"] .text-gray-600,
    html[data-theme="dark"] .text-gray-500,
    html[data-theme="dark"] .glass-header p {
        color: #cbd5e1 !important;
    }

    html[data-theme="dark"] .text-slate-400,
    html[data-theme="dark"] .text-gray-400 {
        color: #94a3b8 !important;
    }

    html[data-theme="dark"] .glass-link,
    html[data-theme="dark"] .glass-accent-text {
        color: #7dd3fc !important;
    }

    html[data-theme="dark"] .glass-link:hover {
        color: #bae6fd !important;
    }

    html[data-theme="dark"] .glass-alert-success {
        background: rgba(6, 78, 59, 0.55);
        color: #a7f3d0 !important;
        border-color: rgba(52, 211, 153, 0.45);
    }

    html[data-theme="dark"] .glass-alert-error {
        background: rgba(127, 29, 29, 0.55);
        color: #fecaca !important;
        border-color: rgba(248, 113, 113, 0.45);
    }

    html[data-theme="dark"] .glass-alert-warning {
        background: rgba(120, 53, 15, 0.55);
        color: #fde68a !important;
        border-color: rgba(251, 191, 36, 0.45);
    }

    html[data-theme="dark"] .glass-status-badge {
        border-color: rgba(148, 163, 184, 0.35);
    }

    html[data-theme="dark"][data-contrast="high"] .liquid-bg {
        background: #000000;
        background-image: none;
    }

    html[data-theme="dark"][data-contrast="high"] .glass-card,
    html[data-theme="dark"][data-contrast="high"] .glass-panel,
    html[data-theme="dark"][data-contrast="high"] .glass-header,
    html[data-theme="dark"][data-contrast="high"] .glass-subtle,
    html[data-theme="dark"][data-contrast="high"] .glass-panel-muted {
        background: #000000;
        border-color: #ffffff;
    }

    html[data-theme="dark"][data-contrast="high"] .text-slate-900,
    html[data-theme="dark"][data-contrast="high"] .text-slate-800,
    html[data-theme="dark"][data-contrast="high"] .text-slate-700,
    html[data-theme="dark"][data-contrast="high"] .text-slate-600,
    html[data-theme="dark"][data-contrast="high"] .text-slate-500,
    html[data-theme="dark"][data-contrast="high"] .text-gray-900,
    html[data-theme="dark"][data-contrast="high"] .text-gray-800,
    html[data-theme="dark"][data-contrast="high"] .text-gray-700,
    html[data-theme="dark"][data-contrast="high"] .text-gray-600,
    html[data-theme="dark"][data-contrast="high"] .text-gray-500 {
        color: #ffffff !important;
    }

    /* Appearance page controls */
    .appearance-option {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
        width: 100%;
        border-radius: 0.85rem;
        border: 1px solid rgba(148, 163, 184, 0.45);
        background: rgba(255, 255, 255, 0.55);
        padding: 0.85rem 1rem;
        text-align: left;
        transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
        cursor: pointer;
    }

    .appearance-option:hover {
        border-color: rgba(30, 58, 95, 0.45);
        background: rgba(255, 255, 255, 0.8);
    }

    .appearance-option.is-selected {
        border-color: #1e3a5f;
        background: rgba(30, 58, 95, 0.08);
        box-shadow: 0 0 0 1px rgba(30, 58, 95, 0.2);
    }

    .appearance-option-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #0f172a;
    }

    .appearance-option-desc {
        font-size: 0.75rem;
        color: #64748b;
    }

    html[data-theme="dark"] .appearance-option {
        background: rgba(15, 23, 42, 0.65);
        border-color: rgba(100, 116, 139, 0.45);
    }

    html[data-theme="dark"] .appearance-option:hover {
        background: rgba(30, 41, 59, 0.85);
    }

    html[data-theme="dark"] .appearance-option.is-selected {
        border-color: #38bdf8;
        background: rgba(56, 189, 248, 0.12);
        box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.25);
    }

    html[data-theme="dark"] .appearance-option-title {
        color: #f1f5f9;
    }

    html[data-theme="dark"] .appearance-option-desc {
        color: #94a3b8;
    }
</style>
