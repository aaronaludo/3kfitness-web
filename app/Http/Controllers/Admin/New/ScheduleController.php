<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Log;
use Illuminate\Support\Facades\Validator;
    use Illuminate\Validation\Rule;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
    
class ScheduleController extends Controller
{
    public function all()
    {
        $data = Schedule::all();
    
        $data = $data->map(function ($item) {
            $now = now();
            $start_date = \Carbon\Carbon::parse($item->class_start_date);
            $end_date = \Carbon\Carbon::parse($item->class_end_date);
    
            $status = 'Past';
            if ($now->lt($start_date)) {
                $status = 'Future';
            } elseif ($now->between($start_date, $end_date)) {
                $status = 'Present';
            }
    
            return [
                'id' => $item->id,
                'name' => $item->name,
                'class_code' => $item->class_code,
                'trainer' => $item->trainer_id == 0 ? 'No Trainer for now' : optional($item->user)->first_name . ' ' . optional($item->user)->last_name,
                'slots' => $item->slots,
                'link' => '0',
                'class_start_date' => $item->class_start_date,
                'class_end_date' => $item->class_end_date,
                'isenabled' => $item->isenabled ? 'Enabled' : 'Disabled',
                'status' => $status,
                'isadminapproved' => $item->isadminapproved,
                'rejection_reason' => $item->rejection_reason,
                'created_at' => $item->created_at,
                'is_archieve' => $item->is_archieve,
            ];
        });
    
        return response()->json(['data' => $data]);
    }
    
    public function index(Request $request)
    {
        $request->validate([
            'search_column' => 'nullable|string',
            'name'          => 'nullable|string|max:255',
            'start_date'    => 'nullable|date_format:Y-m-d',
            'end_date'      => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'status'        => 'nullable|in:all,upcoming,active,completed',
        ]);

        $search        = $request->input('name');
        $searchColumn  = $request->input('search_column');
        $startDate     = $request->input('start_date');
        $endDate       = $request->input('end_date');
        $status        = $request->input('status', 'all');

        if (empty($status)) {
            $status = 'all';
        }
    
        $allowedColumns = [
            'id', 'name', 'class_code', 'trainer_name', 'slots',
            'class_start_date', 'class_end_date', 'isadminapproved',
            'rejection_reason', 'created_at',
        ];
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }
    
        // Choose which date column to filter: default to created_at unless a date-type column is selected.
        $dateColumns = ['class_start_date', 'class_end_date', 'created_at'];
        $rangeColumn = in_array($searchColumn, $dateColumns, true) ? $searchColumn : 'created_at';
    
        $classescreatedbyadmin = Schedule::where('created_role', 'Admin')
            ->where('is_archieve', 0)
            ->count();
        $classescreatedbystaff = Schedule::where('created_role', 'Staff')
            ->where('is_archieve', 0)
            ->count();
        $now = Carbon::now();
        $statusTallies = [
            'all' => Schedule::where('is_archieve', 0)->count(),
            'upcoming' => Schedule::where('is_archieve', 0)
                ->where('class_start_date', '>', $now)->count(),
            'active' => Schedule::where('is_archieve', 0)
                ->where('class_start_date', '<=', $now)
                ->where('class_end_date', '>=', $now)
                ->count(),
            'completed' => Schedule::where('is_archieve', 0)
                ->where('class_end_date', '<', $now)->count(),
        ];

