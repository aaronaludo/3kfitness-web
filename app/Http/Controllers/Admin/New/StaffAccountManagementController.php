<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Language;

class StaffAccountManagementController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search_column'   => 'nullable|string',
            'name'            => 'nullable|string|max:255',
            'start_date'      => 'nullable|date_format:Y-m-d',
            'end_date'        => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'payroll_status'  => 'nullable|in:all,with-payrolls,no-payrolls',
        ]);

        $keyword      = $request->input('name');
        $searchColumn = $request->input('search_column');
        $startDate    = $request->input('start_date');
        $endDate      = $request->input('end_date');
        $payrollStatus = $request->input('payroll_status', 'all');
        if (empty($payrollStatus)) {
            $payrollStatus = 'all';
        }

        $allowedColumns = [
            'id', 'user_code', 'name', 'email', 'role_id', 'phone_number', 'created_at', 'updated_at',
        ];
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }

        $dateColumns = ['created_at', 'updated_at'];
        $rangeColumn = in_array($searchColumn, $dateColumns, true) ? $searchColumn : 'created_at';

        $start = $startDate ? Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay() : null;
        $end   = $endDate   ? Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay()   : null;

        $activeStaffBase = User::where('role_id', 2)->where('is_archive', 0);
        $totalStaff = (clone $activeStaffBase)->count();
        $withPayrollsCount = (clone $activeStaffBase)->whereHas('payrolls')->count();
        $payrollTallies = [
            'all'           => $totalStaff,
            'with-payrolls' => $withPayrollsCount,
            'no-payrolls'   => max($totalStaff - $withPayrollsCount, 0),
        ];

        $baseQuery = $this->buildStaffQuery($keyword, $searchColumn, $start, $end, $rangeColumn, $payrollStatus);

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

        return view('admin.staffaccountmanagement.index', compact('data', 'archivedData', 'payrollTallies', 'payrollStatus'));
    }

    
    public function add()
    {
        return view('admin.staffaccountmanagement.add');
    }

    public function view($id)
    {
        $data = User::where('role_id', 2)->find($id);

        return view('admin.staffaccountmanagement.view', compact('data'));
    }

    public function edit($id)
    {
        $data = User::where('role_id', 2)->find($id);

        return view('admin.staffaccountmanagement.edit', compact('data'));
    }
    
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            // 'email' => 'required|email|unique:users',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')
                    ->where(fn ($q) => $q->where('role_id', 2)),
            ],
            'password' => ['required', 'confirmed'],
            'rate_per_hour' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.staff-account-management.add')
                ->withErrors($validator)
                ->withInput();
        }

        $users = new User;
        $users->role_id = 2;
        $users->status_id = 2;
        $users->first_name = $request->first_name;
        $users->last_name = $request->last_name;
        $users->address = $request->address;
        $users->phone_number = $request->phone_number;
        $users->email = $request->email;
        $users->password = $request->password;
        $users->rate_per_hour = $request->rate_per_hour;
        $users->created_by = $request->user()->first_name . " " .  $request->user()->last_name;
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

        return redirect()->route('admin.staff-account-management.index')->with('success', 'Staff created successfully');
    }
    
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'address' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'regex:/^\\+639\\d{9}$/'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.staff-account-management.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }
        
        $data = User::where('role_id', 2)->findOrFail($id);
        $data->address = $request->address;
        $data->phone_number = $request->phone_number;
        $data->save();

        return redirect()->route('admin.staff-account-management.index')->with('success', 'Staff contact updated successfully');
    }
    
    /*public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $data = User::where('role_id', 2)->findOrFail($request->id);
        $data->delete();

        return redirect()->route('admin.staff-account-management.index')->with('success', 'Staff deleted successfully');
    }*/
    
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
        
        $data = User::where('role_id', 2)->findOrFail($request->id);
        $staffName = trim(sprintf('%s %s', $data->first_name ?? '', $data->last_name ?? ''));
        $staffLabel = $staffName !== ''
            ? sprintf('#%d (%s)', $data->id, $staffName)
            : sprintf('#%d (%s)', $data->id, $data->email ?? 'staff');

        if ((int) $data->is_archive === 1) {
            $data->delete();
            $message = 'Staff deleted permanently';
            $this->logAdminActivity("deleted staff account {$staffLabel} permanently");
        } else {
            $data->is_archive = 1;
            $data->save();
            $message = 'Staff moved to archive';
            $this->logAdminActivity("archived staff account {$staffLabel}");
        }

        return redirect()->route('admin.staff-account-management.index')->with('success', $message);
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

        $data = User::where('role_id', 2)->findOrFail($request->id);
        $staffName = trim(sprintf('%s %s', $data->first_name ?? '', $data->last_name ?? ''));
        $staffLabel = $staffName !== ''
            ? sprintf('#%d (%s)', $data->id, $staffName)
            : sprintf('#%d (%s)', $data->id, $data->email ?? 'staff');

        if ((int) $data->is_archive === 0) {
            return redirect()->route('admin.staff-account-management.index')->with('success', 'Staff is already active');
        }

        $data->is_archive = 0;
        $data->save();

        $this->logAdminActivity("restored staff account {$staffLabel}");

        return redirect()->route('admin.staff-account-management.index')->with('success', 'Staff restored successfully');
    }
    
    public function print(Request $request)
    {
        $request->validate([
            'created_start'   => 'nullable|date',
            'created_end'     => 'nullable|date|after_or_equal:created_start',
            'search_column'   => 'nullable|string',
            'name'            => 'nullable|string|max:255',
            'payroll_status'  => 'nullable|in:all,with-payrolls,no-payrolls',
        ]);

        $startInput   = $request->input('created_start');
        $endInput     = $request->input('created_end');
        $keyword      = $request->input('name');
        $searchColumn = $request->input('search_column');
        $payrollStatus = $request->input('payroll_status', 'all');

        if (empty($payrollStatus)) {
            $payrollStatus = 'all';
        }

        $start = $startInput ? Carbon::parse($startInput)->startOfDay() : null;
        $end   = $endInput   ? Carbon::parse($endInput)->endOfDay()   : null;

        if ($start && !$end) {
            $end = (clone $start)->endOfDay();
        } elseif (!$start && $end) {
            $start = Carbon::createFromTimestamp(0)->startOfDay();
        }

        $allowedColumns = [
            'id', 'user_code', 'name', 'email', 'role_id', 'phone_number', 'created_at', 'updated_at',
        ];
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }

        $dateColumns = ['created_at', 'updated_at'];
        $rangeColumn = in_array($searchColumn, $dateColumns, true) ? $searchColumn : 'created_at';

        $query = $this->buildStaffQuery($keyword, $searchColumn, $start, $end, $rangeColumn, $payrollStatus)
            ->where('is_archive', 0);
        $data  = $query->get();

        $suffix = '';
        if ($start && $end) {
            $suffix = '_' . $start->format('Ymd') . '_to_' . $end->format('Ymd');
        }
        $fileName = "staff_accounts{$suffix}_" . date('Y-m-d') . ".docx";

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

        $title = 'Staff Accounts';
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
        $phpWord->addTableStyle('StaffAccountsTable', $tableStyle, $firstRowStyle);
        $table = $section->addTable('StaffAccountsTable');

        $headers = [
            '#',
            'User Code',
            'Name',
            'Email',
            'Type',
            'Contact Number',
            'Created Date',
            'Updated Date',
            'Rate Per Hour',
            'Total Payrolls',
        ];
        $headerRow = $table->addRow();
        foreach ($headers as $header) {
            $headerRow->addCell()->addText($header, ['bold' => true]);
        }

        foreach ($data as $item) {
            $row = $table->addRow();
            $row->addCell()->addText((string) $item->id);
            $row->addCell()->addText((string) ($item->user_code ?? ''));
            $row->addCell()->addText(trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? '')));
            $row->addCell()->addText((string) ($item->email ?? ''));
            $row->addCell()->addText(optional($item->role)->name ?? '');
            $row->addCell()->addText((string) ($item->phone_number ?? ''));
            $row->addCell()->addText((string) $item->created_at);
            $row->addCell()->addText((string) $item->updated_at);
            $row->addCell()->addText($item->rate_per_hour !== null ? number_format((float) $item->rate_per_hour, 2) : '0.00');
            $row->addCell()->addText((string) ($item->payrolls_count ?? $item->payrolls->count() ?? 0));
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

    protected function buildStaffQuery(?string $keyword, ?string $searchColumn, ?Carbon $start, ?Carbon $end, string $rangeColumn, string $payrollStatus = 'all')
    {
        $query = User::where('role_id', 2)
            ->with(['role', 'payrolls'])
            ->withCount('payrolls')
            ->orderBy('created_at', 'desc');

        if ($keyword && !$searchColumn) {
            $searchColumn = 'name';
        }

        $query->when($keyword && $searchColumn, function ($query) use ($keyword, $searchColumn) {
            $keyword = trim($keyword);

            switch ($searchColumn) {
                case 'id':
                    return $query->where('id', $keyword);
                case 'name':
                    return $query->where(function ($q) use ($keyword) {
                        $q->where('first_name', 'like', "%{$keyword}%")
                            ->orWhere('last_name', 'like', "%{$keyword}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$keyword}%"]);
                    });
                case 'role_id':
                    return $query->where('role_id', $keyword);
                default:
                    return $query->where($searchColumn, 'like', "%{$keyword}%");
            }
        });

        $query->when($start || $end, function ($query) use ($start, $end, $rangeColumn) {
            if ($start && $end) {
                $query->whereBetween($rangeColumn, [$start, $end]);
            } elseif ($start) {
                $query->whereDate($rangeColumn, '>=', $start->toDateString());
            } elseif ($end) {
                $query->whereDate($rangeColumn, '<=', $end->toDateString());
            }
        });

        if ($payrollStatus === 'with-payrolls') {
            $query->whereHas('payrolls');
        } elseif ($payrollStatus === 'no-payrolls') {
            $query->whereDoesntHave('payrolls');
        }

        return $query;
    }
}
