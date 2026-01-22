<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\Schedule;
use App\Models\UserSchedule;
use App\Models\Attendance;
use App\Models\Attendance2;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class MemberDataController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search_column' => 'nullable|string',
            'name' => 'nullable|string|max:255',
            'start_date'    => 'nullable|date_format:Y-m-d',
            'end_date'      => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'membership_status' => 'nullable|in:all,with,none',
        ]);
        
        $search        = $request->input('name');
        $hasSearch     = $request->filled('name');
        $searchColumn  = $request->input('search_column');
        $startDate     = $request->input('start_date');
        $endDate       = $request->input('end_date');
        $membershipStatus = $request->input('membership_status', 'all');
        if (empty($membershipStatus)) {
            $membershipStatus = 'all';
        }

        $startDateObj = $startDate ? Carbon::createFromFormat('Y-m-d', $startDate) : null;
        $endDateObj   = $endDate   ? Carbon::createFromFormat('Y-m-d', $endDate)   : null;

        $allowed_columns = [
            'id', 'user_code', 'name', 'phone_number', 'email', 'created_at',
            'updated_at', 'created_by', 'membership_name', 'expiration_at',
        ];

        if (!in_array($searchColumn, $allowed_columns, true)) {
            $searchColumn = null;
        }
        
        $dateColumns = ['created_at', 'updated_at', 'expiration_at'];
        $rangeColumn = in_array($searchColumn, $dateColumns, true) ? $searchColumn : 'created_at';

        $current_time = Carbon::now();
        $hasPaymentArchiveColumn = Schema::hasColumn('membership_payments', 'is_archive');
        $memberships = Membership::where('is_archive', 0)->get();

        $activeMembersBase = User::where('role_id', 3)->where('is_archive', 0);
        $totalMembers = (clone $activeMembersBase)->count();
        $withMembershipCount = (clone $activeMembersBase)
            ->whereHas('membershipPayments', function ($query) use ($current_time, $hasPaymentArchiveColumn) {
                $query->where('isapproved', 1)
                    ->where('expiration_at', '>=', $current_time);

                if ($hasPaymentArchiveColumn) {
                    $query->where('is_archive', 0);
                }
            })
            ->count();
        $statusTallies = [
            'all' => $totalMembers,
            'with' => $withMembershipCount,
            'none' => max($totalMembers - $withMembershipCount, 0),
        ];

        $baseQuery = User::where('role_id', 3)
            ->with([
                'membershipPayments' => function ($q) use ($current_time, $hasPaymentArchiveColumn) {
                    $q->where('isapproved', 1)
                        ->where('expiration_at', '>=', $current_time);

                    if ($hasPaymentArchiveColumn) {
                        $q->where('is_archive', 0);
                    }

                    $q->orderBy('created_at', 'desc');
                },
                'membershipPayments.membership',
            ])
            ->when($hasSearch, function ($query) use ($search, $current_time, $hasPaymentArchiveColumn) {
                return $this->applyMemberSearch($query, $search, $current_time, $hasPaymentArchiveColumn);
            })
            ->when($startDateObj || $endDateObj, function ($query) use ($startDateObj, $endDateObj, $rangeColumn, $current_time, $hasPaymentArchiveColumn) {
                $start = $startDateObj ? $startDateObj->toDateString() : null;
                $end   = $endDateObj ? $endDateObj->toDateString() : null;

                if ($rangeColumn === 'expiration_at') {
                    return $query->whereHas('membershipPayments', function ($q) use ($start, $end, $current_time, $hasPaymentArchiveColumn) {
                        $q->where('isapproved', 1)
                            ->where('expiration_at', '>=', $current_time);

                        if ($start) {
                            $q->whereDate('expiration_at', '>=', $start);
                        }
                        if ($end) {
                            $q->whereDate('expiration_at', '<=', $end);
                        }

                        if ($hasPaymentArchiveColumn) {
                            $q->where('is_archive', 0);
                        }
                    });
                }

                if ($start) {
                    $query->whereDate($rangeColumn, '>=', $start);
                }
                if ($end) {
                    $query->whereDate($rangeColumn, '<=', $end);
                }
            })
            ->when($membershipStatus !== 'all', function ($query) use ($membershipStatus, $current_time, $hasPaymentArchiveColumn) {
                if ($membershipStatus === 'with') {
                    return $query->whereHas('membershipPayments', function ($q) use ($current_time, $hasPaymentArchiveColumn) {
                        $q->where('isapproved', 1)
                            ->where('expiration_at', '>=', $current_time);

                        if ($hasPaymentArchiveColumn) {
                            $q->where('is_archive', 0);
                        }
                    });
                }

                if ($membershipStatus === 'none') {
                    return $query->whereDoesntHave('membershipPayments', function ($q) use ($current_time, $hasPaymentArchiveColumn) {
                        $q->where('isapproved', 1)
                            ->where('expiration_at', '>=', $current_time);

                        if ($hasPaymentArchiveColumn) {
                            $q->where('is_archive', 0);
                        }
                    });
                }

                return $query;
            })
            ->orderByDesc('created_at');

        $queryParamsWithoutArchivePage = $request->except('archive_page');
        $queryParamsWithoutMainPage = $request->except('page');

        $printAllActive = (clone $baseQuery)
            ->where('is_archive', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $gym_members = (clone $baseQuery)
            ->where('is_archive', 0)
            ->paginate(10)
            ->appends($queryParamsWithoutArchivePage);

        $printAllArchived = (clone $baseQuery)
            ->where('is_archive', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        $archivedData = (clone $baseQuery)
            ->where('is_archive', 1)
            ->paginate(10, ['*'], 'archive_page')
            ->appends($queryParamsWithoutMainPage);

        return view('admin.gymmanagement.memberdata', compact('gym_members', 'archivedData', 'current_time', 'statusTallies', 'printAllActive', 'printAllArchived', 'memberships'));
    }
    
    public function view(Request $request, $id)
    {
        $hasPaymentArchiveColumn = Schema::hasColumn('membership_payments', 'is_archive');

        $gym_member = User::where('role_id', 3)
            ->with([
                'role',
                'membershipPayments' => function ($query) use ($hasPaymentArchiveColumn) {
                    if ($hasPaymentArchiveColumn) {
                        $query->where('is_archive', 0);
                    }

                    $query->orderByDesc('created_at');
                },
                'membershipPayments.membership',
            ])
            ->findOrFail($id);

        $search = trim((string) $request->input('search', ''));

        $userSchedulesQuery = $gym_member->userSchedules()
            ->with(['schedule.user'])
            ->orderByDesc('created_at');

        if ($search !== '') {
            $userSchedulesQuery->whereHas('schedule', function ($query) use ($search) {
                $like = "%{$search}%";
                $query->where('name', 'like', $like)
                    ->orWhere('class_code', 'like', $like)
                    ->orWhereHas('user', function ($trainerQuery) use ($like) {
                        $trainerQuery->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$like]);
                    });
            });
        }

        $userSchedules = $userSchedulesQuery
            ->paginate(10)
            ->withQueryString();

        return view('admin.gymmanagement.memberdata-view', [
            'gym_member' => $gym_member,
            'userSchedules' => $userSchedules,
            'search' => $search,
        ]);
    }

    public function create()
    {
        $memberships = Membership::where('is_archive', 0)->get();
        $classes = Schedule::where('is_archieve', 0)->get();
        
        return view('admin.gymmanagement.memberdata-create', compact('memberships', 'classes'));
    }

    public function edit($id)
    {
        $gym_member = User::where('role_id', 3)->findOrFail($id);
        $memberships = Membership::where('is_archive', 0)->get();
        $current_time = Carbon::now();
        $hasPaymentArchiveColumn = Schema::hasColumn('membership_payments', 'is_archive');
        
        $gym_member_membership = optional($gym_member->membershipPayments()
            ->where('isapproved', 1)
            ->where('expiration_at', '>=', $current_time)
            ->when($hasPaymentArchiveColumn, function ($q) {
                $q->where('is_archive', 0);
            })
            ->orderBy('created_at', 'desc')
            ->first()
        )->membership;
        
        return view('admin.gymmanagement.memberdata-edit', compact('gym_member', 'memberships', 'gym_member_membership'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'profile_picture' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
            'captured_profile_picture' => 'nullable|string',
            'first_name' => 'required',
            'last_name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
            'membership_id' => 'required',
            'class_id' => 'nullable|exists:schedules,id'
        ]);

        $users = new User;
        $users->role_id = 3;
        $users->status_id = 2;
        $users->first_name = $validatedData['first_name'];
        $users->last_name = $validatedData['last_name'];
        $users->address = $validatedData['address'];
        $users->phone_number = $validatedData['phone_number'];
        $users->email = $validatedData['email'];
        $users->password = bcrypt($validatedData['password']);
        $users->created_by = $validatedData['first_name'] . " " .  $validatedData['last_name'];

        $destinationPath = public_path('uploads');
        if (!is_dir($destinationPath)) {
            @mkdir($destinationPath, 0755, true);
        }
        $profilePicturePath = null;
        
        if ($request->filled('captured_profile_picture')) {
            $profilePicturePath = $this->storeCapturedImage($request->input('captured_profile_picture'), $destinationPath);
            if ($profilePicturePath === null) {
                return redirect()->back()
                    ->withErrors(['profile_picture' => 'Captured image is invalid or too large. Please retake the photo.'])
                    ->withInput();
            }
        } elseif ($request->hasFile('profile_picture')) {
            $profilePicture = $request->file('profile_picture');
            $profilePictureUrlName = time() . '_image.' . $profilePicture->getClientOriginalExtension();
            $profilePicture->move($destinationPath, $profilePictureUrlName);
            $profilePicturePath = 'uploads/' . $profilePictureUrlName;
        }
        
        if ($profilePicturePath) {
            $users->profile_picture = $profilePicturePath;
        }
        
        $users->save();

        $prefix = match ((int) $users->role_id) {
            1 => 'A',
            2 => 'S',
            3 => 'M',
            4 => 'SA',
            5 => 'T',
            default => '',
        };
        $users->user_code = $prefix . $users->id;
        $users->save();

        $membership = Membership::find($validatedData['membership_id']);
        $data = new MembershipPayment;
        $data->user_id = $users->id;
        $data->membership_id = $validatedData['membership_id'];
        $data->isapproved = 1;
        $data->proof_of_payment = 'blank_for_now';
        $data->created_by = $request->user()->first_name . " " .  $request->user()->last_name;
        
        $currentDate = new \DateTime();
        if ($membership->year) {
            $currentDate->modify("+{$membership->year} years");
        }
        if ($membership->month) {
            $currentDate->modify("+{$membership->month} months");
        }
        if ($membership->week) {
            $currentDate->modify("+{$membership->week} weeks");
        }
        if ($membership->day) {
            $currentDate->modify("+{$membership->day} days");
        }
        $data->expiration_at = $currentDate;

        $data->save();

        // Optionally enroll to selected upcoming class (walk-in)
        if (!empty($validatedData['class_id'])) {
            $schedule = Schedule::find($validatedData['class_id']);
            if ($schedule) {
                $currentCount = UserSchedule::where('schedule_id', $schedule->id)
                    ->whereHas('user', function ($query) {
                        $query->where('is_archive', 0);
                    })
                    ->count();
                if (!isset($schedule->slots) || $currentCount < (int) $schedule->slots) {
                    $enroll = new UserSchedule();
                    $enroll->user_id = $users->id;
                    $enroll->schedule_id = $schedule->id;
                    $enroll->save();
                }
            }
        }

        // Redirect to printable walk-in payment receipt
        return redirect()->route('admin.staff-account-management.membership-payments.receipt', ['id' => $data->id])->with('success', 'Gym member added successfully');
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'membership_id' => 'required'
        ]);
        
        // Block issuing a new membership if the user already has an active approved membership
        $now = Carbon::now();
        $hasPaymentArchiveColumn = Schema::hasColumn('membership_payments', 'is_archive');
        $existingActive = MembershipPayment::where('user_id', $id)
            ->where('isapproved', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('expiration_at')->orWhere('expiration_at', '>=', $now);
            })
            ->exists();

        if ($existingActive) {
            return redirect()->back()->withErrors(['membership_id' => 'User already has an active membership.'])->withInput();
        }

        if ($validatedData['membership_id'] == 0) {
            $membership = (object) ['year' => 0, 'month' => 0, 'week' => 0, 'day' => 0]; 
            $validatedData['membership_id'] = null;
        } else {
            $membership = Membership::find($validatedData['membership_id']);
        }
    
        $gym_member = User::where('role_id', 3)->findOrFail($id);
        $existingMemberships = MembershipPayment::where('user_id', $gym_member->id)->get();
        foreach ($existingMemberships as $existingMembership) {
            $existingMembership->isapproved = 0;
            $existingMembership->save();
        }
        
        $data = new MembershipPayment;
        $data->user_id = $gym_member->id;
        $data->membership_id = $validatedData['membership_id'];
        $data->isapproved = 1;
        $data->proof_of_payment = 'blank_for_now';
        $data->created_by = $request->user()->first_name . " " .  $request->user()->last_name;
        
        $currentDate = new \DateTime();
        if ($membership->year) {
            $currentDate->modify("+{$membership->year} years");
        }
        if ($membership->month) {
            $currentDate->modify("+{$membership->month} months");
        }
        if ($membership->week) {
            $currentDate->modify("+{$membership->week} weeks");
        }
        if ($membership->day) {
            $currentDate->modify("+{$membership->day} days");
        }
        $data->expiration_at = $currentDate;
        
        $data->save();
        

        return redirect()->route('admin.gym-management.members')->with('success', 'Gym member updated successfully');
    }
    
    public function delete(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:users,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        
        $user = $request->user();
    
        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }
        
        $data = User::where('role_id', 3)->findOrFail($request->id);
        $hasPaymentArchiveColumn = Schema::hasColumn('membership_payments', 'is_archive');
        $memberName = trim(sprintf('%s %s', $data->first_name ?? '', $data->last_name ?? ''));
        $memberLabel = $memberName !== ''
            ? sprintf('#%d (%s)', $data->id, $memberName)
            : sprintf('#%d (%s)', $data->id, $data->email ?? 'member');

        if ((int) $data->is_archive === 1) {
            DB::transaction(function () use ($data) {
                MembershipPayment::where('user_id', $data->id)->delete();
                UserSchedule::where('user_id', $data->id)->delete();
                Attendance::where('user_id', $data->id)->delete();
                Attendance2::where('user_id', $data->id)->delete();

                $data->delete();
            });

            $message = 'Gym member deleted permanently';
            $this->logAdminActivity("deleted gym member {$memberLabel} permanently");
        } else {
            DB::transaction(function () use ($data, $hasPaymentArchiveColumn) {
                if ($hasPaymentArchiveColumn) {
                    MembershipPayment::where('user_id', $data->id)->update(['is_archive' => 1]);
                }

                $data->is_archive = 1;
                $data->save();
            });

            $message = 'Gym member moved to archive';
            $this->logAdminActivity("archived gym member {$memberLabel}");
        }

        return redirect()->route('admin.gym-management.members')->with('success', $message);
    }
    
    public function restore(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:users,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $user = $request->user();

        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }

        $data = User::where('role_id', 3)->findOrFail($request->id);
        $hasPaymentArchiveColumn = Schema::hasColumn('membership_payments', 'is_archive');
        $memberName = trim(sprintf('%s %s', $data->first_name ?? '', $data->last_name ?? ''));
        $memberLabel = $memberName !== ''
            ? sprintf('#%d (%s)', $data->id, $memberName)
            : sprintf('#%d (%s)', $data->id, $data->email ?? 'member');

        if ((int) $data->is_archive === 0) {
            return redirect()->route('admin.gym-management.members')->with('success', 'Gym member is already active');
        }

        DB::transaction(function () use ($data, $hasPaymentArchiveColumn) {
            $data->is_archive = 0;
            $data->save();

            if ($hasPaymentArchiveColumn) {
                MembershipPayment::where('user_id', $data->id)->update(['is_archive' => 0]);
            }
        });

        $this->logAdminActivity("restored gym member {$memberLabel}");

        return redirect()->route('admin.gym-management.members')->with('success', 'Gym member restored successfully');
    }
    

    public function print(Request $request)
    {
        $request->validate([
            'created_start' => 'nullable|date',
            'created_end'   => 'nullable|date|after_or_equal:created_start',
            'search_column' => 'nullable|string',
            'name'          => 'nullable|string|max:255',
            'membership_status' => 'nullable|in:all,with,none',
        ]);

        $startInput   = $request->input('created_start');
        $endInput     = $request->input('created_end');
        $search       = $request->input('name');
        $hasSearch    = $request->filled('name');
        $searchColumn = $request->input('search_column');
        $membershipStatus = $request->input('membership_status', 'all');

        if (empty($membershipStatus)) {
            $membershipStatus = 'all';
        }

        $start = $startInput ? Carbon::parse($startInput)->startOfDay() : null;
        $end   = $endInput   ? Carbon::parse($endInput)->endOfDay()   : null;

        if ($start && !$end) {
            $end = (clone $start)->endOfDay();
        } elseif (!$start && $end) {
            $start = Carbon::createFromTimestamp(0)->startOfDay();
        }

        $now = Carbon::now();
        $hasPaymentArchiveColumn = Schema::hasColumn('membership_payments', 'is_archive');

        $allowedColumns = [
            'id', 'user_code', 'name', 'first_name', 'last_name', 'phone_number', 'email', 'created_at', 'updated_at',
            'created_by', 'membership_name', 'expiration_at',
        ];
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }

        $dateColumns = ['created_at', 'updated_at', 'expiration_at'];
        $rangeColumn = in_array($searchColumn, $dateColumns, true) ? $searchColumn : 'created_at';

        $query = User::where('role_id', 3)
            ->with([
                'membershipPayments' => function ($q) use ($now, $hasPaymentArchiveColumn) {
                    $q->where('isapproved', 1)
                        ->where('expiration_at', '>=', $now);

                    if ($hasPaymentArchiveColumn) {
                        $q->where('is_archive', 0);
                    }

                    $q->orderBy('created_at', 'desc');
                },
                'membershipPayments.membership',
            ])
            ->when($hasSearch, function ($q) use ($search, $now, $hasPaymentArchiveColumn) {
                return $this->applyMemberSearch($q, $search, $now, $hasPaymentArchiveColumn);
            })
            ->when($start || $end, function ($q) use ($start, $end, $rangeColumn, $now, $hasPaymentArchiveColumn) {
                if ($rangeColumn === 'expiration_at') {
                    return $q->whereHas('membershipPayments', function ($inner) use ($start, $end, $now, $hasPaymentArchiveColumn) {
                        $inner->where('isapproved', 1)
                            ->where('expiration_at', '>=', $now);

                        if ($start) {
                            $inner->whereDate('expiration_at', '>=', $start->toDateString());
                        }
                        if ($end) {
                            $inner->whereDate('expiration_at', '<=', $end->toDateString());
                        }

                        if ($hasPaymentArchiveColumn) {
                            $inner->where('is_archive', 0);
                        }
                    });
                }

                if ($start && $end) {
                    $q->whereBetween($rangeColumn, [$start, $end]);
                } elseif ($start) {
                    $q->whereDate($rangeColumn, '>=', $start->toDateString());
                } elseif ($end) {
                    $q->whereDate($rangeColumn, '<=', $end->toDateString());
                }
            })
            ->when($membershipStatus !== 'all', function ($q) use ($membershipStatus, $now, $hasPaymentArchiveColumn) {
                if ($membershipStatus === 'with') {
                    return $q->whereHas('membershipPayments', function ($inner) use ($now, $hasPaymentArchiveColumn) {
                        $inner->where('isapproved', 1)
                            ->where('expiration_at', '>=', $now);

                        if ($hasPaymentArchiveColumn) {
                            $inner->where('is_archive', 0);
                        }
                    });
                }

                if ($membershipStatus === 'none') {
                    return $q->whereDoesntHave('membershipPayments', function ($inner) use ($now, $hasPaymentArchiveColumn) {
                        $inner->where('isapproved', 1)
                            ->where('expiration_at', '>=', $now);

                        if ($hasPaymentArchiveColumn) {
                            $inner->where('is_archive', 0);
                        }
                    });
                }

                return $q;
            })
            ->orderBy('created_at', 'desc');

        $data = $query->get();

        $suffix = '';
        if ($start && $end) {
            $suffix = '_' . $start->format('Ymd') . '_to_' . $end->format('Ymd');
        }
        $fileName = "members_data{$suffix}_" . date('Y-m-d') . ".docx";

        $phpWord = new PhpWord();
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

        $title = 'Members Data';
        if ($start && $end) {
            $title .= ' — ' . $start->format('M d, Y') . ' to ' . $end->format('M d, Y');
        }
        $section->addText($title, ['bold' => true, 'size' => 16]);
        $section->addText('Generated: ' . now()->format('M d, Y H:i'));
        $section->addTextBreak(1);

        $tableStyle = [
            'borderColor' => '777777',
            'borderSize'  => 6,
            'cellMargin'  => 80,
        ];
        $firstRowStyle = ['bgColor' => 'DDDDDD'];
        $phpWord->addTableStyle('MembersTable', $tableStyle, $firstRowStyle);
        $table = $section->addTable('MembersTable');

        $headers = [
            'ID',
            'Membership Name',
            'Membership Expiration Date',
            'Name',
            'Phone Number',
            'Email',
            'Created At',
            'Updated At',
        ];
        $headerRow = $table->addRow();
        foreach ($headers as $header) {
            $headerRow->addCell()->addText($header, ['bold' => true]);
        }

        foreach ($data as $user) {
            $membership = $user->membershipPayments->first();

            $membershipName = optional(optional($membership)->membership)->name ?? 'No Membership';
            $expirationAt   = optional($membership)->expiration_at;
            $expirationText = $expirationAt ? (string) $expirationAt : 'No Expiration Date';
            $fullName       = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            $row = $table->addRow();
            $row->addCell()->addText((string) $user->id);
            $row->addCell()->addText((string) $membershipName);
            $row->addCell()->addText((string) $expirationText);
            $row->addCell()->addText((string) $fullName);
            $row->addCell()->addText((string) ($user->phone_number ?? ''));
            $row->addCell()->addText((string) ($user->email ?? ''));
            $row->addCell()->addText((string) $user->created_at);
            $row->addCell()->addText((string) $user->updated_at);
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

    /**
     * Apply the all-in-one search across member list columns.
     */
    private function applyMemberSearch($query, string $search, Carbon $currentTime, bool $hasPaymentArchiveColumn)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $like = '%' . $search . '%';
        $lowerSearch = strtolower($search);
        $numericSearch = is_numeric($search) ? (int) $search : null;
        $applyActiveMembershipScope = function ($membershipQuery) use ($currentTime, $hasPaymentArchiveColumn) {
            $membershipQuery->where('isapproved', 1)
                ->where('expiration_at', '>=', $currentTime);

            if ($hasPaymentArchiveColumn) {
                $membershipQuery->where('is_archive', 0);
            }
        };

        return $query->where(function ($query) use ($like, $lowerSearch, $numericSearch, $applyActiveMembershipScope) {
            if ($numericSearch !== null) {
                $query->orWhere('id', $numericSearch);
            }

            $query->orWhere('user_code', 'like', $like)
                ->orWhere('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$like])
                ->orWhere('phone_number', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('created_at', 'like', $like)
                ->orWhere('updated_at', 'like', $like);

            $query->orWhereHas('membershipPayments', function ($membershipQuery) use ($like, $applyActiveMembershipScope) {
                $applyActiveMembershipScope($membershipQuery);

                $membershipQuery->where(function ($inner) use ($like) {
                    $inner->where('expiration_at', 'like', $like)
                        ->orWhere('created_by', 'like', $like)
                        ->orWhereHas('membership', function ($membershipQuery) use ($like) {
                            $membershipQuery->where('name', 'like', $like);
                        });
                });
            });

            if (
                strpos($lowerSearch, 'no membership') !== false
                || strpos($lowerSearch, 'no active') !== false
                || strpos($lowerSearch, 'none') !== false
                || strpos($lowerSearch, 'without') !== false
            ) {
                $query->orWhereDoesntHave('membershipPayments', function ($membershipQuery) use ($applyActiveMembershipScope) {
                    $applyActiveMembershipScope($membershipQuery);
                });
            }

            if (strpos($lowerSearch, 'active') !== false || strpos($lowerSearch, 'with membership') !== false) {
                $query->orWhereHas('membershipPayments', function ($membershipQuery) use ($applyActiveMembershipScope) {
                    $applyActiveMembershipScope($membershipQuery);
                });
            }

            if (strpos($lowerSearch, 'pending') !== false) {
                $query->orWhereDoesntHave('membershipPayments', function ($membershipQuery) use ($applyActiveMembershipScope) {
                    $applyActiveMembershipScope($membershipQuery);
                });
            }
        });
    }

    protected function storeCapturedImage(?string $dataUri, string $destinationPath): ?string
    {
        if (empty($dataUri)) {
            return null;
        }

        if (!preg_match('/^data:image\/(jpe?g|png);base64,/', $dataUri, $matches)) {
            return null;
        }

        $base64Data = substr($dataUri, strpos($dataUri, ',') + 1);
        $binary = base64_decode($base64Data, true);
        if ($binary === false) {
            return null;
        }

        $sizeInKb = strlen($binary) / 1024;
        if ($sizeInKb > 2048) { // ~2MB limit to mirror file validation
            return null;
        }

        if (!is_dir($destinationPath)) {
            @mkdir($destinationPath, 0755, true);
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $fileName = uniqid('profile_', true) . '.' . $extension;
        $fullPath = $destinationPath . DIRECTORY_SEPARATOR . $fileName;

        file_put_contents($fullPath, $binary);

        return 'uploads/' . $fileName;
    }
}