        $applyFilters = function ($query) use ($search, $searchColumn, $startDate, $endDate, $rangeColumn, $status, $now) {
            return $query
                ->when($search && $searchColumn, function ($query) use ($search, $searchColumn) {
                    if ($searchColumn === 'trainer_name') {
                        $likeSearch = "%{$search}%";

                        return $query->whereHas('user', function ($userQuery) use ($likeSearch) {
                            $userQuery->where(function ($nameQuery) use ($likeSearch) {
                                $nameQuery->whereRaw(
                                    "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                                    [$likeSearch]
                                )->orWhere('first_name', 'like', $likeSearch)
                                 ->orWhere('last_name', 'like', $likeSearch);
                            });
                        });
                    }

                    $exactColumns = ['id', 'slots', 'isadminapproved'];
                    if (in_array($searchColumn, $exactColumns, true)) {
                        return $query->where($searchColumn, $search);
                    }
                    return $query->where($searchColumn, 'like', "%{$search}%");
                })
                ->when($startDate || $endDate, function ($query) use ($startDate, $endDate, $rangeColumn) {
                    if ($startDate) {
                        $query->whereDate($rangeColumn, '>=', Carbon::createFromFormat('Y-m-d', $startDate)->toDateString());
                    }
                    if ($endDate) {
                        $query->whereDate($rangeColumn, '<=', Carbon::createFromFormat('Y-m-d', $endDate)->toDateString());
                    }
                })
                ->when($status !== 'all', function ($query) use ($status, $now) {
                    if ($status === 'upcoming') {
                        return $query->where('class_start_date', '>', $now);
                    }

                    if ($status === 'active') {
                        return $query->where('class_start_date', '<=', $now)
                            ->where('class_end_date', '>=', $now);
                    }

                    if ($status === 'completed') {
                        return $query->where('class_end_date', '<', $now);
                    }

                    return $query;
                });
        };

        $mapSchedule = function ($schedule) {
            $schedule->user_schedules_count = $schedule->user_schedules_count ?? $schedule->user_schedules->count();

            $schedule->user_schedules_json = $schedule->user_schedules->map(function ($us) {
                $user = $us->user;
                $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';
                return [
                    'user_name'  => $fullName !== '' ? $fullName : 'Unknown',
                    'user_email' => $user->email ?? 'Unknown',
                ];
            });

            return $schedule;
        };

        $queryParamsWithoutArchivePage = $request->except('archive_page');
        $queryParamsWithoutMainPage = $request->except('page');

        $activeQuery = $applyFilters(
            Schedule::with(['user_schedules.user', 'user'])
                ->withCount('user_schedules')
                ->where('is_archieve', 0)
        );

        $data = $activeQuery
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends($queryParamsWithoutArchivePage)
            ->through($mapSchedule);

        $archivedQuery = $applyFilters(
            Schedule::with(['user_schedules.user', 'user'])
                ->withCount('user_schedules')
                ->where('is_archieve', 1)
        );

