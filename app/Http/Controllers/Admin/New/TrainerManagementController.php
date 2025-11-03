<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Membership;
use App\Models\UserMembership;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
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
        
        $search = $request->name;
        $search_column = $request->search_column;
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $statusFilter = $request->input('status', 'all');

        if (empty($statusFilter)) {
            $statusFilter = 'all';
        }
        
        $allowed_columns = [
            'id', 'name', 'phone_number', 'email', 'created_at',
            'updated_at'
        ];
        
        if (!in_array($search_column, $allowed_columns)) {
            $search_column = null;
        }
        
        $current_time = Carbon::now();
        $dateColumns = ['created_at', 'updated_at'];
        $rangeColumn = in_array($search_column, $dateColumns, true) ? $search_column : 'created_at';

        $activeTrainerIds = Schedule::whereNotNull('trainer_id')
            ->where('class_end_date', '>=', $current_time)
            ->pluck('trainer_id')
            ->unique()
            ->filter()
            ->values();

        $statusTallies = [
            'all' => User::where('role_id', 5)->count(),
            'assigned' => User::where('role_id', 5)
                ->whereIn('id', $activeTrainerIds)
                ->count(),
        ];

        $statusTallies['unassigned'] = max($statusTallies['all'] - $statusTallies['assigned'], 0);
     
        $trainers = User::where('role_id', 5)
            ->with(['trainerSchedules.user_schedules.user'])
            ->when($search && $search_column, function ($query) use ($search, $search_column) {
                if ($search_column === 'name') {
                    return $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
                    });
                } else {
                    return $query->where($search_column, 'like', "%{$search}%");
                }
            })
            ->when($startDate || $endDate, function ($query) use ($startDate, $endDate, $rangeColumn) {
                if ($startDate) {
                    $query->whereDate($rangeColumn, '>=', Carbon::createFromFormat('Y-m-d', $startDate)->toDateString());
                }

                if ($endDate) {
                    $query->whereDate($rangeColumn, '<=', Carbon::createFromFormat('Y-m-d', $endDate)->toDateString());
                }
            })
            ->when($statusFilter !== 'all', function ($query) use ($statusFilter, $activeTrainerIds) {
                if ($statusFilter === 'assigned') {
                    return $query->whereIn('id', $activeTrainerIds);
                }

                if ($statusFilter === 'unassigned') {
                    if ($activeTrainerIds->isEmpty()) {
                        return $query;
                    }

                    return $query->whereNotIn('id', $activeTrainerIds);
                }

                return $query;
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->appends($request->query());

        return view('admin.trainermanagement.index', compact('trainers', 'current_time', 'statusTallies', 'statusFilter'));
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
        
        $destinationPath = public_path('uploads');
        
        if ($request->hasFile('profile_picture')) {
            $profilePicture = $request->file('profile_picture');
            $profilePictureUrlName = time() . '_image.' . $profilePicture->getClientOriginalExtension();
            $profilePicture->move($destinationPath, $profilePictureUrlName);
            $users->profile_picture = 'uploads/' . $profilePictureUrlName;
        }
        
        $users->save();
        
        return redirect()->route('admin.trainer-management.index')->with('success', 'Trainer added successfully');
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.trainer-management.index')
                ->withErrors($validator)
                ->withInput();
        }
        
        $data = User::findOrFail($id);
        $data->role_id = 5;
        $data->status_id = 2;
        $data->first_name = $request->first_name;
        $data->last_name = $request->last_name;
        $data->address = $request->address;
        $data->phone_number = $request->phone_number;
        $data->email = $request->email;
        
        if ($request->filled('password')) {
            $data->password = bcrypt($request['password']);
        }
        
        $data->save();

        return redirect()->route('admin.trainer-management.index')->with('success', 'Trainer updated successfully');
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
        $data->delete();

        return redirect()->route('admin.trainer-management.index')->with('success', 'Trainer deleted successfully');
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

        $search       = $request->input('name');
        $searchColumn = $request->input('search_column');
        $startDate    = $request->input('created_start', $request->input('start_date'));
        $endDate      = $request->input('created_end', $request->input('end_date'));
        $statusFilter = $request->input('status', 'all');

        if (empty($statusFilter)) {
            $statusFilter = 'all';
        }

        $allowedColumns = ['id', 'name', 'phone_number', 'email', 'created_at', 'updated_at'];
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }

        $dateColumns = ['created_at', 'updated_at'];
        $rangeColumn = in_array($searchColumn, $dateColumns, true) ? $searchColumn : 'created_at';

        $start = $startDate ? Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay() : null;
        $end   = $endDate   ? Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay()   : null;

        $now = Carbon::now();
        $activeTrainerIds = Schedule::whereNotNull('trainer_id')
            ->where('class_end_date', '>=', $now)
            ->pluck('trainer_id')
            ->unique()
            ->filter()
            ->values();

        $trainers = User::where('role_id', 5)
            ->when($search && $searchColumn, function ($query) use ($search, $searchColumn) {
                if ($searchColumn === 'name') {
                    return $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
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
}
