<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function preview(Request $request)
    {
        $request->validate([
            'payload' => 'required|string',
        ]);

        $payload = json_decode($request->input('payload'), true);
        if (!is_array($payload)) {
            abort(422, 'Invalid print payload.');
        }

        $table = $this->normalizeTable($payload['table'] ?? []);
        $filters = $this->normalizeFilters($payload['filters'] ?? []);

        $title = $payload['title'] ?? 'Print preview';
        $generatedAt = $payload['generated_at'] ?? now()->format('M d, Y g:i A');
        $count = array_key_exists('count', $payload) ? (int) $payload['count'] : count($table['rows']);

        return view('admin.print.preview', [
            'title' => $title,
            'generatedAt' => $generatedAt,
            'count' => $count,
            'filters' => $filters,
            'headers' => $table['headers'],
            'rows' => $table['rows'],
            'rowsHtml' => $table['rows_html'],
            'meta' => $payload['meta'] ?? [],
            'notes' => $payload['notes'] ?? null,
        ]);
    }

    protected function normalizeFilters($filters): array
    {
        return collect($filters)
            ->map(function ($value, $key) {
                if (is_array($value)) {
                    $label = $value['label'] ?? (is_string($key) ? $key : null);
                    $display = $value['value'] ?? ($value['text'] ?? null);

                    if ($display === null || $display === '') {
                        return null;
                    }

                    return [
                        'label' => $label,
                        'value' => (string) $display,
                    ];
                }

                if (is_string($value) || is_numeric($value)) {
                    return [
                        'label' => is_string($key) ? $key : null,
                        'value' => (string) $value,
                    ];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeTable($table): array
    {
        if (!is_array($table)) {
            $table = [];
        }

        $headers = [];
        if (isset($table['headers']) && is_array($table['headers'])) {
            $headers = array_values(array_map('strval', $table['headers']));
        }

        $rows = [];
        $rowsHtml = '';
        if (isset($table['rows']) && is_array($table['rows'])) {
            foreach ($table['rows'] as $row) {
                if (is_array($row)) {
                    $rows[] = array_map(function ($value) {
                        if (is_bool($value)) {
                            return $value ? 'Yes' : 'No';
                        }

                        return is_scalar($value) ? (string) $value : '';
                    }, array_values($row));
                } elseif (is_string($row) || is_numeric($row)) {
                    $rows[] = [(string) $row];
                }
            }
        }

        if (isset($table['rows_html']) && is_string($table['rows_html'])) {
            $rowsHtml = $table['rows_html'];
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'rows_html' => $rowsHtml,
        ];
    }
}
