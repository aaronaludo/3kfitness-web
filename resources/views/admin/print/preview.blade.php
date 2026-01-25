<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Print preview' }}</title>
    <style>
        :root { color-scheme: light; }
        body { font-family: "Inter", "Segoe UI", Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 24px; color: #111827; }
        .sheet { max-width: 1100px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px 28px; box-shadow: 0 18px 45px rgba(17, 24, 39, 0.08); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
        .title { margin: 0; font-size: 22px; line-height: 1.3; }
        .meta { color: #6b7280; font-size: 12px; margin: 2px 0; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin: 16px 0 8px; }
        .summary-card {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px 18px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 24px rgba(17, 24, 39, 0.08);
        }
        .summary-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #94a3b8; }
        .summary-card[data-tone="revenue"]::before { background: #16a34a; }
        .summary-card[data-tone="commission"]::before { background: #e11d48; }
        .summary-card[data-tone="count"]::before { background: #2563eb; }
        .summary-card[data-tone="cost"]::before { background: #f59e0b; }
        .summary-card[data-tone="profit"]::before { background: #0f766e; }
        .summary-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; }
        .summary-value { font-size: 20px; font-weight: 700; margin-top: 6px; color: #111827; }
        .pill-row { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0; padding: 0; list-style: none; }
        .pill {
            --pill-bg: #f5f7fb;
            --pill-border: #d5deec;
            --pill-text: #111827;
            --pill-dot: #9ca3af;
            background: var(--pill-bg);
            border: 1px solid var(--pill-border);
            border-radius: 10px;
            padding: 6px 10px;
            font-size: 12px;
            display: inline-flex;
            gap: 6px;
            align-items: center;
            color: var(--pill-text);
            letter-spacing: 0.01em;
            text-decoration: none;
        }
        .pill--date { font-weight: 700; }
        .pill--date .pill-label { color: #111827; font-weight: 700; }
        .pill--date .pill-value { font-weight: 700; }
        .pill::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--pill-dot);
            opacity: 0.9;
        }
        .pill-label { color: #6b7280; }
        .status-pill {
            --status-bg: #f1f3f7;
            --status-border: #d5deec;
            --status-text: #4b5563;
            --status-dot: #9ca3af;
            display: inline-flex;
            align-items: center;
            justify-content: flex-start;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid var(--status-border);
            background: var(--status-bg);
            color: var(--status-text);
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            line-height: 1.2;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            white-space: normal;
            flex-wrap: wrap;
            text-align: left;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .status-pill-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--status-dot);
            opacity: 0.9;
            flex: 0 0 auto;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .status-pill--warning {
            --status-bg: #fff4e5;
            --status-border: #f3d7a6;
            --status-text: #7a4b00;
            --status-dot: #e0a100;
        }
        .status-pill--info {
            --status-bg: #ecf2ff;
            --status-border: #d5def7;
            --status-text: #123a6d;
            --status-dot: #3b82f6;
        }
        .status-pill--success {
            --status-bg: #e8f6ef;
            --status-border: #c5e5d5;
            --status-text: #1f5133;
            --status-dot: #2e8b57;
        }
        .status-pill--muted {
            --status-bg: #f1f3f7;
            --status-border: #d5deec;
            --status-text: #4b5563;
            --status-dot: #9ca3af;
        }
        .member-stats {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .member-pill {
            --pill-bg: #f1f5f9;
            --pill-border: #d5deec;
            --pill-text: #374151;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid var(--pill-border);
            background: var(--pill-bg);
            color: var(--pill-text);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.01em;
            white-space: nowrap;
            width: fit-content;
            max-width: 100%;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .member-pill-label {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.06em;
            color: inherit;
        }
        .member-pill--success {
            --pill-bg: #e8f6ef;
            --pill-border: #c5e5d5;
            --pill-text: #1f5133;
        }
        .member-pill--warning {
            --pill-bg: #fff4e5;
            --pill-border: #f3d7a6;
            --pill-text: #7a4b00;
        }
        .member-pill--danger {
            --pill-bg: #fbecec;
            --pill-border: #f0c4c2;
            --pill-text: #7b1c1c;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 13px; }
        th, td { border: 1px solid #e5e7eb; padding: 10px; vertical-align: top; }
        th { background: #f9fafb; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
        .fw { font-weight: 700; }
        .muted { color: #6b7280; font-size: 12px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .badge-soft-info { background: #e0f2fe; color: #075985; }
        .badge-soft-success { background: #dcfce7; color: #166534; }
        .badge-soft-warning { background: #fef3c7; color: #92400e; }
        .badge-soft-danger { background: #fee2e2; color: #b91c1c; }
        .badge-soft-secondary { background: #e5e7eb; color: #374151; }
        .badge-soft-muted { background: #f3f4f6; color: #6b7280; }
        .empty { text-align: center; padding: 18px; color: #6b7280; }
        @media print {
            body { background: #fff; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .sheet { box-shadow: none; border-radius: 0; border: none; }
            .summary-card { box-shadow: none; }
        }
    </style>
</head>
<body>
    @php
        $columnCount = max(count($headers ?? []), 1);
        $rowsHtml = $rowsHtml ?? '';
    @endphp
    <div class="sheet">
        <div class="header">
            <div>
                <h1 class="title">{{ $title ?? 'Print preview' }}</h1>
                <div class="meta">Generated {{ $generatedAt ?? '' }}</div>
                @if(!empty($meta['generated_by']))
                    <div class="meta">Generated by {{ $meta['generated_by'] }}</div>
                @endif
                @if(empty($meta['hide_table']))
                    <div class="meta">Showing {{ $count ?? 0 }} record(s)</div>
                @endif
            </div>
            @if(!empty($meta['subtitle']))
                <div class="muted">{{ $meta['subtitle'] }}</div>
            @endif
        </div>

        @if(!empty($meta['summary_cards']))
            <div class="summary-grid">
                @foreach($meta['summary_cards'] as $card)
                    <div class="summary-card" data-tone="{{ $card['tone'] ?? '' }}">
                        <div class="summary-label">{{ $card['label'] ?? '' }}</div>
                        <div class="summary-value">{{ $card['value'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="pill-row">
            @forelse($filters ?? [] as $filter)
                @php
                    $filterLabel = $filter['label'] ?? '';
                    $normalizedLabel = is_string($filterLabel) ? strtolower($filterLabel) : '';
                    $isEmphasized = in_array($normalizedLabel, ['date', 'period'], true);
                @endphp
                <span class="pill{{ $isEmphasized ? ' pill--date' : '' }}">
                    @if(!empty($filter['label']))
                        <span class="pill-label">{{ $filter['label'] }}:</span>
                    @endif
                    <span class="pill-value">{!! $filter['value'] !!}</span>
                </span>
            @empty
                <span class="muted">No filters applied</span>
            @endforelse
        </div>

        @if(empty($meta['hide_table']))
            <table>
                @if(!empty($headers))
                    <thead>
                        <tr>
                            @foreach($headers as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                @endif
                <tbody>
                    @if(!empty($rowsHtml))
                        {!! $rowsHtml !!}
                    @else
                        @forelse($rows ?? [] as $row)
                            <tr>
                                @for($i = 0; $i < $columnCount; $i++)
                                    <td>{!! $row[$i] ?? '&mdash;' !!}</td>
                                @endfor
                            </tr>
                        @empty
                            <tr>
                                <td class="empty" colspan="{{ $columnCount }}">No records to print for this view.</td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        @endif

        @if(!empty($notes))
            <div class="muted" style="margin-top: 12px;">{!! $notes !!}</div>
        @endif
    </div>
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
