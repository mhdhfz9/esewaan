<style>
    :root {
        --color-navy-950: #0a1628;
        --color-navy-900: #0f2744;
        --color-navy-800: #1e3a5f;
        --color-navy-700: #2a4a73;
        --color-brand: #1e3a5f;
        --color-brand-hover: #254770;
        --color-brand-soft: rgba(30, 58, 95, 0.1);
        --color-brand-ring: rgba(30, 58, 95, 0.22);
        --color-slate-50: #f8fafc;
        --color-slate-100: #f1f5f9;
        --color-slate-200: #e2e8f0;
        --color-slate-300: #cbd5e1;
        --color-slate-500: #64748b;
        --color-slate-700: #334155;
        --glass-border: rgba(255, 255, 255, 0.7);
        --glass-shadow: rgba(15, 39, 68, 0.07);
        --glass-surface: rgba(255, 255, 255, 0.64);
        --glass-surface-strong: rgba(255, 255, 255, 0.78);
    }

    @keyframes liquid-float-1 {
        0%, 100% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(24px, -18px) scale(1.04); }
    }

    @keyframes liquid-float-2 {
        0%, 100% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(-20px, 22px) scale(1.06); }
    }

    @keyframes liquid-float-3 {
        0%, 100% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(16px, 14px) scale(0.97); }
    }

    .liquid-bg {
        background-color: #e8eef5;
        background-image:
            radial-gradient(ellipse 95% 70% at 0% 0%, rgba(30, 58, 95, 0.08) 0%, transparent 55%),
            radial-gradient(ellipse 80% 60% at 100% 0%, rgba(42, 74, 115, 0.07) 0%, transparent 52%),
            radial-gradient(ellipse 70% 55% at 50% 100%, rgba(226, 232, 240, 0.85) 0%, transparent 60%),
            linear-gradient(165deg, #f1f5f9 0%, #e8eef5 36%, #dde5ef 70%, #d0dae6 100%);
        position: relative;
        isolation: isolate;
        min-height: 100vh;
    }

    .liquid-bg-mesh {
        position: fixed;
        inset: 0;
        overflow: hidden;
        pointer-events: none;
        z-index: 0;
    }

    .liquid-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.5;
        will-change: transform;
    }

    .liquid-orb-1 {
        width: 520px;
        height: 520px;
        top: -12%;
        right: -8%;
        background: radial-gradient(circle, rgba(30, 58, 95, 0.18) 0%, rgba(30, 58, 95, 0.05) 45%, transparent 70%);
        animation: liquid-float-1 22s ease-in-out infinite;
    }

    .liquid-orb-2 {
        width: 460px;
        height: 460px;
        bottom: -10%;
        left: -6%;
        background: radial-gradient(circle, rgba(100, 116, 139, 0.2) 0%, rgba(148, 163, 184, 0.06) 50%, transparent 72%);
        animation: liquid-float-2 26s ease-in-out infinite;
    }

    .liquid-orb-3 {
        width: 340px;
        height: 340px;
        top: 42%;
        left: 38%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.9) 0%, rgba(226, 232, 240, 0.25) 40%, transparent 68%);
        animation: liquid-float-3 18s ease-in-out infinite;
    }

    @media (prefers-reduced-motion: reduce) {
        .liquid-orb {
            animation: none;
        }
    }

    .liquid-bg > *:not(.liquid-bg-mesh) {
        position: relative;
        z-index: 1;
    }

    .glass {
        background: var(--glass-surface);
        backdrop-filter: blur(24px) saturate(180%);
        -webkit-backdrop-filter: blur(24px) saturate(180%);
        border: 1px solid var(--glass-border);
        box-shadow:
            0 8px 32px var(--glass-shadow),
            inset 0 1px 0 rgba(255, 255, 255, 0.95);
    }

    .glass-card {
        background: var(--glass-surface);
        backdrop-filter: blur(28px) saturate(185%);
        -webkit-backdrop-filter: blur(28px) saturate(185%);
        border: 1px solid rgba(255, 255, 255, 0.78);
        box-shadow:
            0 8px 28px rgba(15, 39, 68, 0.06),
            0 1px 2px rgba(15, 39, 68, 0.03),
            inset 0 1px 0 rgba(255, 255, 255, 0.98);
        border-radius: 1.25rem;
    }

    .glass-panel {
        background: rgba(255, 255, 255, 0.58);
        backdrop-filter: blur(22px) saturate(170%);
        -webkit-backdrop-filter: blur(22px) saturate(170%);
        border: 1px solid rgba(255, 255, 255, 0.72);
        box-shadow:
            0 4px 20px rgba(15, 39, 68, 0.045),
            inset 0 1px 0 rgba(255, 255, 255, 0.95);
        border-radius: 1rem;
    }

    .glass-panel-muted {
        background: rgba(241, 245, 249, 0.72);
        backdrop-filter: blur(18px) saturate(150%);
        -webkit-backdrop-filter: blur(18px) saturate(150%);
        border: 1px solid rgba(203, 213, 225, 0.7);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
        border-radius: 1rem;
    }

    .glass-kpi-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }

    .glass-kpi-card:hover {
        transform: translateY(-2px);
        border-color: rgba(255, 255, 255, 0.92);
        box-shadow:
            0 12px 36px rgba(15, 39, 68, 0.09),
            inset 0 1px 0 rgba(255, 255, 255, 1);
    }

    .glass-subtle {
        background: rgba(255, 255, 255, 0.48);
        backdrop-filter: blur(16px) saturate(160%);
        -webkit-backdrop-filter: blur(16px) saturate(160%);
        border: 1px solid rgba(255, 255, 255, 0.62);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
    }

    .glass-sidebar {
        background: linear-gradient(180deg, rgba(10, 22, 40, 0.96) 0%, rgba(15, 39, 68, 0.97) 55%, rgba(20, 45, 72, 0.96) 100%);
        backdrop-filter: blur(32px) saturate(160%);
        -webkit-backdrop-filter: blur(32px) saturate(160%);
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 4px 0 40px rgba(10, 22, 40, 0.22);
    }

    .glass-header {
        background: rgba(255, 255, 255, 0.62);
        backdrop-filter: blur(22px) saturate(180%);
        -webkit-backdrop-filter: blur(22px) saturate(180%);
        border-bottom: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 1px 0 rgba(255, 255, 255, 0.55), 0 4px 22px rgba(15, 39, 68, 0.045);
    }

    .glass-link {
        color: var(--color-brand);
        font-weight: 500;
        transition: color 0.15s ease;
    }

    .glass-link:hover {
        color: var(--color-brand-hover);
    }

    .glass-accent-text {
        color: var(--color-brand);
    }

    .glass-icon-accent {
        color: var(--color-navy-700);
    }

    .glass-hover-tint:hover {
        background: var(--color-brand-soft);
        color: var(--color-brand);
    }

    .glass-badge-brand {
        background: rgba(30, 58, 95, 0.12);
        color: var(--color-navy-800);
    }

    .glass-tabs {
        display: inline-flex;
        border-radius: 0.75rem;
        border: 1px solid rgba(226, 232, 240, 0.8);
        background: rgba(255, 255, 255, 0.55);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        padding: 0.25rem;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }

    .glass-tab {
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: rgb(71, 85, 105);
        transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    }

    .glass-tab:hover {
        background: rgba(255, 255, 255, 0.72);
    }

    .glass-tab.is-active {
        background: linear-gradient(135deg, #1e3a5f 0%, #0f2744 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(15, 39, 68, 0.25);
    }

    .glass-input,
    .liquid-bg input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="image"]):not([type="range"]):not([type="color"]),
    .liquid-bg select,
    .liquid-bg textarea {
        background: rgba(255, 255, 255, 0.84);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(148, 163, 184, 0.55);
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 0.9),
            inset 0 1px 2px rgba(15, 39, 68, 0.04);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .glass-input:focus,
    .liquid-bg input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="image"]):not([type="range"]):not([type="color"]):focus,
    .liquid-bg select:focus,
    .liquid-bg textarea:focus {
        background: rgba(255, 255, 255, 0.98);
        border-color: rgba(30, 58, 95, 0.55);
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 1),
            0 0 0 3px var(--color-brand-ring),
            inset 0 1px 2px rgba(15, 39, 68, 0.03);
        outline: none;
    }

    /* Satu ikon mata sahaja — sembunyikan butang papar kata laluan bawaan pelayar (Edge/Chrome). */
    .auth-password-input::-ms-reveal,
    .auth-password-input::-ms-clear {
        display: none;
    }

    .auth-password-input::-webkit-credentials-auto-fill-button {
        visibility: hidden;
        pointer-events: none;
        position: absolute;
        right: 0;
    }

    .auth-password-input::-webkit-password-reveal-button {
        -webkit-appearance: none;
        appearance: none;
        display: none;
    }

    /* Red = medan wajib kosong selepas user cuba teruskan/hantar. Tiada highlight biru proaktif. */
    .glass-input.glass-input-required-error,
    .liquid-bg input.glass-input-required-error,
    .liquid-bg select.glass-input-required-error,
    .liquid-bg textarea.glass-input-required-error,
    #form-sewaan.form-showing-required-errors .glass-input.glass-input-required-error,
    #form-tindakan.form-showing-required-errors .glass-input.glass-input-required-error {
        border-color: rgba(239, 68, 68, 0.9) !important;
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 0.95),
            0 0 0 3px rgba(239, 68, 68, 0.18),
            inset 0 1px 2px rgba(15, 39, 68, 0.03) !important;
    }

    .proceed-step-checkbox-label.proceed-required-error,
    .proceed-agency-checklist.proceed-required-error {
        border-color: rgba(239, 68, 68, 0.9) !important;
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 0.95),
            0 0 0 3px rgba(239, 68, 68, 0.18) !important;
    }

    .glass-input.glass-input-required-error:focus,
    .liquid-bg input.glass-input-required-error:focus,
    .liquid-bg select.glass-input-required-error:focus,
    .liquid-bg textarea.glass-input-required-error:focus,
    #form-sewaan.form-showing-required-errors .glass-input.glass-input-required-error:focus,
    #form-tindakan.form-showing-required-errors .glass-input.glass-input-required-error:focus {
        border-color: rgba(220, 38, 38, 0.95) !important;
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 1),
            0 0 0 3px rgba(239, 68, 68, 0.28),
            inset 0 1px 2px rgba(15, 39, 68, 0.03) !important;
    }

    .glass-input:disabled,
    .liquid-bg input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):disabled,
    .liquid-bg select:disabled,
    .liquid-bg textarea:disabled,
    .liquid-bg input[readonly]:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]) {
        background: rgba(241, 245, 249, 0.85);
        border-color: rgba(148, 163, 184, 0.4);
        color: rgb(100, 116, 139);
        cursor: not-allowed;
    }

    .glass-field-static {
        background: rgba(248, 250, 252, 0.85);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid rgba(148, 163, 184, 0.45);
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 0.8),
            inset 0 1px 0 rgba(255, 255, 255, 0.95);
        border-radius: 0.75rem;
    }

    .liquid-bg input[type="file"] {
        background: rgba(255, 255, 255, 0.82);
        border: 1px solid rgba(148, 163, 184, 0.55);
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 0.9),
            inset 0 1px 2px rgba(15, 39, 68, 0.04);
        border-radius: 0.75rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }

    .liquid-bg input[type="search"] {
        border-radius: 0.75rem;
    }

    .glass-filter-bar {
        background: rgba(255, 255, 255, 0.55);
        backdrop-filter: blur(20px) saturate(170%);
        -webkit-backdrop-filter: blur(20px) saturate(170%);
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow:
            0 4px 20px rgba(15, 39, 68, 0.04),
            inset 0 1px 0 rgba(255, 255, 255, 0.95);
        border-radius: 1rem;
    }

    .glass-btn-primary {
        background: linear-gradient(135deg, #1e3a5f 0%, #0f2744 100%);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow:
            0 4px 14px rgba(15, 39, 68, 0.28),
            inset 0 1px 0 rgba(255, 255, 255, 0.12);
        color: white;
        transition: transform 0.15s, box-shadow 0.15s, background 0.15s, opacity 0.15s;
    }

    .glass-btn-primary:hover:not(:disabled) {
        background: linear-gradient(135deg, #254770 0%, #153052 100%);
        box-shadow:
            0 6px 20px rgba(15, 39, 68, 0.35),
            inset 0 1px 0 rgba(255, 255, 255, 0.15);
        transform: translateY(-1px);
    }

    .glass-btn-secondary {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(203, 213, 225, 0.9);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 1);
        color: rgb(51, 65, 85);
        transition: background 0.15s, transform 0.15s, border-color 0.15s;
    }

    .glass-btn-secondary:hover:not(:disabled) {
        background: rgba(255, 255, 255, 1);
        border-color: rgba(148, 163, 184, 0.8);
        transform: translateY(-1px);
    }

    .glass-btn-danger {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow: 0 4px 14px rgba(185, 28, 28, 0.25);
        color: white;
        transition: transform 0.15s, box-shadow 0.15s, background 0.15s;
    }

    .glass-btn-danger:hover:not(:disabled) {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        transform: translateY(-1px);
    }

    .glass-btn-success {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow: 0 4px 14px rgba(4, 120, 87, 0.25);
        color: white;
        transition: transform 0.15s, box-shadow 0.15s, background 0.15s;
    }

    .glass-btn-success:hover:not(:disabled) {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        transform: translateY(-1px);
    }

    .glass-divider {
        border-color: rgba(226, 232, 240, 0.85);
    }

    .glass-alert-success {
        background: rgba(236, 253, 245, 0.85);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(167, 243, 208, 0.7);
    }

    .glass-alert-error {
        background: rgba(254, 242, 242, 0.85);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(254, 202, 202, 0.7);
    }

    .glass-alert-warning {
        background: rgba(255, 251, 235, 0.85);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(253, 230, 138, 0.7);
    }

    .glass-progress-track {
        background: rgba(241, 245, 249, 0.9);
        backdrop-filter: blur(4px);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }

    .glass-progress-fill {
        background: linear-gradient(90deg, #1e3a5f, #334155);
        box-shadow: 0 0 10px rgba(30, 58, 95, 0.25);
    }

    .glass-step-active {
        background: linear-gradient(135deg, #1e3a5f, #0f2744);
        border: 1px solid rgba(148, 163, 184, 0.35);
        box-shadow: 0 4px 14px rgba(15, 39, 68, 0.32), inset 0 1px 0 rgba(255, 255, 255, 0.18);
        color: white;
    }

    .glass-step-done {
        background: linear-gradient(135deg, #10b981, #059669);
        border: 1px solid rgba(167, 243, 208, 0.65);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.28);
        color: white;
    }

    .glass-step-pending {
        background: rgba(255, 255, 255, 0.75);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(251, 191, 36, 0.5);
        box-shadow: 0 2px 8px rgba(251, 191, 36, 0.15);
        color: rgb(180, 83, 9);
    }

    .glass-step-card-active {
        background: rgba(241, 245, 249, 0.92);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(148, 163, 184, 0.55);
        box-shadow: 0 4px 20px rgba(15, 39, 68, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.95);
    }

    .glass-step-card-pending {
        background: rgba(255, 251, 235, 0.65);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(251, 191, 36, 0.3);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .glass-step-card-done {
        background: rgba(236, 253, 245, 0.88);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(167, 243, 208, 0.75);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.95);
        transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .glass-step-card-done:hover,
    .group:hover .glass-step-card-done {
        background: rgba(209, 250, 229, 0.95);
        border-color: rgba(110, 231, 183, 0.85);
        box-shadow: 0 4px 16px rgba(16, 185, 129, 0.12), inset 0 1px 0 rgba(255, 255, 255, 1);
    }

    .glass-table {
        background: rgba(255, 255, 255, 0.58);
        backdrop-filter: blur(24px) saturate(180%);
        -webkit-backdrop-filter: blur(24px) saturate(180%);
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 4px 24px rgba(15, 39, 68, 0.05);
    }

    .glass-table thead {
        background: rgba(248, 250, 252, 0.65);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    /*
     * Glass tables & list rows — anti-flicker standard.
     * Nested blur + animated semi-transparent row hovers repaint badly in Chrome/Edge.
     */
    .glass-card.glass-table {
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }

    .glass-card.glass-table thead {
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        background: rgba(248, 250, 252, 0.98);
    }

    .glass-card.glass-table tbody,
    .glass-card.glass-table > .glass-row-hover,
    .glass-card > .divide-y {
        background: rgba(255, 255, 255, 0.96);
    }

    .glass-card.glass-table tbody tr,
    .glass-card.glass-table > .glass-row-hover,
    .glass-card .glass-row-hover,
    .glass-row-neutral,
    .glass-row-pending,
    .glass-row-ready-hq,
    .glass-row-hq-review,
    .glass-row-withdrawal-pending,
    .glass-row-hover {
        transition: none;
    }

    .glass-divider-soft {
        border-color: rgba(226, 232, 240, 0.55);
    }

    .glass-row-neutral {
        background: transparent;
    }

    .glass-row-neutral:hover {
        background: rgba(255, 255, 255, 0.55);
    }

    .glass-row-pending {
        background: rgba(255, 251, 235, 0.72);
    }

    .glass-row-pending:hover {
        background: rgba(254, 243, 199, 0.92);
    }

    .glass-row-ready-hq {
        background: rgba(236, 253, 245, 0.72);
    }

    .glass-row-ready-hq:hover {
        background: rgba(209, 250, 229, 0.95);
    }

    .glass-row-hq-review {
        background: rgba(241, 245, 249, 0.82);
    }

    .glass-row-hq-review:hover {
        background: rgba(226, 232, 240, 0.96);
    }

    .glass-row-withdrawal-pending {
        background: rgba(255, 251, 235, 0.78);
    }

    .glass-row-withdrawal-pending:hover {
        background: rgba(254, 243, 199, 0.96);
    }

    .glass-row-unseen {
        box-shadow: inset 4px 0 0 #f59e0b;
    }

    .glass-row-unseen td:first-child {
        position: relative;
    }

    .glass-row-hover:hover {
        background: rgba(255, 255, 255, 0.45);
    }

    .glass-modal {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(28px) saturate(180%);
        -webkit-backdrop-filter: blur(28px) saturate(180%);
        border: 1px solid rgba(226, 232, 240, 0.95);
        box-shadow: 0 24px 64px rgba(15, 39, 68, 0.15);
    }

    .glass-overlay {
        background: rgba(10, 22, 40, 0.45);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }

    /* Sidebar navigation */
    .sidebar-nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border-radius: 0.75rem;
        padding: 0.625rem 0.75rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: rgba(255, 255, 255, 0.65);
        border: 1px solid transparent;
        transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .sidebar-nav-link:hover {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
    }

    .sidebar-nav-link.is-active {
        background: rgba(255, 255, 255, 0.14);
        border-color: rgba(255, 255, 255, 0.12);
        color: #ffffff;
        font-weight: 500;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
    }

    .sidebar-appearance-btn {
        display: inline-flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
        color: rgba(255, 255, 255, 0.65);
        border: 1px solid transparent;
        transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .sidebar-appearance-btn:hover {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
    }

    .sidebar-appearance-btn.is-active {
        background: rgba(255, 255, 255, 0.14);
        border-color: rgba(255, 255, 255, 0.12);
        color: #ffffff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
    }

    .sidebar-nav-sublink {
        display: block;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.6);
        border: 1px solid transparent;
        transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    }

    .sidebar-nav-sublink:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    .sidebar-nav-sublink.is-active {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        font-weight: 500;
    }

    .sidebar-nav-summary {
        display: flex;
        cursor: pointer;
        list-style: none;
        align-items: center;
        gap: 0.75rem;
        border-radius: 0.5rem;
        padding: 0.625rem 0.75rem;
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.6);
        border: 1px solid transparent;
        transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    }

    .sidebar-nav-summary:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    .sidebar-nav-summary.is-active {
        background: rgba(255, 255, 255, 0.14);
        border-color: rgba(255, 255, 255, 0.12);
        color: #ffffff;
        font-weight: 500;
    }

    .glass-nav-active {
        background: rgba(255, 255, 255, 0.14);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
    }

    .glass-nav-item {
        border: 1px solid transparent;
        transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .glass-nav-item:hover {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.1);
        color: #ffffff !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
    }

    .sidebar-logout-btn {
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.05);
        transition: background 0.2s ease, border-color 0.2s ease;
    }

    .sidebar-logout-btn:hover {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.2);
    }

    .sidebar-profile-footer {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .sidebar-profile-card {
        display: flex;
        width: 100%;
        flex-direction: column;
        align-items: center;
        border-radius: 0.75rem;
        padding: 0.5rem 0.75rem;
        color: rgba(255, 255, 255, 0.65);
        border: 1px solid transparent;
        transition: background 320ms cubic-bezier(0.22, 1, 0.36, 1), color 320ms ease, border-color 320ms ease;
    }

    .sidebar-profile-card:hover {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }

    .sidebar-profile-card.is-active {
        background: rgba(255, 255, 255, 0.14);
        border-color: rgba(255, 255, 255, 0.12);
        color: #ffffff;
    }

    .sidebar-profile-card .sidebar-profile-email {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sidebar-profile-footer form {
        width: 100%;
        padding-top: 0.5rem;
    }

    .glass-progress-fill-amber {
        background: linear-gradient(90deg, rgba(251, 191, 36, 0.85), rgba(245, 158, 11, 0.9));
        box-shadow: 0 0 10px rgba(251, 191, 36, 0.25);
    }

    .glass-progress-fill-emerald {
        background: linear-gradient(90deg, #10b981, #059669);
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.35);
    }

    .glass-progress-fill-indigo {
        background: linear-gradient(90deg, #6366f1, #4f46e5);
        box-shadow: 0 0 10px rgba(99, 102, 241, 0.35);
    }

    .glass-status-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 0.5rem;
        padding: 0.25rem 0.625rem;
        font-size: 0.75rem;
        line-height: 1.25rem;
        font-weight: 500;
        color: rgb(51, 65, 85);
        background: rgba(248, 250, 252, 0.9);
        border: 1px solid rgba(148, 163, 184, 0.35);
    }

    .glass-status-badge--ready {
        color: #047857;
        background: rgba(236, 253, 245, 0.85);
        border-color: rgba(167, 243, 208, 0.8);
    }

    .glass-status-badge--pending {
        color: #b45309;
        background: rgba(255, 251, 235, 0.9);
        border-color: rgba(253, 230, 138, 0.85);
    }

    .glass-status-badge--hq-review {
        color: #4338ca;
        background: rgba(238, 242, 255, 0.92);
        border-color: rgba(199, 210, 254, 0.9);
    }

    .glass-status-badge--withdrawal {
        color: #b45309;
        background: rgba(255, 251, 235, 0.95);
        border-color: rgba(253, 230, 138, 0.9);
    }

    .glass-status-badge--withdrawal-rejected {
        color: #b91c1c;
        background: rgba(254, 242, 242, 0.95);
        border-color: rgba(254, 202, 202, 0.9);
    }
</style>
