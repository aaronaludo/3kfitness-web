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
            'search_column' => 'nullable|string',
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
            $this->applyEnrollmentSearch($baseQuery, $filters['search']);
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

        $printAllEnrollments = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.history.enrollments', [
            'enrollments'  => $enrollments,
            'classOptions' => $classOptions,
            'filters'      => $filters,
            'stats'        => $stats,
            'printAllEnrollments' => $printAllEnrollments,
        ]);
    }

    public function print(Request $request)
    {
        $request->validate([
            'search'     => 'nullable|string|max:255',
            'class_id'   => 'nullable|exists:schedules,id',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'search_column' => 'nullable|string',
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
            $this->applyEnrollmentSearch($query, $filters['search']);
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
                ($filters['search'] ? "Search='{$filters['search']}'" : '') .
                ($filters['class_id'] ? " Class ID={$filters['class_id']}" : '')
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

        $weekdayLookup = [
            'sun' => 'Sunday',
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
        ];
        $normalizeDayKeys = function ($value) {
            if (is_array($value)) {
                return $value;
            }

            if (is_string($value) && trim($value) !== '') {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }

                return [$value];
            }

            return [];
        };
        $formatTimeLabel = function ($startTime, $endTime) {
            try {
                if ($startTime && $endTime) {
                    return Carbon::parse($startTime)->format('g:i A') . ' - ' . Carbon::parse($endTime)->format('g:i A');
                }
                if ($startTime) {
                    return Carbon::parse($startTime)->format('g:i A');
                }
                if ($endTime) {
                    return Carbon::parse($endTime)->format('g:i A');
                }
            } catch (\Throwable $th) {
                return null;
            }

            return null;
        };
        $formatDateLabel = function ($value) {
            if (empty($value)) {
                return null;
            }

            try {
                return Carbon::parse($value)->format('M d, Y');
            } catch (\Throwable $th) {
                return (string) $value;
            }
        };
        $buildScheduleLabel = function ($class) use ($weekdayLookup, $normalizeDayKeys, $formatTimeLabel, $formatDateLabel) {
            if (!$class) {
                return 'Schedule not set';
            }

            $timeLabel = $formatTimeLabel($class->class_start_time ?? null, $class->class_end_time ?? null) ?: 'Time not set';
            $dayKeys = $normalizeDayKeys($class->recurring_days ?? []);
            $dayLabel = collect($dayKeys)->map(function ($dayKey) use ($weekdayLookup) {
                $value = is_string($dayKey) ? trim($dayKey) : (string) $dayKey;
                if ($value === '') {
                    return null;
                }

                $lookupKey = strtolower(substr($value, 0, 3));

                return $weekdayLookup[$lookupKey] ?? ucfirst($value);
            })->filter()->implode(', ');
            $dayLabel = $dayLabel !== '' ? $dayLabel : 'One-time';

            $seriesStart = $formatDateLabel($class->series_start_date ?? null) ?: $formatDateLabel($class->class_start_date ?? null);
            $seriesEnd = $formatDateLabel($class->series_end_date ?? null) ?: $formatDateLabel($class->class_end_date ?? null);
            $seriesLabel = ($seriesStart || $seriesEnd)
                ? 'Series: ' . ($seriesStart ?: '—') . ' -> ' . ($seriesEnd ?: '—')
                : null;

            return $timeLabel . ' | ' . $dayLabel . ($seriesLabel ? ' | ' . $seriesLabel : '');
        };

        $headers = [
            '#', 'Member', 'User Code', 'Contact', 'Class', 'Trainer', 'Joined', 'Schedule',
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
            $memberCode = $member->user_code ?? '';
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
            $scheduleLabel = $buildScheduleLabel($class);

            $row = $table->addRow();
            $cells = [
                $class ? $class->id : ($enrollment->schedule_id ?? '—'),
                $memberName,
                $memberCode !== '' ? $memberCode : '—',
                $contact !== '' ? $contact : '—',
                $classTitle,
                $trainerName,
                $joined,
                $scheduleLabel,
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

    protected function applyEnrollmentSearch($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $like = '%' . $search . '%';
        $integerSearch = ctype_digit($search) ? (int) $search : null;

        return $query->where(function ($query) use ($like, $integerSearch) {
            if ($integerSearch !== null) {
                $query->orWhere('schedule_id', $integerSearch)
                    ->orWhere('user_id', $integerSearch);
            }

            $query->orWhere('created_at', 'like', $like);

            $query->orWhereHas('user', function ($userQuery) use ($like) {
                $userQuery->where(function ($nameQuery) use ($like) {
                    $nameQuery->whereRaw(
                        "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                        [$like]
                    )
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('user_code', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone_number', 'like', $like);
                });
            });

            $query->orWhereHas('user.role', function ($roleQuery) use ($like) {
                $roleQuery->where('name', 'like', $like);
            });

            $query->orWhereHas('schedule', function ($scheduleQuery) use ($like, $integerSearch) {
                $scheduleQuery->where(function ($inner) use ($like, $integerSearch) {
                    if ($integerSearch !== null) {
                        $inner->orWhere('id', $integerSearch);
                    }
                    $inner->orWhere('name', 'like', $like)
                        ->orWhere('class_code', 'like', $like)
                        ->orWhere('class_start_date', 'like', $like)
                        ->orWhere('class_end_date', 'like', $like);
                });
            });

            $query->orWhereHas('schedule.user', function ($trainerQuery) use ($like) {
                $trainerQuery->where(function ($trainerNameQuery) use ($like) {
                    $trainerNameQuery->whereRaw(
                        "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                        [$like]
                    )
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like);
                });
            });
        });
    }
}
