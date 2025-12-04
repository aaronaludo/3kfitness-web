<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Language;

class TrainerClassHistoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search'     => 'nullable|string|max:255',
            'trainer_id' => 'nullable|exists:users,id',
            'status'     => 'nullable|in:all,pending,approved,rejected',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $filters = [
            'search'     => trim((string) $request->input('search', '')),
            'trainer_id' => $request->input('trainer_id'),
            'status'     => $request->input('status', 'all'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ];

        $statusMap = [
            'pending'  => 0,
            'approved' => 1,
            'rejected' => 2,
        ];

        if (!array_key_exists($filters['status'], $statusMap)) {
            $filters['status'] = 'all';
        }

        $now = Carbon::now();

        $baseQuery = Schedule::with(['user', 'user_schedules'])
            ->withCount('user_schedules')
            ->whereNotNull('class_end_date')
            ->where('class_end_date', '<', $now);

        if ($filters['trainer_id']) {
            $baseQuery->where('trainer_id', $filters['trainer_id']);
        }

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';

            $baseQuery->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('class_code', 'like', $like)
                    ->orWhereHas('user', function ($trainerQuery) use ($like) {
                        $trainerQuery->where(function ($nameQuery) use ($like) {
                            $nameQuery->whereRaw(
                                "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                                [$like]
                            )->orWhere('first_name', 'like', $like)
                             ->orWhere('last_name', 'like', $like);
                        });
                    });
            });
        }

        if ($filters['start_date']) {
            $baseQuery->whereDate('class_end_date', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $baseQuery->whereDate('class_end_date', '<=', $filters['end_date']);
        }

        $statusTallies = [
            'all'      => (clone $baseQuery)->count(),
            'pending'  => (clone $baseQuery)->where('isadminapproved', $statusMap['pending'])->count(),
            'approved' => (clone $baseQuery)->where('isadminapproved', $statusMap['approved'])->count(),
            'rejected' => (clone $baseQuery)->where('isadminapproved', $statusMap['rejected'])->count(),
        ];

        $historyQuery = clone $baseQuery;
        if ($filters['status'] !== 'all') {
            $historyQuery->where('isadminapproved', $statusMap[$filters['status']]);
        }

        $queryParams = $request->query();

        $classes = (clone $historyQuery)
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($queryParams);

        $statsBase = clone $historyQuery;
        $stats = [
            'classes'     => (clone $statsBase)->count(),
            'trainers'    => (clone $statsBase)->where('trainer_id', '>', 0)->distinct('trainer_id')->count('trainer_id'),
            'enrollments' => (clone $statsBase)->get()->sum('user_schedules_count'),
        ];

        $trainerOptions = User::where('role_id', 5)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        $printAllClasses = (clone $historyQuery)
            ->orderByDesc('id')
            ->get();

        return view('admin.history.trainer-classes', [
            'classes'        => $classes,
            'filters'        => $filters,
            'statusTallies'  => $statusTallies,
            'trainerOptions' => $trainerOptions,
            'stats'          => $stats,
            'printAllClasses' => $printAllClasses,
        ]);
    }

    public function print(Request $request)
    {
        $request->validate([
            'search'     => 'nullable|string|max:255',
            'trainer_id' => 'nullable|exists:users,id',
            'status'     => 'nullable|in:all,pending,approved,rejected',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $filters = [
            'search'     => trim((string) $request->input('search', '')),
            'trainer_id' => $request->input('trainer_id'),
            'status'     => $request->input('status', 'all'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ];

        $statusMap = [
            'pending'  => 0,
            'approved' => 1,
            'rejected' => 2,
        ];

        if (!array_key_exists($filters['status'], $statusMap)) {
            $filters['status'] = 'all';
        }

        $now = Carbon::now();

        $query = Schedule::with(['user', 'user_schedules'])
            ->withCount('user_schedules')
            ->whereNotNull('class_end_date')
            ->where('class_end_date', '<', $now);

        if ($filters['trainer_id']) {
            $query->where('trainer_id', $filters['trainer_id']);
        }

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';

            $query->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)
                    ->orWhere('class_code', 'like', $like)
                    ->orWhereHas('user', function ($trainerQuery) use ($like) {
                        $trainerQuery->where(function ($nameQuery) use ($like) {
                            $nameQuery->whereRaw(
                                "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                                [$like]
                            )->orWhere('first_name', 'like', $like)
                             ->orWhere('last_name', 'like', $like);
                        });
                    });
            });
        }

        if ($filters['start_date']) {
            $query->whereDate('class_end_date', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $query->whereDate('class_end_date', '<=', $filters['end_date']);
        }

        if ($filters['status'] !== 'all') {
            $query->where('isadminapproved', $statusMap[$filters['status']]);
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

        $title = 'Trainer Class History';
        if ($filters['status'] !== 'all') {
            $title .= ' — ' . ucfirst($filters['status']);
        }

        $section->addText($title . $rangeLabel, ['bold' => true, 'size' => 16]);
        $section->addText('Generated: ' . now()->format('M d, Y H:i'));

        $filterSummary = [];
        if ($filters['search'] !== '') {
            $filterSummary[] = "Search='{$filters['search']}'";
        }
        if ($filters['trainer_id']) {
            $trainer = User::find($filters['trainer_id']);
            $trainerName = $trainer ? trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? '')) : $filters['trainer_id'];
            $filterSummary[] = 'Trainer=' . ($trainerName !== '' ? $trainerName : $filters['trainer_id']);
        }
        if ($filters['status'] !== 'all') {
            $filterSummary[] = 'Status=' . ucfirst($filters['status']);
        }

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
        $phpWord->addTableStyle('TrainerClassHistoryTable', $tableStyle, $firstRowStyle);
        $table = $section->addTable('TrainerClassHistoryTable');

        $headers = [
            'ID',
            'Class',
            'Code',
            'Trainer',
            'Enrollments',
            'Rate/hr',
            'Starts',
            'Ends',
            'Admin Status',
            'Archive',
        ];
        $headerRow = $table->addRow();
        foreach ($headers as $header) {
            $headerRow->addCell()->addText($header, ['bold' => true]);
        }

        foreach ($records as $record) {
            $trainer = $record->user;
            $start = $record->class_start_date ? Carbon::parse($record->class_start_date) : null;
            $end = $record->class_end_date ? Carbon::parse($record->class_end_date) : null;
            $statusMeta = [
                0 => 'Pending',
                1 => 'Approved',
                2 => 'Rejected',
            ];

            $row = $table->addRow();
            $cells = [
                $record->id,
                $record->name,
                $record->class_code,
                $trainer ? trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? '')) : 'Not assigned',
                $record->user_schedules_count ?? 0,
                $record->trainer_rate_per_hour !== null ? number_format((float) $record->trainer_rate_per_hour, 2) : '—',
                $start ? $start->format('Y-m-d H:i') : '—',
                $end ? $end->format('Y-m-d H:i') : '—',
                $statusMeta[$record->isadminapproved] ?? 'Pending',
                (int) ($record->is_archieve ?? 0) === 1 ? 'Archived' : 'Active',
            ];

            foreach ($cells as $value) {
                $row->addCell()->addText((string) $value);
            }
        }

        $tempPath = storage_path('app/temp_exports');
        if (!is_dir($tempPath)) {
            @mkdir($tempPath, 0775, true);
        }

        $fileName = 'trainer_class_history_' . now()->format('Y-m-d_H-i') . '.docx';
        $fullPath = $tempPath . DIRECTORY_SEPARATOR . $fileName;
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($fullPath);

        return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
    }
}
