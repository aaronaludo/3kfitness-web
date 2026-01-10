<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Membership;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Language;

class TrainerManagementController extends Controller
{
    // public function index(){
    //     return view("admin.trainermanagement.index");
    // }

    public function index(Request $request)
    {
        $request->validate([
            'search_column' => 'nullable|string',
            'name' => 'nullable|string|max:255',
            'start_date'    => 'nullable|date_format:Y-m-d',
            'end_date'      => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'status'        => 'nullable|in:all,assigned,unassigned',
        ]);
        
        $search = trim((string) $request->input('name', ''));
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $statusFilter = $request->input('status', 'all');

        if (empty($statusFilter)) {
            $statusFilter = 'all';
        }
        
        $current_time = Carbon::now();

        $activeTrainerIds = Schedule::whereNotNull('trainer_id')
            ->where('class_end_date', '>=', $current_time)
            ->pluck('trainer_id')
            ->unique()
            ->filter()
            ->values();

        $activeTrainerBaseQuery = User::where('role_id', 5)->where('is_archive', 0);
        $statusTallies = [
            'all' => (clone $activeTrainerBaseQuery)->count(),
            'assigned' => (clone $activeTrainerBaseQuery)
                ->whereIn('id', $activeTrainerIds)
                ->count(),
        ];

        $statusTallies['unassigned'] = max($statusTallies['all'] - $statusTallies['assigned'], 0);

        $baseQuery = User::where('role_id', 5)
            ->with(['trainerSchedules.activeUserSchedules.user'])
            ->when($startDate || $endDate, function ($query) use ($startDate, $endDate) {
                if ($startDate) {
                    $query->whereDate('created_at', '>=', Carbon::createFromFormat('Y-m-d', $startDate)->toDateString());
                }

                if ($endDate) {
                    $query->whereDate('created_at', '<=', Carbon::createFromFormat('Y-m-d', $endDate)->toDateString());
                }
            });

        if ($search !== '') {
            $this->applyTrainerSearch($baseQuery, $search);
        }

        $queryParamsWithoutArchivePage = $request->except('archive_page');
        $queryParamsWithoutMainPage = $request->except('page');

        $activeQuery = (clone $baseQuery)
            ->where('is_archive', 0)
            ->when($statusFilter !== 'all', function ($query) use ($statusFilter, $activeTrainerIds) {
                if ($statusFilter === 'assigned') {
                    return $query->whereIn('id', $activeTrainerIds->all());
                }

                if ($statusFilter === 'unassigned') {
                    if ($activeTrainerIds->isEmpty()) {
                        return $query;
                    }

                    return $query->whereNotIn('id', $activeTrainerIds->all());
                }

                return $query;
            })
            ->orderByDesc('created_at');

        $archivedQuery = (clone $baseQuery)
            ->where('is_archive', 1)
            ->orderByDesc('created_at');

        $printAllActive = (clone $activeQuery)->get();
        $printAllArchived = (clone $archivedQuery)->get();

        $trainers = (clone $activeQuery)
            ->paginate(10)
            ->appends($queryParamsWithoutArchivePage);

        $archivedData = (clone $archivedQuery)
            ->paginate(10, ['*'], 'archive_page')
            ->appends($queryParamsWithoutMainPage);

        return view('admin.trainermanagement.index', compact(
            'trainers',
            'archivedData',
            'current_time',
            'statusTallies',
            'statusFilter',
            'printAllActive',
            'printAllArchived'
        ));
    } 

    public function view($id){
        $trainer = User::where('role_id', 5)->findOrFail($id);

        return view("admin.trainermanagement.view", compact("trainer"));
    }

    public function add(){
        return view("admin.trainermanagement.add");
    }

