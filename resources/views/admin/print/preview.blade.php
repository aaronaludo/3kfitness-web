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
        .pill-row { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0; padding: 0; list-style: none; }
        .pill { background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 999px; padding: 6px 12px; font-size: 12px; display: inline-flex; gap: 6px; align-items: center; color: #111827; }
        .pill-label { color: #6b7280; }
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
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; border-radius: 0; border: none; }
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
                <div class="meta">Showing {{ $count ?? 0 }} record(s)</div>
            </div>
            @if(!empty($meta['subtitle']))
                <div class="muted">{{ $meta['subtitle'] }}</div>
            @endif
        </div>

        <div class="pill-row">
            @forelse($filters ?? [] as $filter)
                <span class="pill">
                    @if(!empty($filter['label']))
                        <span class="pill-label">{{ $filter['label'] }}:</span>
                    @endif
                    <span class="pill-value">{!! $filter['value'] !!}</span>
                </span>
            @empty
                <span class="muted">No filters applied</span>
            @endforelse
        </div>

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
