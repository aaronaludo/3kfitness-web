<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Attendance2;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Language;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search_column' => 'nullable|string',
            'name' => 'nullable|string|max:255',
            'start_date'    => 'nullable|date_format:Y-m-d',
            'end_date'      => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'status'        => 'nullable|in:all,open,completed',
        ]);
        
        $search = $request->name;
        $search_column = $request->search_column;
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $statusFilter = $request->input('status', 'all');

        if (empty($statusFilter)) {
            $statusFilter = 'all';
        }
    
        $allowed_columns = [
            'id', 'role', 'name', 'clockin_at', 'clockout_at', 'created_at'
        ];
    
        if (!in_array($search_column, $allowed_columns)) {
            $search_column = null;
        }

        $date_columns = ['clockin_at', 'clockout_at', 'created_at'];
        $rangeColumn = in_array($search_column, $date_columns, true) ? $search_column : 'clockin_at';

        $activeAttendanceBase = Attendance2::where('is_archive', 0);
        $statusTallies = [
            'all' => (clone $activeAttendanceBase)->count(),
            'open' => (clone $activeAttendanceBase)->whereNull('clockout_at')->count(),
            'completed' => (clone $activeAttendanceBase)->whereNotNull('clockout_at')->count(),
        ];
    
        $baseQuery = Attendance2::query()
            ->with('user.role') // Ensure role relationship is loaded
            ->when($search && $search_column, function ($query) use ($search, $search_column) {
                if ($search_column === 'role') {
                    return $query->whereHas('user.role', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                }
    
                if ($search_column === 'name') {
                    return $query->whereHas('user', function ($q) use ($search) {
                        $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    });
                }
    
                return $query->where($search_column, 'like', "%{$search}%");
            })
            ->when($startDate || $endDate, function ($query) use ($startDate, $endDate, $rangeColumn) {
                if ($startDate) {
                    $query->whereDate($rangeColumn, '>=', Carbon::createFromFormat('Y-m-d', $startDate)->toDateString());
                }

                if ($endDate) {
                    $query->whereDate($rangeColumn, '<=', Carbon::createFromFormat('Y-m-d', $endDate)->toDateString());
                }
            })
            ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                if ($statusFilter === 'open') {
                    return $query->whereNull('clockout_at');
                }

                if ($statusFilter === 'completed') {
                    return $query->whereNotNull('clockout_at');
                }

                return $query;
            })
            ->orderByDesc('clockin_at');

        $queryParamsWithoutArchivePage = $request->except('archive_page');
        $queryParamsWithoutMainPage = $request->except('page');

        $data = (clone $baseQuery)
            ->where('is_archive', 0)
            ->paginate(10)
            ->appends($queryParamsWithoutArchivePage);

        $archivedData = (clone $baseQuery)
            ->where('is_archive', 1)
            ->paginate(10, ['*'], 'archive_page')
            ->appends($queryParamsWithoutMainPage);

        return view('admin.attendances.index', compact('data', 'archivedData', 'statusTallies', 'statusFilter'));
    }


    public function scanner()
    {
        return view('admin.attendances.scanner');
    }

    public function fetchScanner(Request $request)
    {
        $result = $request->result;
    
        if (!preg_match('/^[\w\.-]+@[\w\.-]+\.[a-zA-Z]{2,}_clock(in|out)$/', $result)) {
            return response()->json(['data' => 'Invalid format.']);
        }
    
        [$email, $type] = explode('_', $result);
        $user = User::where('email', $email)->first();
    
        if ($user) {
            if ($user->role_id == 3) {
                $membership = $user->membershipPayments()
                    ->where('isapproved', 1)
                    ->where('expiration_at', '>', now())
                    ->latest('expiration_at')
                    ->first();
    
                if (!$membership) {
                    return response()->json(['data' => 'No valid membership found']);
                }
            }
    
            $existingAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('created_at', now()->toDateString())
                ->pluck('type')
                ->toArray();
    
            if ($type === 'clockout' && !in_array('clockin', $existingAttendance)) {
                return response()->json(['data' => "Clockout cannot be used without clocking in first."]);
            }
    
            if (($type === 'clockin' && in_array('clockin', $existingAttendance)) ||
                ($type === 'clockout' && in_array('clockout', $existingAttendance))) {
                return response()->json(['data' => "User has already clocked $type today."]);
            }
    
            $data = new Attendance;
            $data->user_id = $user->id;
            $data->type = $type;
            $data->save();
    
            return response()->json([
                'data' => $user->first_name .' '. $user->last_name . ' has ' . ($type == 'clockin' ? 'clocked in' : 'clocked out') . ' successfully'
            ]);
        } else {
            return response()->json(['data' => 'No data found']);
        }
    }
    
    public function fetchScanner2(Request $request)
    {
        $email = $request->result;
    
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['data' => 'Invalid email format.']);
        }
    
        $user = User::where('email', $email)->first();
    
        if (!$user) {
            return response()->json(['data' => 'No data found']);
        }
    
        if ($user->role_id == 3) {
            $membership = $user->membershipPayments()
                ->where('isapproved', 1)
                ->where('expiration_at', '>', now())
                ->latest('expiration_at')
                ->first();
    
            if (!$membership) {
                return response()->json(['data' => 'No valid membership found']);
            }
        }
    
        // Check if the user has already clocked in or out for today
        $attendance = Attendance2::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('created_at', 'desc') // Get the latest record
            ->first();
    
        if (!$attendance || $attendance->clockout_at) {
            // If no attendance record exists, or if the user has clocked out, clock in
            $attendance = new Attendance2();
            $attendance->user_id = $user->id;
            $attendance->clockin_at = now();
            $attendance->save();
    
            return response()->json(['data' => $user->first_name . ' ' . $user->last_name . ' has clocked in successfully.']);
        }
    
        if ($attendance && !$attendance->clockout_at) {
            // If the user has clocked in, clock out
            $attendance->clockout_at = now();
            $attendance->save();
    
            return response()->json(['data' => $user->first_name . ' ' . $user->last_name . ' has clocked out successfully.']);
        }
    
        return response()->json(['data' => 'An unexpected error occurred.']);
    }
    
    public function delete(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:attendances2,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }

        $data = Attendance2::findOrFail($request->id);

        $attendanceId = $data->id;
        $attendanceUser = optional($data->user);
        $attendanceUserName = trim(sprintf('%s %s', $attendanceUser->first_name ?? '', $attendanceUser->last_name ?? ''));
        $attendanceLabel = $attendanceUserName !== ''
            ? sprintf('#%d for %s', $attendanceId, $attendanceUserName)
            : sprintf('#%d', $attendanceId);

        if ((int) $data->is_archive === 1) {
            $data->delete();
            $message = 'Attendance record deleted permanently';
            $this->logAdminActivity("deleted attendance record {$attendanceLabel} permanently");
        } else {
            $data->is_archive = 1;
            $data->save();
            $message = 'Attendance record moved to archive';
            $this->logAdminActivity("archived attendance record {$attendanceLabel}");
        }

        return redirect()->route('admin.staff-account-management.attendances')->with('success', $message);
    }

    public function restore(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:attendances2,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }

        $data = Attendance2::findOrFail($request->id);
        $attendanceId = $data->id;
        $attendanceUser = optional($data->user);
        $attendanceUserName = trim(sprintf('%s %s', $attendanceUser->first_name ?? '', $attendanceUser->last_name ?? ''));
        $attendanceLabel = $attendanceUserName !== ''
            ? sprintf('#%d for %s', $attendanceId, $attendanceUserName)
            : sprintf('#%d', $attendanceId);

        if ((int) $data->is_archive === 0) {
            return redirect()->route('admin.staff-account-management.attendances')->with('success', 'Attendance record is already active');
        }

        $data->is_archive = 0;
        $data->save();

        $this->logAdminActivity("restored attendance record {$attendanceLabel}");

        return redirect()->route('admin.staff-account-management.attendances')->with('success', 'Attendance record restored successfully');
    }
    
    public function print(Request $request)
    {
        $request->validate([
            'created_start' => 'nullable|date_format:Y-m-d',
            'created_end'   => 'nullable|date_format:Y-m-d|after_or_equal:created_start',
            'name'          => 'nullable|string|max:255',
            'search_column' => 'nullable|string',
            'status'        => 'nullable|in:all,open,completed',
        ]);

        $search       = $request->input('name');
        $searchColumn = $request->input('search_column');
        $startDate    = $request->input('created_start', $request->input('start_date'));
        $endDate      = $request->input('created_end', $request->input('end_date'));
        $statusFilter = $request->input('status', 'all');

        if (empty($statusFilter)) {
            $statusFilter = 'all';
        }

        $allowedColumns = ['id', 'role', 'name', 'clockin_at', 'clockout_at', 'created_at'];
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }

        $dateColumns = ['clockin_at', 'clockout_at', 'created_at'];
        $rangeColumn = in_array($searchColumn, $dateColumns, true) ? $searchColumn : 'clockin_at';

        $start = $startDate ? Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay() : null;
        $end   = $endDate   ? Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay()   : null;

        $query = Attendance2::query()
            ->with('user.role')
            ->when($search && $searchColumn, function ($query) use ($search, $searchColumn) {
                if ($searchColumn === 'role') {
                    return $query->whereHas('user.role', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                }

                if ($searchColumn === 'name') {
                    return $query->whereHas('user', function ($q) use ($search) {
                        $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    });
                }

                return $query->where($searchColumn, 'like', "%{$search}%");
            })
            ->when($start || $end, function ($query) use ($start, $end, $rangeColumn) {
                if ($start && $end) {
                    return $query->whereBetween($rangeColumn, [$start, $end]);
                }

                if ($start) {
                    return $query->whereDate($rangeColumn, '>=', $start->toDateString());
                }

                if ($end) {
                    return $query->whereDate($rangeColumn, '<=', $end->toDateString());
                }

                return $query;
            })
            ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                if ($statusFilter === 'open') {
                    return $query->whereNull('clockout_at');
                }

                if ($statusFilter === 'completed') {
                    return $query->whereNotNull('clockout_at');
                }

                return $query;
            })
            ->where('is_archive', 0)
            ->orderByDesc('clockin_at');

        $records = $query->get();

        $suffix = '';
        if ($start && $end) {
            $suffix .= '_' . $start->format('Ymd') . '_to_' . $end->format('Ymd');
        }
        if ($statusFilter !== 'all') {
            $suffix .= '_' . $statusFilter;
        }

        $fileName = 'attendance_log' . $suffix . '_' . now()->format('Y-m-d') . '.docx';

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

        $titleParts = ['Attendance Log'];
        if ($start && $end) {
            $titleParts[] = $start->format('M d, Y') . ' – ' . $end->format('M d, Y');
        }
        if ($statusFilter !== 'all') {
            $titleParts[] = ucfirst($statusFilter);
        }

        $section->addText(implode(' | ', $titleParts), ['bold' => true, 'size' => 16]);
        $section->addText('Generated: ' . now()->format('M d, Y H:i'));
        $section->addTextBreak(1);

        $tableStyle = [
            'borderColor' => '777777',
            'borderSize'  => 6,
            'cellMargin'  => 80,
        ];
        $firstRowStyle = ['bgColor' => 'DDDDDD'];
        $phpWord->addTableStyle('AttendanceTable', $tableStyle, $firstRowStyle);
        $table = $section->addTable('AttendanceTable');

        $headers = [
            'ID',
            'Role',
            'Member Name',
            'Clock-in Time',
            'Clock-out Time',
            'Duration (hrs)',
            'Status',
            'Created At',
            'Updated At',
        ];
        $headerRow = $table->addRow();
        foreach ($headers as $header) {
            $headerRow->addCell()->addText($header, ['bold' => true]);
        }

        foreach ($records as $record) {
            $row = $table->addRow();

            $roleName = optional(optional($record->user)->role)->name ?? 'Unknown';
            $fullName = optional($record->user) ? trim(($record->user->first_name ?? '') . ' ' . ($record->user->last_name ?? '')) : 'Unknown';
            $duration = null;
            if ($record->clockin_at && $record->clockout_at) {
                $clockIn  = Carbon::parse($record->clockin_at);
                $clockOut = Carbon::parse($record->clockout_at);
                $duration = $clockOut->diffInMinutes($clockIn) / 60;
            }

            $statusText = $record->clockout_at ? 'Completed' : 'Open';

            $clockInValue = $record->clockin_at ? (string) $record->clockin_at : '—';
            $clockOutValue = $record->clockout_at ? (string) $record->clockout_at : '—';

            $cells = [
                $record->id,
                $roleName,
                $fullName,
                $clockInValue,
                $clockOutValue,
                $duration !== null ? number_format($duration, 2) : '—',
                $statusText,
                (string) $record->created_at,
                (string) $record->updated_at,
            ];

            foreach ($cells as $value) {
                $row->addCell()->addText((string) $value);
            }
        }

        $tempPath = storage_path('app/temp_exports');
        if (!is_dir($tempPath)) {
            @mkdir($tempPath, 0775, true);
        }

        $fullPath = $tempPath . DIRECTORY_SEPARATOR . $fileName;
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($fullPath);

        return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
    }
}