    public function edit($id){
        $data = User::where('role_id', 5)->findOrFail($id);

        return view("admin.trainermanagement.edit", compact("data"));
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
        ]);

        $users = new User;
        $users->role_id = 5;
        $users->status_id = 2;
        $users->first_name = $validatedData['first_name'];
        $users->last_name = $validatedData['last_name'];
        $users->address = $validatedData['address'];
        $users->phone_number = $validatedData['phone_number'];
        $users->email = $validatedData['email'];
        $users->password = bcrypt($validatedData['password']);
        $users->created_by = $request->user()->first_name . " " .  $request->user()->last_name;

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
        
        return redirect()->route('admin.trainer-management.index')->with('success', 'Trainer added successfully');
    }

    public function update(Request $request, $id)
    {
        $canEditAll = in_array($request->user()->role_id ?? null, [1, 4], true);

        $rules = [
            'address' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'regex:/^\\+639\\d{9}$/'],
        ];

        if ($canEditAll) {
            $rules = array_merge($rules, [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
                'profile_picture' => ['nullable', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
                'password' => ['nullable', 'confirmed'],
            ]);
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->route('admin.trainer-management.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }
        
        $data = User::where('role_id', 5)->findOrFail($id);
        $data->address = $request->address;
        $data->phone_number = $request->phone_number;
        if ($canEditAll) {
            $data->first_name = $request->first_name;
            $data->last_name = $request->last_name;
            $data->email = $request->email;

            if ($request->filled('password')) {
                $data->password = $request->password;
            }

            if ($request->hasFile('profile_picture')) {
                $destinationPath = public_path('uploads');
                if (!is_dir($destinationPath)) {
                    @mkdir($destinationPath, 0775, true);
                }

                $profilePicture = $request->file('profile_picture');
                $profilePictureUrlName = time() . '_image.' . $profilePicture->getClientOriginalExtension();
                $profilePicture->move($destinationPath, $profilePictureUrlName);

                if (!empty($data->profile_picture) && file_exists(public_path($data->profile_picture))) {
                    @unlink(public_path($data->profile_picture));
                }

                $data->profile_picture = 'uploads/' . $profilePictureUrlName;
            }
        }
        $data->save();

        $message = $canEditAll ? 'Trainer details updated successfully' : 'Trainer contact updated successfully';

        return redirect()->route('admin.trainer-management.index')->with('success', $message);
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
        
        $data = User::where('role_id', 5)->findOrFail($request->id);
        $trainerName = trim(sprintf('%s %s', $data->first_name ?? '', $data->last_name ?? ''));
        $trainerLabel = $trainerName !== ''
            ? sprintf('#%d (%s)', $data->id, $trainerName)
            : sprintf('#%d (%s)', $data->id, $data->email ?? 'trainer');

        if ((int) $data->is_archive === 1) {
            $data->delete();
            $message = 'Trainer deleted permanently';
            $this->logAdminActivity("deleted trainer {$trainerLabel} permanently");
        } else {
            $data->is_archive = 1;
            $data->save();
            $message = 'Trainer moved to archive';
            $this->logAdminActivity("archived trainer {$trainerLabel}");
        }

        return redirect()->route('admin.trainer-management.index')->with('success', $message);
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

        $data = User::where('role_id', 5)->findOrFail($request->id);
        $trainerName = trim(sprintf('%s %s', $data->first_name ?? '', $data->last_name ?? ''));
        $trainerLabel = $trainerName !== ''
            ? sprintf('#%d (%s)', $data->id, $trainerName)
            : sprintf('#%d (%s)', $data->id, $data->email ?? 'trainer');

        if ((int) $data->is_archive === 0) {
            return redirect()->route('admin.trainer-management.index')->with('success', 'Trainer is already active');
        }

        $data->is_archive = 0;
        $data->save();

        $this->logAdminActivity("restored trainer {$trainerLabel}");

        return redirect()->route('admin.trainer-management.index')->with('success', 'Trainer restored successfully');
    }

    public function print(Request $request)
    {
        $request->validate([
            'search_column' => 'nullable|string',
            'name'          => 'nullable|string|max:255',
            'created_start' => 'nullable|date_format:Y-m-d',
            'created_end'   => 'nullable|date_format:Y-m-d|after_or_equal:created_start',
            'status'        => 'nullable|in:all,assigned,unassigned',
        ]);

        $search       = trim((string) $request->input('name', ''));
        $startDate    = $request->input('created_start', $request->input('start_date'));
        $endDate      = $request->input('created_end', $request->input('end_date'));
        $statusFilter = $request->input('status', 'all');

        if (empty($statusFilter)) {
            $statusFilter = 'all';
        }

        $start = $startDate ? Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay() : null;
        $end   = $endDate   ? Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay()   : null;

        $now = Carbon::now();
        $activeTrainerIds = Schedule::whereNotNull('trainer_id')
            ->where('class_end_date', '>=', $now)
            ->pluck('trainer_id')
            ->unique()
            ->filter()
            ->values();

        $trainersQuery = User::where('role_id', 5)
            ->when($start || $end, function ($query) use ($start, $end) {
                if ($start && $end) {
                    return $query->whereBetween('created_at', [$start, $end]);
                }

                if ($start) {
                    return $query->whereDate('created_at', '>=', $start->toDateString());
                }

                if ($end) {
                    return $query->whereDate('created_at', '<=', $end->toDateString());
                }

                return $query;
            })
            ->when($statusFilter !== 'all', function ($query) use ($statusFilter, $activeTrainerIds) {
                if ($statusFilter === 'assigned') {
                    return $query->whereIn('id', $activeTrainerIds->all());
                }

                if ($statusFilter === 'unassigned') {
                    if ($activeTrainerIds->isEmpty()) {
                        return $query;
                    }

                    return $query->whereNotIn('id', $activeTrainerIds->all());
                }

                return $query;
            });

        if ($search !== '') {
            $this->applyTrainerSearch($trainersQuery, $search);
        }

        $trainers = $trainersQuery
            ->orderByDesc('created_at')
            ->get();

        $suffix = '';
        if ($start && $end) {
            $suffix .= '_' . $start->format('Ymd') . '_to_' . $end->format('Ymd');
        }
        if ($statusFilter !== 'all') {
            $suffix .= '_' . $statusFilter;
        }

        $fileName = 'trainers' . $suffix . '_' . now()->format('Y-m-d') . '.docx';

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

        $titleParts = ['Trainer Directory'];
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
        $phpWord->addTableStyle('TrainersTable', $tableStyle, $firstRowStyle);
        $table = $section->addTable('TrainersTable');

        $headers = [
            'ID',
            'Name',
            'Phone Number',
            'Email',
            'Status',
            'Created At',
            'Updated At',
        ];
        $headerRow = $table->addRow();
        foreach ($headers as $header) {
            $headerRow->addCell()->addText($header, ['bold' => true]);
        }

        foreach ($trainers as $trainer) {
            $row = $table->addRow();

            $fullName = trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? ''));
            $statusText = $activeTrainerIds->contains($trainer->id) ? 'Assigned' : 'Unassigned';

            $cells = [
                $trainer->id,
                $fullName !== '' ? $fullName : 'Unknown',
                (string) $trainer->phone_number,
                (string) $trainer->email,
                $statusText,
                (string) $trainer->created_at,
                (string) $trainer->updated_at,
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

    protected function applyTrainerSearch($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $like = '%' . $search . '%';
        $integerSearch = ctype_digit($search) ? (int) $search : null;

        $parsedDate = null;
        try {
            $parsedDate = Carbon::parse($search)->toDateString();
        } catch (\Exception $e) {
            $parsedDate = null;
        }

        return $query->where(function ($query) use ($like, $integerSearch, $parsedDate) {
            if ($integerSearch !== null) {
                $query->orWhere('id', $integerSearch);
            }

            $query->orWhere('user_code', 'like', $like)
                ->orWhere('phone_number', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('created_by', 'like', $like)
                ->orWhere('created_at', 'like', $like)
                ->orWhere('updated_at', 'like', $like);

            $query->orWhere(function ($nameQuery) use ($like) {
                $nameQuery->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhereRaw(
                        "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                        [$like]
                    );
            });

            if ($parsedDate) {
                $query->orWhereDate('created_at', $parsedDate)
                    ->orWhereDate('updated_at', $parsedDate);
            }
        });
    }
}
