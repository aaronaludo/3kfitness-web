<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\UserSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClassEnrollmentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search'     => 'nullable|string|max:255',
            'class_id'   => 'nullable|exists:schedules,id',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $filters = [
            'search'     => trim((string) $request->input('search', '')),
            'class_id'   => $request->input('class_id'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ];

        $now = Carbon::now();

        $baseQuery = UserSchedule::with(['user.role', 'schedule.user'])
            ->whereHas('schedule', function ($query) use ($now) {
                $query->whereNotNull('class_end_date')
                    ->where('class_end_date', '<', $now);
            });

        if ($filters['class_id']) {
            $baseQuery->where('schedule_id', $filters['class_id']);
        }

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';

            $baseQuery->where(function ($query) use ($like) {
                $query
                    ->whereHas('user', function ($userQuery) use ($like) {
                        $userQuery->where(function ($nameQuery) use ($like) {
                            $nameQuery->whereRaw(
                                "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                                [$like]
                            )
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone_number', 'like', $like);
                        });
                    })
                    ->orWhereHas('schedule', function ($scheduleQuery) use ($like) {
                        $scheduleQuery
                            ->where('name', 'like', $like)
                            ->orWhere('class_code', 'like', $like);
                    });
            });
        }

        if ($filters['start_date']) {
            $baseQuery->whereDate('created_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $baseQuery->whereDate('created_at', '<=', $filters['end_date']);
        }

        $queryParams = $request->query();

        $enrollments = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->paginate(10)
            ->appends($queryParams);

        $stats = [
            'total'   => (clone $baseQuery)->count(),
            'members' => (clone $baseQuery)->distinct('user_id')->count('user_id'),
            'classes' => (clone $baseQuery)->distinct('schedule_id')->count('schedule_id'),
        ];

        $classOptions = Schedule::whereNotNull('class_end_date')
            ->where('class_end_date', '<', $now)
            ->orderBy('name')
            ->get(['id', 'name', 'class_code']);

        return view('admin.history.enrollments', [
            'enrollments'  => $enrollments,
            'classOptions' => $classOptions,
            'filters'      => $filters,
            'stats'        => $stats,
        ]);
    }

    public function print(Request $request)
    {
        $request->validate([
            'search'     => 'nullable|string|max:255',
            'class_id'   => 'nullable|exists:schedules,id',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $filters = [
            'search'     => trim((string) $request->input('search', '')),
            'class_id'   => $request->input('class_id'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ];

        $now = Carbon::now();

        $query = UserSchedule::with(['user.role', 'schedule.user'])
            ->whereHas('schedule', function ($query) use ($now) {
                $query->whereNotNull('class_end_date')
                    ->where('class_end_date', '<', $now);
            });

        if ($filters['class_id']) {
            $query->where('schedule_id', $filters['class_id']);
        }

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';

            $query->where(function ($builder) use ($like) {
                $builder
                    ->whereHas('user', function ($userQuery) use ($like) {
                        $userQuery->where(function ($nameQuery) use ($like) {
                            $nameQuery->whereRaw(
                                "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                                [$like]
                            )
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone_number', 'like', $like);
                        });
                    })
                    ->orWhereHas('schedule', function ($scheduleQuery) use ($like) {
                        $scheduleQuery
                            ->where('name', 'like', $like)
                            ->orWhere('class_code', 'like', $like);
                    });
            });
        }

        if ($filters['start_date']) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        $data = $query->orderByDesc('created_at')->get();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->getSettings()->setThemeFontLang(
            new \PhpOffice\PhpWord\Style\Language(\PhpOffice\PhpWord\Style\Language::EN_US)
        );
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
            $rangeLabel = ' — ' . Carbon::parse($filters['start_date'])->format('M d, Y') .
                ' to ' . Carbon::parse($filters['end_date'])->format('M d, Y');
        } elseif ($filters['start_date']) {
            $rangeLabel = ' — From ' . Carbon::parse($filters['start_date'])->format('M d, Y');
        } elseif ($filters['end_date']) {
            $rangeLabel = ' — Until ' . Carbon::parse($filters['end_date'])->format('M d, Y');
        }

        $section->addText('Enrollment History' . $rangeLabel, ['bold' => true, 'size' => 16]);
        $section->addText('Generated: ' . now()->format('M d, Y H:i'));
        if ($filters['search'] || $filters['class_id']) {
            $section->addText('Filters: ' . trim(
                ($filters['search'] ? "Search='{$filters['search']}' " : '') .
                ($filters['class_id'] ? "Class ID={$filters['class_id']}" : '')
            ));
        }
        $section->addTextBreak(1);

        $tableStyle = [
            'borderColor' => '777777',
            'borderSize'  => 6,
            'cellMargin'  => 80,
        ];
        $firstRowStyle = ['bgColor' => 'DDDDDD'];
        $phpWord->addTableStyle('EnrollmentHistoryTable', $tableStyle, $firstRowStyle);
        $table = $section->addTable('EnrollmentHistoryTable');

        $headers = [
            '#', 'Member', 'Contact', 'Class', 'Trainer', 'Joined', 'Class Start', 'Class End',
        ];
        $headerRow = $table->addRow();
        foreach ($headers as $h) {
            $headerRow->addCell()->addText($h, ['bold' => true]);
        }

        foreach ($data as $enrollment) {
            $class   = $enrollment->schedule;
            $member  = $enrollment->user;
            $trainer = optional($class)->user;

            $memberName = $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : 'Unknown member';
            $contact    = trim(($member->email ?? '') . (($member->email ?? '') && ($member->phone_number ?? '') ? ' / ' : '') . ($member->phone_number ?? ''));
            $classTitle = $class
                ? trim(($class->name ?? '') . ($class->class_code ? ' (' . $class->class_code . ')' : ''))
                : 'Class unavailable';
            $trainerName = $trainer
                ? trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? ''))
                : 'Not assigned';

            $joined = $enrollment->created_at
                ? $enrollment->created_at->format('M d, Y g:i A')
                : '—';

            $classStart = $class && $class->class_start_date
                ? Carbon::parse($class->class_start_date)->format('M d, Y g:i A')
                : '—';
            $classEnd = $class && $class->class_end_date
                ? Carbon::parse($class->class_end_date)->format('M d, Y g:i A')
                : '—';

            $row = $table->addRow();
            $cells = [
                $class ? $class->id : ($enrollment->schedule_id ?? '—'),
                $memberName,
                $contact !== '' ? $contact : '—',
                $classTitle,
                $trainerName,
                $joined,
                $classStart,
                $classEnd,
            ];

            foreach ($cells as $val) {
                $row->addCell()->addText((string) $val);
            }
        }

        $suffix = '';
        if ($filters['start_date'] && $filters['end_date']) {
            $suffix = '_' . Carbon::parse($filters['start_date'])->format('Ymd') .
                '_to_' . Carbon::parse($filters['end_date'])->format('Ymd');
        }

        $fileName = 'enrollment_history' . $suffix . '_' . date('Y-m-d') . '.docx';

        $tempPath = storage_path('app/temp_exports');
        if (!is_dir($tempPath)) {
            @mkdir($tempPath, 0775, true);
        }
        $fullPath = $tempPath . DIRECTORY_SEPARATOR . $fileName;

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($fullPath);

        return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
    }
}
