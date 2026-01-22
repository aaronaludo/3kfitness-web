<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Attendance2;
use App\Models\UserQrCode;
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
        
        $search = trim((string) $request->input('name', ''));
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $statusFilter = $request->input('status', 'all');
        $currentAdmin = $request->user();
        $restrictStaffView = $currentAdmin && (int) $currentAdmin->role_id === 2;

        if (empty($statusFilter)) {
            $statusFilter = 'all';
        }
    
        $activeAttendanceBase = Attendance2::where('is_archive', 0)
            ->when($restrictStaffView, function ($query) {
                $query->whereDoesntHave('user', function ($q) {
                    $q->where('role_id', 2);
                });
            });
        $statusTallies = [
            'all' => (clone $activeAttendanceBase)->count(),
            'open' => (clone $activeAttendanceBase)->whereNull('clockout_at')->count(),
            'completed' => (clone $activeAttendanceBase)->whereNotNull('clockout_at')->count(),
        ];

        $baseQuery = Attendance2::query()
            ->with('user.role') // Ensure role relationship is loaded
            ->when($restrictStaffView, function ($query) {
                $query->whereDoesntHave('user', function ($q) {
                    $q->where('role_id', 2);
                });
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

        if ($search !== '') {
            $this->applyAttendanceLogSearch($baseQuery, $search);
        }

        if ($startDate) {
            $baseQuery->whereDate('clockin_at', '>=', Carbon::createFromFormat('Y-m-d', $startDate)->toDateString());
        }

        if ($endDate) {
            $baseQuery->whereDate('clockin_at', '<=', Carbon::createFromFormat('Y-m-d', $endDate)->toDateString());
        }

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

        $printAllActive = (clone $baseQuery)
            ->where('is_archive', 0)
            ->get();

        $printAllArchived = (clone $baseQuery)
            ->where('is_archive', 1)
            ->get();

        return view('admin.attendances.index', [
            'data' => $data,
            'archivedData' => $archivedData,
            'statusTallies' => $statusTallies,
            'statusFilter' => $statusFilter,
            'printAllActive' => $printAllActive,
            'printAllArchived' => $printAllArchived,
        ]);
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
        $rawResult = $request->input('result');
        $action = $request->input('action');

        if ($action && !in_array($action, ['clockin', 'clockout'], true)) {
            return response()->json(['data' => 'Invalid action provided.']);
        }

        $extractPayloadValue = function ($payload, array $keys) {
            if (is_array($payload)) {
                foreach ($keys as $key) {
                    if (isset($payload[$key]) && is_string($payload[$key])) {
                        $candidate = trim($payload[$key]);
                        if ($candidate !== '') {
                            return $candidate;
                        }
                    }
                }
            }

            if (is_string($payload)) {
                $trimmed = trim($payload);
                if ($trimmed === '') {
                    return null;
                }
                if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
                    $decoded = json_decode($trimmed, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        foreach ($keys as $key) {
                            if (isset($decoded[$key]) && is_string($decoded[$key])) {
                                $candidate = trim($decoded[$key]);
                                if ($candidate !== '') {
                                    return $candidate;
                                }
                            }
                        }
                    }
                }
                return $trimmed;
            }

            return null;
        };

        $email = null;
        $token = null;
        if ($action) {
            $email = $extractPayloadValue($rawResult, ['email', 'user_email', 'userEmail']);
        } else {
            $token = $extractPayloadValue($rawResult, ['token', 'qr_token', 'qrToken']);
        }

        $user = null;
        $membership = null;
        $membershipName = null;
        $membershipActive = null;
        $membershipDaysRemaining = null;
        $userPayload = null;

        $buildUserPayload = function ($user, $membershipName, $membershipActive, $membershipDaysRemaining = null) {
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $avatar = $user->profile_picture
                ? asset($user->profile_picture)
                : asset('assets/images/profile-45x45.png');
            $codeValue = $user->user_code ?? $user->id;
            $formattedCode = $codeValue ? (str_starts_with((string) $codeValue, '#') ? (string) $codeValue : '#' . $codeValue) : '#---';

            return [
                'name' => $name !== '' ? $name : ($user->name ?? 'User'),
                'email' => $user->email ?? '',
                'phone' => $user->phone_number ?? '',
                'code' => $formattedCode,
                'membership' => $membershipName ?? 'No Membership',
                'membership_active' => (bool) $membershipActive,
                'membership_days_remaining' => is_null($membershipDaysRemaining) ? null : (int) $membershipDaysRemaining,
                'avatar' => $avatar,
            ];
        };

        $respond = function ($message, $status = 'error', $userPayload = null) {
            return response()->json([
                'data' => $message,
                'status' => $status,
                'user' => $userPayload,
            ]);
        };

        if ($action) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $respond('Invalid email format.', 'error');
            }

            $user = User::where('email', $email)->first();
        } else {
            if (!$token) {
                return $respond('Invalid QR code.', 'error');
            }

            $qrCode = UserQrCode::where('token', $token)
                ->where('is_active', true)
                ->orderByDesc('issued_at')
                ->first();

            if (!$qrCode) {
                return $respond('QR code expired or invalid.', 'error');
            }

            $user = $qrCode->user;
        }
    
        if (!$user) {
            return $respond('No data found', 'error');
        }
    
        if ($user->role_id == 3) {
            $membership = $user->membershipPayments()
                ->where('isapproved', 1)
                ->where('expiration_at', '>', now())
                ->latest('expiration_at')
                ->first();
    
            if (!$membership) {
                $membershipName = 'No Membership';
                $membershipActive = false;
                $userPayload = $buildUserPayload($user, $membershipName, $membershipActive, $membershipDaysRemaining);
                return $respond('No valid membership found', 'error', $userPayload);
            }

            $membershipName = optional($membership->membership)->name ?? 'Active Membership';
            $membershipActive = true;
            if (!empty($membership->expiration_at)) {
                $expirationDate = \Carbon\Carbon::parse($membership->expiration_at);
                $membershipDaysRemaining = max(0, now()->diffInDays($expirationDate, false));
            }
            $userPayload = $buildUserPayload($user, $membershipName, $membershipActive, $membershipDaysRemaining);
        } else {
            $membershipName = optional(optional($user)->role)->name ?? 'Staff';
            $membershipActive = true;
            $userPayload = $buildUserPayload($user, $membershipName, $membershipActive, $membershipDaysRemaining);
        }
    
        // Check if the user has already clocked in or out for today (active records only)
        $attendance = Attendance2::where('user_id', $user->id)
            ->where('is_archive', 0)
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('created_at', 'desc') // Get the latest record
            ->first();

        // Explicit manual action: clock in only
        if ($action === 'clockin') {
            if ($attendance && !$attendance->clockout_at) {
                return $respond($user->first_name . ' ' . $user->last_name . ' already clocked in today.', 'error', $userPayload);
            }

            $attendance = new Attendance2();
            $attendance->user_id = $user->id;
            $attendance->clockin_at = now();
            $attendance->save();

            return $respond($user->first_name . ' ' . $user->last_name . ' has clocked in successfully.', 'clockin', $userPayload);
        }

        // Explicit manual action: clock out only
        if ($action === 'clockout') {
            if (!$attendance || $attendance->clockout_at) {
                return $respond('No active clock-in found for today.', 'error', $userPayload);
            }

            $attendance->clockout_at = now();
            $attendance->save();

            return $respond($user->first_name . ' ' . $user->last_name . ' has clocked out successfully.', 'clockout', $userPayload);
        }

        // Default behavior: toggle clock-in/clock-out
        if (!$attendance || $attendance->clockout_at) {
            // If no attendance record exists, or if the user has clocked out, clock in
            $attendance = new Attendance2();
            $attendance->user_id = $user->id;
            $attendance->clockin_at = now();
            $attendance->save();
    
            return $respond($user->first_name . ' ' . $user->last_name . ' has clocked in successfully.', 'clockin', $userPayload);
        }
    
        if ($attendance && !$attendance->clockout_at) {
            // If the user has clocked in, clock out
            $attendance->clockout_at = now();
            $attendance->save();
    
            return $respond($user->first_name . ' ' . $user->last_name . ' has clocked out successfully.', 'clockout', $userPayload);
        }
    
        return $respond('An unexpected error occurred.', 'error', $userPayload);
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

        $search       = trim((string) $request->input('name', ''));
        $startDate    = $request->input('created_start', $request->input('start_date'));
        $endDate      = $request->input('created_end', $request->input('end_date'));
        $statusFilter = $request->input('status', 'all');
        $currentAdmin = $request->user();
        $restrictStaffView = $currentAdmin && (int) $currentAdmin->role_id === 2;

        if (empty($statusFilter)) {
            $statusFilter = 'all';
        }

        $start = $startDate ? Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay() : null;
        $end   = $endDate   ? Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay()   : null;

        $query = Attendance2::query()
            ->with('user.role')
            ->when($restrictStaffView, function ($query) {
                $query->whereDoesntHave('user', function ($q) {
                    $q->where('role_id', 2);
                });
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

        if ($search !== '') {
            $this->applyAttendanceLogSearch($query, $search);
        }

        if ($start && $end) {
            $query->whereBetween('clockin_at', [$start, $end]);
        } elseif ($start) {
            $query->whereDate('clockin_at', '>=', $start->toDateString());
        } elseif ($end) {
            $query->whereDate('clockin_at', '<=', $end->toDateString());
        }

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
            'User Code',
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
                optional($record->user)->user_code ?? '—',
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

    protected function applyAttendanceLogSearch($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $like = '%' . $search . '%';
        $lowerSearch = strtolower($search);
        $integerSearch = ctype_digit($search) ? (int) $search : null;

        $parsedDate = null;
        try {
            $parsedDate = Carbon::parse($search)->toDateString();
        } catch (\Exception $e) {
            $parsedDate = null;
        }

        return $query->where(function ($query) use ($like, $lowerSearch, $integerSearch, $parsedDate) {
            if ($integerSearch !== null) {
                $query->orWhere('id', $integerSearch);
            }

            $query->orWhere('clockin_at', 'like', $like)
                ->orWhere('clockout_at', 'like', $like)
                ->orWhere('created_at', 'like', $like)
                ->orWhere('updated_at', 'like', $like);

            if ($parsedDate) {
                $query->orWhereDate('clockin_at', $parsedDate)
                    ->orWhereDate('clockout_at', $parsedDate)
                    ->orWhereDate('created_at', $parsedDate)
                    ->orWhereDate('updated_at', $parsedDate);
            }

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

            if (strpos($lowerSearch, 'completed') !== false || strpos($lowerSearch, 'complete') !== false) {
                $query->orWhereNotNull('clockout_at');
            }
            if (strpos($lowerSearch, 'open') !== false || strpos($lowerSearch, 'pending') !== false) {
                $query->orWhereNull('clockout_at');
            }
        });
    }
}
