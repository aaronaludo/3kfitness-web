<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use App\Models\Attendance2;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Language;

class AttendanceHistoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search'        => 'nullable|string|max:255',
            'role_id'       => 'nullable|exists:roles,id',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'status'        => 'nullable|in:all,open,completed',
            'show_archived' => 'nullable|boolean',
        ]);

        $filters = [
            'search'        => trim((string) $request->input('search', '')),
            'role_id'       => $request->input('role_id'),
            'start_date'    => $request->input('start_date'),
            'end_date'      => $request->input('end_date'),
            'status'        => $request->input('status', 'completed'),
            'show_archived' => $request->boolean('show_archived'),
        ];

        $validStatuses = ['all', 'open', 'completed'];
        if (!in_array($filters['status'], $validStatuses, true)) {
            $filters['status'] = 'completed';
        }

        $baseQuery = Attendance2::with(['user.role'])
            ->where('is_archive', $filters['show_archived'] ? 1 : 0);

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $baseQuery->where(function ($query) use ($like) {
                $query->whereHas('user', function ($userQuery) use ($like) {
                    $userQuery->where(function ($nameQuery) use ($like) {
                        $nameQuery->whereRaw(
                            "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                            [$like]
                        )->orWhere('first_name', 'like', $like)
                         ->orWhere('last_name', 'like', $like)
                         ->orWhere('email', 'like', $like)
                         ->orWhere('phone_number', 'like', $like);
                    });
                });
            });
        }

        if ($filters['role_id']) {
            $baseQuery->whereHas('user', function ($query) use ($filters) {
                $query->where('role_id', $filters['role_id']);
            });
        }

        if ($filters['start_date']) {
            $baseQuery->whereDate('clockin_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $baseQuery->whereDate('clockin_at', '<=', $filters['end_date']);
        }

        $statusTallies = [
            'all'       => (clone $baseQuery)->count(),
            'open'      => (clone $baseQuery)->whereNull('clockout_at')->count(),
            'completed' => (clone $baseQuery)->whereNotNull('clockout_at')->count(),
        ];

        $historyQuery = clone $baseQuery;
        if ($filters['status'] === 'open') {
            $historyQuery->whereNull('clockout_at');
        } elseif ($filters['status'] === 'completed') {
            $historyQuery->whereNotNull('clockout_at');
        }

        $queryParams = $request->query();

        $attendances = (clone $historyQuery)
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($queryParams);

        $statsBase = clone $historyQuery;
        $stats = [
            'records'   => (clone $statsBase)->count(),
            'people'    => (clone $statsBase)->distinct('user_id')->count('user_id'),
            'completed' => (clone $statsBase)->whereNotNull('clockout_at')->count(),
        ];

        $roleOptions = Role::orderBy('name')->get(['id', 'name']);

        $printAllAttendances = (clone $historyQuery)
            ->orderByDesc('id')
            ->get();

        return view('admin.history.attendances', [
            'attendances'  => $attendances,
            'filters'      => $filters,
            'statusTallies'=> $statusTallies,
            'roleOptions'  => $roleOptions,
            'stats'        => $stats,
            'printAllAttendances' => $printAllAttendances,
        ]);
    }

    public function print(Request $request)
    {
        $request->validate([
            'search'        => 'nullable|string|max:255',
            'role_id'       => 'nullable|exists:roles,id',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'status'        => 'nullable|in:all,open,completed',
            'show_archived' => 'nullable|boolean',
        ]);

        $filters = [
            'search'        => trim((string) $request->input('search', '')),
            'role_id'       => $request->input('role_id'),
            'start_date'    => $request->input('start_date'),
            'end_date'      => $request->input('end_date'),
            'status'        => $request->input('status', 'completed'),
            'show_archived' => $request->boolean('show_archived'),
        ];

        $validStatuses = ['all', 'open', 'completed'];
        if (!in_array($filters['status'], $validStatuses, true)) {
            $filters['status'] = 'completed';
        }

        $query = Attendance2::with(['user.role'])
            ->where('is_archive', $filters['show_archived'] ? 1 : 0);

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($builder) use ($like) {
                $builder->whereHas('user', function ($userQuery) use ($like) {
                    $userQuery->where(function ($nameQuery) use ($like) {
                        $nameQuery->whereRaw(
                            "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                            [$like]
                        )->orWhere('first_name', 'like', $like)
                         ->orWhere('last_name', 'like', $like)
                         ->orWhere('email', 'like', $like)
                         ->orWhere('phone_number', 'like', $like);
                    });
                });
            });
        }

        if ($filters['role_id']) {
            $query->whereHas('user', function ($builder) use ($filters) {
                $builder->where('role_id', $filters['role_id']);
            });
        }

        if ($filters['start_date']) {
            $query->whereDate('clockin_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $query->whereDate('clockin_at', '<=', $filters['end_date']);
        }

        if ($filters['status'] === 'open') {
            $query->whereNull('clockout_at');
        } elseif ($filters['status'] === 'completed') {
            $query->whereNotNull('clockout_at');
        }

        $records = $query->orderByDesc('id')->get();

        $phpWord = new PhpWord();
        $phpWord->getSettings()->setThemeFontLang(new Language(Language::EN_US));
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginLeft'   => 800,
            'marginRight'  => 800,
            'marginTop'    => 800,
            'marginBottom' => 800,
        ]);

        $rangeLabel = '';
        if ($filters['start_date'] && $filters['end_date']) {
            $rangeLabel = ' | ' . Carbon::parse($filters['start_date'])->format('M d, Y') .
                ' - ' . Carbon::parse($filters['end_date'])->format('M d, Y');
        } elseif ($filters['start_date']) {
            $rangeLabel = ' | From ' . Carbon::parse($filters['start_date'])->format('M d, Y');
        } elseif ($filters['end_date']) {
            $rangeLabel = ' | Until ' . Carbon::parse($filters['end_date'])->format('M d, Y');
        }

        $title = 'Attendance History';
        if ($filters['status'] !== 'all') {
            $title .= ' — ' . ucfirst($filters['status']);
        }
        $section->addText($title . $rangeLabel, ['bold' => true, 'size' => 16]);
        $section->addText('Generated: ' . now()->format('M d, Y H:i'));

        $filterSummary = [];
        if ($filters['search'] !== '') {
            $filterSummary[] = "Search='{$filters['search']}'";
        }
        if ($filters['role_id']) {
            $roleName = Role::find($filters['role_id']);
            $filterSummary[] = 'Role=' . ($roleName ? $roleName->name : $filters['role_id']);
        }
        if ($filters['status'] !== 'all') {
            $filterSummary[] = 'Status=' . ucfirst($filters['status']);
        }
        $filterSummary[] = 'Showing ' . ($filters['show_archived'] ? 'archived' : 'active') . ' records';

        if (!empty($filterSummary)) {
            $section->addText('Filters: ' . implode('; ', $filterSummary));
        }
        $section->addTextBreak(1);

        $tableStyle = [
            'borderColor' => '777777',
            'borderSize'  => 6,
            'cellMargin'  => 80,
        ];
        $firstRowStyle = ['bgColor' => 'DDDDDD'];
        $phpWord->addTableStyle('AttendanceHistoryTable', $tableStyle, $firstRowStyle);
        $table = $section->addTable('AttendanceHistoryTable');

        $headers = [
            'ID',
            'Name',
            'Role',
            'Email',
            'Phone',
            'Clock-in',
            'Clock-out',
            'Duration',
            'Status',
            'Archive',
        ];
        $headerRow = $table->addRow();
        foreach ($headers as $header) {
            $headerRow->addCell()->addText($header, ['bold' => true]);
        }

        foreach ($records as $record) {
            $person = $record->user;
            $clockIn = $record->clockin_at ? Carbon::parse($record->clockin_at) : null;
            $clockOut = $record->clockout_at ? Carbon::parse($record->clockout_at) : null;
            $durationMinutes = ($clockIn && $clockOut) ? $clockIn->diffInMinutes($clockOut) : null;
            $durationText = $durationMinutes !== null
                ? sprintf('%dh %02dm', intdiv($durationMinutes, 60), $durationMinutes % 60)
                : '—';

            $row = $table->addRow();
            $cells = [
                $record->id,
                $person ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) : 'Unknown',
                $person && $person->role ? ($person->role->name ?? 'Unknown') : 'Unknown',
                $person ? ($person->email ?? '—') : '—',
                $person ? ($person->phone_number ?? '—') : '—',
                $clockIn ? $clockIn->format('Y-m-d H:i') : '—',
                $clockOut ? $clockOut->format('Y-m-d H:i') : '—',
                $durationText,
                $record->clockout_at ? 'Completed' : 'Open',
                (int) ($record->is_archive ?? 0) === 1 ? 'Archived' : 'Active',
            ];

            foreach ($cells as $value) {
                $row->addCell()->addText((string) $value);
            }
        }

        $tempPath = storage_path('app/temp_exports');
        if (!is_dir($tempPath)) {
            @mkdir($tempPath, 0775, true);
        }

        $fileName = 'attendance_history_' . now()->format('Y-m-d_H-i') . '.docx';
        $fullPath = $tempPath . DIRECTORY_SEPARATOR . $fileName;
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($fullPath);

        return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
    }
}
