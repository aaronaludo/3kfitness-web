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
        $searchColumn  = $request->input('search_column');
        $startDate     = $request->input('start_date');
        $endDate       = $request->input('end_date');
        $membershipStatus = $request->input('membership_status', 'all');
        if (empty($membershipStatus)) {
            $membershipStatus = 'all';
        }
        
        $allowed_columns = [
            'id', 'name', 'phone_number', 'email', 'created_at',
            'updated_at'
        ];
        
        if (!in_array($searchColumn, $allowed_columns)) {
            $searchColumn = null;
        }
        
        $dateColumns = ['created_at'];
        $rangeColumn = in_array($searchColumn, $dateColumns, true) ? $searchColumn : 'created_at';

        $current_time = Carbon::now();

        $activeMembersBase = User::where('role_id', 3)->where('is_archive', 0);
        $totalMembers = (clone $activeMembersBase)->count();
        $withMembershipCount = (clone $activeMembersBase)
            ->whereHas('membershipPayments', function ($query) use ($current_time) {
                $query->where('isapproved', 1)
                    ->where('expiration_at', '>=', $current_time);
            })
            ->count();
        $statusTallies = [
            'all' => $totalMembers,
            'with' => $withMembershipCount,
            'none' => max($totalMembers - $withMembershipCount, 0),
        ];

        $baseQuery = User::where('role_id', 3)
            ->with([
                'membershipPayments' => function ($q) use ($current_time) {
                    $q->where('isapproved', 1)
                        ->where('expiration_at', '>=', $current_time)
                        ->orderBy('created_at', 'desc');
                },
                'membershipPayments.membership',
            ])
            ->when($search && $searchColumn, function ($query) use ($search, $searchColumn) {
                if ($searchColumn === 'name') {
                    return $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
                    });
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
            ->when($membershipStatus !== 'all', function ($query) use ($membershipStatus, $current_time) {
                if ($membershipStatus === 'with') {
                    return $query->whereHas('membershipPayments', function ($q) use ($current_time) {
                        $q->where('isapproved', 1)
                            ->where('expiration_at', '>=', $current_time);
                    });
                }

                if ($membershipStatus === 'none') {
                    return $query->whereDoesntHave('membershipPayments', function ($q) use ($current_time) {
                        $q->where('isapproved', 1)
                            ->where('expiration_at', '>=', $current_time);
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

        return view('admin.gymmanagement.memberdata', compact('gym_members', 'archivedData', 'current_time', 'statusTallies', 'printAllActive', 'printAllArchived'));
    }
    
    public function view($id)
    {
        $gym_member = User::where('role_id', 3)->findOrFail($id);

        return view('admin.gymmanagement.memberdata-view', compact('gym_member'));
    }

    public function create()
    {
        $memberships = Membership::all();
        $classes = Schedule::all();
        
        return view('admin.gymmanagement.memberdata-create', compact('memberships', 'classes'));
    }

    public function edit($id)
    {
        $gym_member = User::where('role_id', 3)->findOrFail($id);
        $memberships = Membership::all();
        $current_time = Carbon::now();
        
        $gym_member_membership = optional($gym_member->membershipPayments()
            ->where('isapproved', 1)
            ->where('expiration_at', '>=', $current_time)
            ->orderBy('created_at', 'desc')
            ->first()
        )->membership;
        
        return view('admin.gymmanagement.memberdata-edit', compact('gym_member', 'memberships', 'gym_member_membership'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'profile_picture' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
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
        
        if ($request->hasFile('profile_picture')) {
            $profilePicture = $request->file('profile_picture');
            $profilePictureUrlName = time() . '_image.' . $profilePicture->getClientOriginalExtension();
            $profilePicture->move($destinationPath, $profilePictureUrlName);
            $users->profile_picture = 'uploads/' . $profilePictureUrlName;
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
        $data->expiration_at = $currentDate;

        $data->save();

        // Optionally enroll to selected upcoming class (walk-in)
        if (!empty($validatedData['class_id'])) {
            $schedule = Schedule::find($validatedData['class_id']);
            if ($schedule) {
                $currentCount = UserSchedule::where('schedule_id', $schedule->id)->count();
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
            $membership = (object) ['year' => 0, 'month' => 0, 'week' => 0]; 
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
            $data->is_archive = 1;
            $data->save();
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
        $memberName = trim(sprintf('%s %s', $data->first_name ?? '', $data->last_name ?? ''));
        $memberLabel = $memberName !== ''
            ? sprintf('#%d (%s)', $data->id, $memberName)
            : sprintf('#%d (%s)', $data->id, $data->email ?? 'member');

        if ((int) $data->is_archive === 0) {
            return redirect()->route('admin.gym-management.members')->with('success', 'Gym member is already active');
        }

        $data->is_archive = 0;
        $data->save();

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

        $allowedColumns = [
            'id', 'name', 'first_name', 'last_name', 'phone_number', 'email', 'created_at', 'updated_at',
        ];
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }

        $dateColumns = ['created_at', 'updated_at'];
        $rangeColumn = in_array($searchColumn, $dateColumns, true) ? $searchColumn : 'created_at';

        $query = User::where('role_id', 3)
            ->with([
                'membershipPayments' => function ($q) use ($now) {
                    $q->where('isapproved', 1)
                        ->where('expiration_at', '>=', $now)
                        ->orderBy('created_at', 'desc');
                },
                'membershipPayments.membership',
            ])
            ->when($search && $searchColumn, function ($q) use ($search, $searchColumn) {
                if ($searchColumn === 'name') {
                    return $q->where(function ($inner) use ($search) {
                        $inner->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
                }

                $exactColumns = ['id'];
                if (in_array($searchColumn, $exactColumns, true)) {
                    return $q->where($searchColumn, $search);
                }

                return $q->where($searchColumn, 'like', "%{$search}%");
            })
            ->when($start || $end, function ($q) use ($start, $end, $rangeColumn) {
                if ($start && $end) {
                    $q->whereBetween($rangeColumn, [$start, $end]);
                } elseif ($start) {
                    $q->whereDate($rangeColumn, '>=', $start->toDateString());
                } elseif ($end) {
                    $q->whereDate($rangeColumn, '<=', $end->toDateString());
                }
            })
            ->when($membershipStatus !== 'all', function ($q) use ($membershipStatus, $now) {
                if ($membershipStatus === 'with') {
                    return $q->whereHas('membershipPayments', function ($inner) use ($now) {
                        $inner->where('isapproved', 1)
                            ->where('expiration_at', '>=', $now);
                    });
                }

                if ($membershipStatus === 'none') {
                    return $q->whereDoesntHave('membershipPayments', function ($inner) use ($now) {
                        $inner->where('isapproved', 1)
                            ->where('expiration_at', '>=', $now);
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
}