        $archivedData = $archivedQuery
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'archive_page')
            ->appends($queryParamsWithoutMainPage)
            ->through($mapSchedule);
        
        return view(
            'admin.gymmanagement.schedules',
            compact('data', 'archivedData', 'classescreatedbyadmin', 'classescreatedbystaff', 'statusTallies')
        );
    }

    public function view($id)
    {
        $data = Schedule::findOrFail($id);

        return view('admin.gymmanagement.schedules-view', compact('data'));
    }

    public function users(Request $request, $id)
    {
        $schedule = Schedule::with(['user'])
            ->withCount('user_schedules')
            ->findOrFail($id);

        $search = trim((string) $request->input('search', ''));

        $userSchedulesQuery = $schedule->user_schedules()
            ->with('user')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $userSchedulesQuery->whereHas('user', function ($query) use ($search) {
                $like = "%{$search}%";
                $query->where(function ($inner) use ($like) {
                    $inner->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$like])
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone_number', 'like', $like);
                });
            });
        }

        $userSchedules = $userSchedulesQuery
            ->paginate(10)
            ->withQueryString();

        return view('admin.gymmanagement.schedules-users', [
            'schedule' => $schedule,
            'userSchedules' => $userSchedules,
            'search' => $search,
        ]);
    }

    public function create()
    {
        $trainers = User::where('role_id', 5)->get();
        
        return view('admin.gymmanagement.schedules-create', compact('trainers'));
    }

    public function edit($id)
    {
        $data = Schedule::findOrFail($id);
        $trainers = User::where('role_id', 5)->get();
        
        return view('admin.gymmanagement.schedules-edit', compact('data', 'trainers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'slots' => 'required|integer|min:1',
            'class_start_date' => 'required|date',
            'class_end_date' => 'required|date|after:class_start_date',
            'series_start_date' => 'required|date',
            'series_end_date' => 'required|date|after_or_equal:series_start_date',
            'class_start_time' => 'required|date_format:H:i',
            'class_end_time' => 'required|date_format:H:i|after:class_start_time',
            'recurring_days' => 'required',
            'trainer_id' => 'required',
            'trainer_rate_per_hour' => 'nullable|numeric|min:0',
        ]);

        $recurringDays = $this->sanitizeRecurringDays($request->input('recurring_days'));
        if (empty($recurringDays)) {
            return back()
                ->withErrors(['recurring_days' => 'Please choose at least one recurring day.'])
                ->withInput();
        }
        
        $startRange = Carbon::parse($request->class_start_date)->subHour();
        $endRange = Carbon::parse($request->class_end_date)->addHour();
        
        $existingSchedule = Schedule::where('trainer_id', $request->trainer_id)
            ->where(function ($query) use ($startRange, $endRange) {
                $query->whereBetween('class_start_date', [$startRange, $endRange])
                      ->orWhereBetween('class_end_date', [$startRange, $endRange])
                      ->orWhere(function ($q) use ($startRange, $endRange) {
                          $q->where('class_start_date', '<=', $startRange)
                            ->where('class_end_date', '>=', $endRange);
                      });
            })
            ->first();
        
        if ($existingSchedule) {
            return back()->withErrors(['schedule' => 'The trainer is already booked within this time range.']);
        }

        if ((int) $request->trainer_id !== 0 && !$request->filled('trainer_rate_per_hour')) {
            return back()
                ->withErrors(['trainer_rate_per_hour' => 'Please provide a rate per hour for the assigned trainer.'])
                ->withInput();
        }

        $trainerRate = (int) $request->trainer_id === 0
            ? null
            : $request->input('trainer_rate_per_hour');

        $data = new Schedule;
        $data->name = $request->name;
        $nameParts = explode(' ', $request->name);
        $initials = array_map(fn($word) => strtoupper($word[0]), $nameParts);
        $prefix = implode('', $initials);
        $latestCode = Schedule::where('class_code', 'LIKE', "$prefix-%")
            ->orderBy('class_code', 'desc')
            ->value('class_code');
    
        $number = $latestCode ? intval(substr($latestCode, strlen($prefix) + 1)) + 1 : 1;
        $data->class_code = sprintf('%s-%02d', $prefix, $number);
        $data->slots = $request->slots;
        $data->class_start_date = $request->class_start_date;
        $data->class_end_date = $request->class_end_date;
        $data->series_start_date = $request->series_start_date;
        $data->series_end_date = $request->series_end_date;
        $data->class_start_time = $request->class_start_time;
        $data->class_end_time = $request->class_end_time;
        $data->recurring_days = json_encode($recurringDays);
        $data->isenabled = 1;
        $data->trainer_id = $request->trainer_id;
        $data->trainer_rate_per_hour = $trainerRate;
        $data->isadminapproved = $request->trainer_id == 0 ? 0 : 1; 
        $data->created_role = $request->user()->role_id == 1 || $request->user()->role_id == 4 ? 'Admin' : 'Staff';
        $data->created_by = $request->user()->first_name . " " .  $request->user()->last_name;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('images/schedules');
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
    
            $image->move($destinationPath, $imageName);
            $data->image = 'images/schedules/' . $imageName;
        }
        
        $data->save();
    
        $log = new Log;
        $log->message = $request->user()->first_name . " " . $request->user()->last_name . " has created class successfully.";
        $log->role_name = 'Admin';
        $log->save();
        
        return redirect()->route('admin.gym-management.schedules')->with('success', 'Schedule added successfully');
    }    

    public function update(Request $request, $id)
    {
        $data = Schedule::findOrFail($id);
        
        $request->validate([
            'name' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'slots' => 'required|integer|min:1',
            'class_start_date' => 'required|date',
            'class_end_date' => 'required|date|after:class_start_date',
            'series_start_date' => 'required|date',
            'series_end_date' => 'required|date|after_or_equal:series_start_date',
            'class_start_time' => 'required|date_format:H:i',
            'class_end_time' => 'required|date_format:H:i|after:class_start_time',
            'recurring_days' => 'required',
            'trainer_id' => 'required',
            'class_code' => 'required',
            'trainer_rate_per_hour' => 'nullable|numeric|min:0',
        ]);

        $recurringDays = $this->sanitizeRecurringDays($request->input('recurring_days'));
        if (empty($recurringDays)) {
            return back()
                ->withErrors(['recurring_days' => 'Please choose at least one recurring day.'])
                ->withInput();
        }

        $startRange = Carbon::parse($request->class_start_date)->subHour();
        $endRange = Carbon::parse($request->class_end_date)->addHour();
        
        $existingSchedule = Schedule::where('trainer_id', $request->trainer_id)
            ->where('id', '!=', $data->id)
            ->where(function ($query) use ($startRange, $endRange) {
                $query->whereBetween('class_start_date', [$startRange, $endRange])
                      ->orWhereBetween('class_end_date', [$startRange, $endRange])
                      ->orWhere(function ($q) use ($startRange, $endRange) {
                          $q->where('class_start_date', '<=', $startRange)
                            ->where('class_end_date', '>=', $endRange);
                      });
            })
            ->first();
        
        if ($existingSchedule) {
            return back()->withErrors(['schedule' => 'The trainer is already booked within this time range.']);
        }

        if ((int) $request->trainer_id !== 0 && !$request->filled('trainer_rate_per_hour')) {
            return back()
                ->withErrors(['trainer_rate_per_hour' => 'Please provide a rate per hour for the assigned trainer.'])
                ->withInput();
        }

        $trainerRate = (int) $request->trainer_id === 0
            ? null
            : $request->input('trainer_rate_per_hour');
        
        $data->name = $request->name;
        $data->slots = $request->slots;
        $data->class_start_date = $request->class_start_date;
        $data->class_end_date = $request->class_end_date;
        $data->series_start_date = $request->series_start_date;
        $data->series_end_date = $request->series_end_date;
        $data->class_start_time = $request->class_start_time;
        $data->class_end_time = $request->class_end_time;
        $data->recurring_days = json_encode($recurringDays);
        $data->isenabled = 1;
        $data->trainer_id = $request->trainer_id;
        $data->class_code = $request->class_code;
        $data->trainer_rate_per_hour = $trainerRate;
        $data->isadminapproved = $request->trainer_id == 0 ? 0 : 1;
    
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('images/schedules');
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
    
            $image->move($destinationPath, $imageName);
            $data->image = 'images/schedules/' . $imageName;
        }
        
        $data->save();

        $log = new Log;
        $log->message = $request->user()->first_name . " " . $request->user()->last_name . " has updated class successfully.";
        $log->role_name = 'Admin';
        $log->save();
        
        return redirect()->route('admin.gym-management.schedules')->with('success', 'Schedule updated successfully');
    }

    public function adminacceptance(Request $request)
    {
        $validated = $request->validate([
            'id'               => ['required', 'exists:schedules,id'],
            'isadminapproved'  => ['required', Rule::in([0, 1, 2])],
            'rejection_reason' => ['nullable', 'string'],
        ], [
            'rejection_reason.required_if' => 'Please provide a rejection reason when rejecting a class.',
        ]);

        $data = Schedule::findOrFail($validated['id']);
        $data->isadminapproved = (int) $validated['isadminapproved'];

        if ((int) $validated['isadminapproved'] === 2) {
            $data->rejection_reason = trim($validated['rejection_reason']);
        } else {
            $data->rejection_reason = null;
        }

        $data->save();

        return redirect()->route('admin.gym-management.schedules')->with('success', 'Schedule changed successfully');
    }
    
    public function delete(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:schedules,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        
        $user = $request->user();
    
        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }
        
        $data = Schedule::findOrFail($request->id);
        $scheduleName = $data->name ?? 'schedule';
        $scheduleLabel = sprintf('#%d (%s)', $data->id, $scheduleName);

        if ((int) $data->is_archieve === 1) {
            $data->delete();
            $message = 'Schedule deleted permanently';
            $this->logAdminActivity("deleted schedule {$scheduleLabel} permanently");
        } else {
            $data->is_archieve = 1;
            $data->save();
            $message = 'Schedule moved to archive';
            $this->logAdminActivity("archived schedule {$scheduleLabel}");
        }

        return redirect()->route('admin.gym-management.schedules')->with('success', $message);
    }

    public function restore(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:schedules,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $user = $request->user();

        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }

        $data = Schedule::findOrFail($request->id);
        $scheduleName = $data->name ?? 'schedule';
        $scheduleLabel = sprintf('#%d (%s)', $data->id, $scheduleName);

        if ((int) $data->is_archieve === 0) {
            return redirect()->route('admin.gym-management.schedules')->with('success', 'Schedule is already active');
        }

        $data->is_archieve = 0;
        $data->save();

        $this->logAdminActivity("restored schedule {$scheduleLabel}");

        return redirect()->route('admin.gym-management.schedules')->with('success', 'Schedule restored successfully');
    }
    
    public function rejectmessage(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:schedules,id',
                'rejection_reason' => 'required',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        
        $user = $request->user();
    
        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }
        
        $data = Schedule::findOrFail($request->id);
        $data->rejection_reason = $request->rejection_reason;
        $data->isadminapproved = 2;
        $data->save();

        return redirect()->route('admin.gym-management.schedules')->with('success', 'Schedule changed successfully');
    }

    /**
     * Normalize recurring_days payload into an array of weekday keys.
     */
    private function sanitizeRecurringDays($raw): array
    {
        $daysMeta = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
        }

        if (!is_array($raw)) {
            return [];
        }

        $filtered = array_values(array_intersect($daysMeta, $raw));

        return array_values(array_unique($filtered));
    }
    
    // public function print(Request $request)
    // {
    //     $request->validate([
    //         'created_start' => 'nullable|date',
    //         'created_end'   => 'nullable|date|after_or_equal:created_start',
    //     ]);
    
    //     $startInput = $request->input('created_start');
    //     $endInput   = $request->input('created_end');
    
    //     // Normalize range (all-day, inclusive)
    //     $start = $startInput ? \Carbon\Carbon::parse($startInput)->startOfDay() : null;
    //     $end   = $endInput   ? \Carbon\Carbon::parse($endInput)->endOfDay()   : null;
    
    //     if ($start && !$end) {
    //         $end = (clone $start)->endOfDay();
    //     } elseif (!$start && $end) {
    //         $start = \Carbon\Carbon::createFromTimestamp(0)->startOfDay(); // include everything until $end
    //     }
    
    //     $query = \App\Models\Schedule::with(['user:id,first_name,last_name'])
    //         ->withCount('user_schedules');
    
    //     if ($start && $end) {
    //         $query->whereBetween('created_at', [$start, $end]);
    //     }
    
    //     $data = $query->orderBy('created_at', 'desc')->get();
    
    //     // Name with created date range for clarity
    //     $suffix = '';
    //     if ($start && $end) {
    //         $suffix = '_' . $start->format('Ymd') . '_to_' . $end->format('Ymd');
    //     }
    //     $fileName = "classes{$suffix}_" . date('Y-m-d') . ".csv";
    
    //     header("Content-Type: text/csv");
    //     header("Content-Disposition: attachment; filename=\"$fileName\"");
    //     header("Pragma: no-cache");
    //     header("Expires: 0");
    
    //     $output = fopen('php://output', 'w');
    
    //     fputcsv($output, [
    //         'ID', 'Class Name', 'Class Code', 'Trainer', 'Slots', 'Total Members Enrolled',
    //         'Class Start Date and Time', 'Class End Date and Time',
    //         'Status', 'Categorization', 'Admin Acceptance', 'Reject Reason',
    //         'Created Date', 'Updated Date',
    //     ]);
    
    //     $now = now();
    
    //     foreach ($data as $item) {
    //         $start_date = \Carbon\Carbon::parse($item->class_start_date);
    //         $end_date   = \Carbon\Carbon::parse($item->class_end_date);
    
    //         $status = 'Past';
    //         if ($now->lt($start_date)) {
    //             $status = 'Future';
    //         } elseif ($now->between($start_date, $end_date)) {
    //             $status = 'Present';
    //         }
    
    //         fputcsv($output, [
    //             $item->id,
    //             $item->name,
    //             $item->class_code,
    //             $item->trainer_id == 0
    //                 ? 'No Trainer for now'
    //                 : optional($item->user)->first_name . ' ' . optional($item->user)->last_name,
    //             $item->slots,
    //             $item->user_schedules_count, // real count
    //             $item->class_start_date,
    //             $item->class_end_date,
    //             $item->isenabled ? 'Enabled' : 'Disabled',
    //             $status,
    //             $item->isadminapproved == 0 ? 'Pending' :
    //                 ($item->isadminapproved == 1 ? 'Approve' :
    //                 ($item->isadminapproved == 2 ? 'Reject' : '')),
    //             $item->rejection_reason,
    //             $item->created_at,
    //             $item->updated_at,
    //         ]);
    //     }
    
    //     fclose($output);
    //     exit;
    // }    
    
    public function print(Request $request)
    {
        $request->validate([
            'created_start' => 'nullable|date',
            'created_end'   => 'nullable|date|after_or_equal:created_start',
            'search_column' => 'nullable|string',
            'name'          => 'nullable|string|max:255',
            'status'        => 'nullable|in:all,upcoming,active,completed',
        ]);

        $startInput   = $request->input('created_start');
        $endInput     = $request->input('created_end');
        $search       = $request->input('name');
        $searchColumn = $request->input('search_column');
        $status       = $request->input('status', 'all');

        if (empty($status)) {
            $status = 'all';
        }

        // Normalize range (all-day, inclusive)
        $start = $startInput ? \Carbon\Carbon::parse($startInput)->startOfDay() : null;
        $end   = $endInput   ? \Carbon\Carbon::parse($endInput)->endOfDay()   : null;

        if ($start && !$end) {
            $end = (clone $start)->endOfDay();
        } elseif (!$start && $end) {
            $start = \Carbon\Carbon::createFromTimestamp(0)->startOfDay(); // include everything until $end
        }

        // Allowed columns
        $allowedColumns = [
            'id', 'name', 'class_code', 'trainer_name', 'slots',
            'class_start_date', 'class_end_date', 'isadminapproved',
            'rejection_reason', 'created_at',
        ];
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }

        // Default date filter column
        $dateColumns = ['class_start_date', 'class_end_date', 'created_at'];
        $rangeColumn = in_array($searchColumn, $dateColumns, true) ? $searchColumn : 'created_at';

        $now = Carbon::now();

        $query = \App\Models\Schedule::with(['user:id,first_name,last_name'])
            ->withCount('user_schedules')
            ->when($search && $searchColumn, function ($query) use ($search, $searchColumn) {
                if ($searchColumn === 'trainer_name') {
                    $likeSearch = "%{$search}%";

                    return $query->whereHas('user', function ($userQuery) use ($likeSearch) {
                        $userQuery->where(function ($nameQuery) use ($likeSearch) {
                            $nameQuery->whereRaw(
                                "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                                [$likeSearch]
                            )->orWhere('first_name', 'like', $likeSearch)
                             ->orWhere('last_name', 'like', $likeSearch);
                        });
                    });
                }

                $exactColumns = ['id', 'slots', 'isadminapproved'];
                if (in_array($searchColumn, $exactColumns, true)) {
                    return $query->where($searchColumn, $search);
                }
                return $query->where($searchColumn, 'like', "%{$search}%");
            })
            ->when($start || $end, function ($query) use ($start, $end, $rangeColumn) {
                if ($start && $end) {
                    $query->whereBetween($rangeColumn, [$start, $end]);
                } elseif ($start) {
                    $query->whereDate($rangeColumn, '>=', $start->toDateString());
                } elseif ($end) {
                    $query->whereDate($rangeColumn, '<=', $end->toDateString());
                }
            })
            ->when($status !== 'all', function ($query) use ($status, $now) {
                if ($status === 'upcoming') {
                    return $query->where('class_start_date', '>', $now);
                }

                if ($status === 'active') {
                    return $query->where('class_start_date', '<=', $now)
                        ->where('class_end_date', '>=', $now);
                }

                if ($status === 'completed') {
                    return $query->where('class_end_date', '<', $now);
                }

                return $query;
            })
            ->orderBy('created_at', 'desc');

        $data = $query->get();

        // ------------------- Word export code (unchanged) -------------------
        $suffix = '';
        if ($start && $end) {
            $suffix = '_' . $start->format('Ymd') . '_to_' . $end->format('Ymd');
        }
        $fileName = "classes{$suffix}_" . date('Y-m-d') . ".docx";

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

        $title = 'Classes';
        if ($start && $end) {
            $title .= ' — ' . $start->format('M d, Y') . ' to ' . $end->format('M d, Y');
        }
        $section->addText($title, ['bold' => true, 'size' => 16]);
        $section->addText('Generated: ' . now()->format('M d, Y H:i'));
        $section->addTextBreak(1);

        // Table setup
        $tableStyle = [
            'borderColor' => '777777',
            'borderSize'  => 6,
            'cellMargin'  => 80,
        ];
        $firstRowStyle = ['bgColor' => 'DDDDDD'];
        $phpWord->addTableStyle('ClassesTable', $tableStyle, $firstRowStyle);
        $table = $section->addTable('ClassesTable');

        // Headers
        $headers = [
            'ID', 'Class Name', 'Class Code', 'Trainer', 'Trainer Rate Per Hour', 'Slots', 'Total Members Enrolled',
            'Class Start Date and Time', 'Class End Date and Time',
            'Status', 'Categorization', 'Admin Acceptance', 'Reject Reason',
            'Created Date', 'Updated Date',
        ];
        $headerRow = $table->addRow();
        foreach ($headers as $h) {
            $headerRow->addCell()->addText($h, ['bold' => true]);
        }

        // Rows
        $now = now();
        foreach ($data as $item) {
            $row = $table->addRow();

            $start_date = \Carbon\Carbon::parse($item->class_start_date);
            $end_date   = \Carbon\Carbon::parse($item->class_end_date);

            $status = 'Past';
            if ($now->lt($start_date)) {
                $status = 'Future';
            } elseif ($now->between($start_date, $end_date)) {
                $status = 'Present';
            }

            $adminAcceptance = $item->isadminapproved == 0 ? 'Pending' :
                ($item->isadminapproved == 1 ? 'Approve' :
                ($item->isadminapproved == 2 ? 'Reject' : ''));

            $cells = [
                $item->id,
                (string) $item->name,
                (string) $item->class_code,
                $item->trainer_id == 0
                    ? 'No Trainer for now'
                    : trim((optional($item->user)->first_name ?? '') . ' ' . (optional($item->user)->last_name ?? '')),
                $item->trainer_rate_per_hour !== null
                    ? number_format((float) $item->trainer_rate_per_hour, 2)
                    : '',
                (string) $item->slots,
                (string) $item->user_schedules_count,
                $item->class_start_date,
                $item->class_end_date,
                $item->isenabled ? 'Enabled' : 'Disabled',
                $status,
                $adminAcceptance,
                (string) $item->rejection_reason,
                $item->created_at,
                $item->updated_at
            ];

            foreach ($cells as $val) {
                $row->addCell()->addText((string) $val);
            }
        }

        // Save + return
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
