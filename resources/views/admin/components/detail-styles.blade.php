<style>
    .detail-hero {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        padding: 28px;
        color: #fff;
        background: linear-gradient(120deg, #ff6b6b 0%, #ff9472 45%, #ffd166 100%);
        box-shadow: 0 20px 45px rgba(255, 107, 107, 0.28);
    }

    .detail-hero::after {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(120% 80% at 80% 10%, rgba(255, 255, 255, 0.22), transparent 60%);
        pointer-events: none;
        mix-blend-mode: screen;
    }

    .detail-hero .hero-label {
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.75rem;
        opacity: 0.9;
    }

    .detail-hero .hero-title {
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .detail-hero .hero-subtitle {
        opacity: 0.9;
        font-size: 0.95rem;
    }

    .detail-avatar {
        width: 96px;
        height: 96px;
        object-fit: cover;
        border-radius: 18px;
        border: 3px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.25);
        background: #fff;
    }

    .detail-avatar.sm {
        width: 72px;
        height: 72px;
        border-radius: 14px;
    }

    .detail-card {
        background: #fff;
        border: 1px solid #f1f3f5;
        border-radius: 18px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        padding: 24px;
    }

    .detail-card h5,
    .detail-card h6 {
        font-weight: 700;
        letter-spacing: -0.01em;
    }

    .detail-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 10px;
        background: #fff5f5;
        color: #9f1239;
        border: 1px solid #f8c7d6;
        font-weight: 700;
        box-shadow: none;
        letter-spacing: 0.01em;
    }

    .detail-chip .icon {
        display: inline-flex;
        width: 28px;
        height: 28px;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.85);
        color: #b91c1c;
        box-shadow: none;
    }

    .detail-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 11px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.16);
        color: #fff;
        font-weight: 700;
        letter-spacing: 0.01em;
        border: 1px solid rgba(255, 255, 255, 0.45);
        box-shadow: none;
        backdrop-filter: blur(5px);
        position: relative;
    }

    .detail-pill::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
    }

    .detail-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .detail-table th {
        width: 35%;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.75rem;
        color: #6b7280;
        border: none;
        padding: 10px 14px;
        background: #f9fafb;
        border-radius: 10px 0 0 10px;
    }

    .detail-table td {
        border: none;
        padding: 10px 14px;
        background: #fff;
        border-radius: 0 10px 10px 0;
        box-shadow: inset 0 0 0 1px #f1f3f5;
        font-weight: 600;
        color: #111827;
    }

    .detail-meta {
        font-size: 0.9rem;
        color: #6b7280;
    }

    .detail-divider {
        border-top: 1px dashed #e5e7eb;
        margin: 18px 0;
    }

    .detail-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 10px;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    .detail-badge.success {
        background: #ecfdf3;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .detail-badge.warning {
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
    }

    .detail-badge.neutral {
        background: #f4f4f5;
        color: #44403c;
        border: 1px solid #e4e4e7;
    }

    .detail-badge.danger {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecdd3;
    }

    .detail-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 14px;
        margin: 12px 0 20px;
    }

    .detail-stat {
        position: relative;
        background: #fff;
        border: 1px solid #f1f3f5;
        border-radius: 14px;
        padding: 14px 16px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
    }

    .detail-stat .label {
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 6px;
        display: block;
    }

    .detail-stat .value {
        font-weight: 800;
        color: #0f172a;
        font-size: 1.1rem;
        letter-spacing: -0.01em;
    }

    .detail-stat .hint {
        margin-top: 4px;
        color: #6b7280;
        font-size: 0.9rem;
    }

    .pill-soft {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        color: #111827;
        font-weight: 600;
    }

    .pill-soft .icon {
        width: 28px;
        height: 28px;
        border-radius: 10px;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #dc2626;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
    }

    .stat-progress {
        height: 8px;
        background: #f3f4f6;
        border-radius: 999px;
        overflow: hidden;
        margin-top: 10px;
    }

    .stat-progress .bar {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(120deg, #dc2626, #f97316);
    }

    @media (max-width: 767px) {
        .detail-hero {
            padding: 20px;
        }

        .detail-avatar {
            width: 80px;
            height: 80px;
        }

        .detail-table th,
        .detail-table td {
            display: block;
            width: 100%;
            border-radius: 10px;
        }

        .detail-table tr {
            display: block;
        }
    }
</style>
