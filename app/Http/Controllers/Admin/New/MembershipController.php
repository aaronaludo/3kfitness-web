<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Membership;
use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Language;

class MembershipController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search_column' => 'nullable|string',
            'name' => 'nullable|string|max:255',
            'start_date'    => 'nullable|date_format:Y-m-d',
            'end_date'      => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'membership_status' => 'nullable|in:all,active,empty',
        ]);
    
        $search = $request->name;
        $search_column = $request->search_column;
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $statusFilter = $request->input('membership_status', 'all');
        if (empty($statusFilter)) {
            $statusFilter = 'all';
        }
    
        $allowed_columns = [
            'id', 'name', 'description', 'month', 'class_limit_per_month', 'members_approved', 'members_pending', 'members_reject',
            'created_at', 'updated_at',
        ];
    
        if (!in_array($search_column, $allowed_columns)) {
            $search_column = null;
        }

        $dateColumns = ['created_at', 'updated_at'];
        $rangeColumn = in_array($search_column, $dateColumns, true) ? $search_column : 'created_at';

        $start = $startDate ? Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay() : null;
        $end   = $endDate   ? Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay()   : null;
    
        $activeBase = Membership::where('is_archive', 0);
        $totalMemberships = (clone $activeBase)->count();
        $activeMemberships = (clone $activeBase)->whereHas('membershipPayments', function ($query) {
            $query->where('isapproved', 1);
        })->count();
        $statusTallies = [
            'all' => $totalMemberships,
            'active' => $activeMemberships,
            'empty' => max($totalMemberships - $activeMemberships, 0),
        ];

        $baseQuery = Membership::query()
            ->withCount([
                'membershipPayments as members_approved' => function ($query) {
                    $query->where('isapproved', 1);
                },
                'membershipPayments as members_pending' => function ($query) {
                    $query->where('isapproved', 0);
                },
                'membershipPayments as members_reject' => function ($query) {
                    $query->where('isapproved', 2);
                },
            ])
            ->when($search && $search_column, function ($query) use ($search, $search_column) {
                if (in_array($search_column, ['members_approved', 'members_pending', 'members_reject'])) {
                    return $query->having($search_column, '=', (int) $search);
                }
                return $query->where($search_column, 'like', "%{$search}%");
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
            ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                if ($statusFilter === 'active') {
                    return $query->whereHas('membershipPayments', function ($q) {
                        $q->where('isapproved', 1);
                    });
                }

                if ($statusFilter === 'empty') {
                    return $query->whereDoesntHave('membershipPayments', function ($q) {
                        $q->where('isapproved', 1);
                    });
                }

                return $query;
            })
            ->orderByDesc('created_at');

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

        return view('admin.memberships.index', [
            'data' => $data,
            'archivedData' => $archivedData,
            'statusTallies' => $statusTallies,
            'printAllActive' => $printAllActive,
            'printAllArchived' => $printAllArchived,
        ]);
    }


    public function view($id)
    {
        $data = Membership::findOrFail($id);

        return view('admin.memberships.view', compact('data'));
    }

    public function create()
    {
        return view('admin.memberships.create');
    }

    public function edit($id)
    {
        $data = Membership::findOrFail($id);

        return view('admin.memberships.edit', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            // 'currency' => 'required',
            'price' => 'required',
            'description' => 'nullable|string|max:2000',
            'class_limit_per_month' => 'nullable|integer|min:0',
        ]);

        $data = new Membership;
        $data->name = $request->name;
        // $data->currency = $request->currency;
        $data->price = $request->price;
        $data->description = $request->description;
        // $data->year = $request->year;
        $data->month = $request->month;
        // $data->week = $request->week;
        $data->class_limit_per_month = $request->class_limit_per_month !== null && $request->class_limit_per_month !== '' 
            ? $request->class_limit_per_month 
            : null;
        $data->save();

        return redirect()->route('admin.staff-account-management.memberships')->with('success', 'Membership added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            // 'currency' => 'required',
            'price' => 'required',
            'description' => 'nullable|string|max:2000',
            'class_limit_per_month' => 'nullable|integer|min:0',
        ]);

        $data = Membership::findOrFail($id);
        $data->name = $request->name;
        // $data->currency = $request->currency;
        $data->price = $request->price;
        $data->description = $request->description;
        // $data->year = $request->year;
        $data->month = $request->month;
        // $data->week = $request->week;
        $data->class_limit_per_month = $request->class_limit_per_month !== null && $request->class_limit_per_month !== '' 
            ? $request->class_limit_per_month 
            : null;
        $data->save();

        return redirect()->route('admin.staff-account-management.memberships')->with('success', 'Membership updated successfully');
    }

    public function delete(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:memberships,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        
        $user = $request->user();
    
        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }
        
        $data = Membership::findOrFail($request->id);
        $membershipLabel = sprintf('#%d (%s)', $data->id, $data->name ?? 'membership');

        if ((int) $data->is_archive === 1) {
            $data->delete();
            $message = 'Membership deleted permanently';
            $this->logAdminActivity("deleted membership {$membershipLabel} permanently");
        } else {
            $data->is_archive = 1;
            $data->save();
            $message = 'Membership moved to archive';
            $this->logAdminActivity("archived membership {$membershipLabel}");
        }

        return redirect()->route('admin.staff-account-management.memberships')->with('success', $message);
    }
    
    public function restore(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:memberships,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $user = $request->user();

        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }

        $data = Membership::findOrFail($request->id);
        $membershipLabel = sprintf('#%d (%s)', $data->id, $data->name ?? 'membership');

        if ((int) $data->is_archive === 0) {
            return redirect()->route('admin.staff-account-management.memberships')->with('success', 'Membership is already active');
        }

        $data->is_archive = 0;
        $data->save();

        $this->logAdminActivity("restored membership {$membershipLabel}");

        return redirect()->route('admin.staff-account-management.memberships')->with('success', 'Membership restored successfully');
    }
    
    public function print(Request $request)
    {
        $request->validate([
            'created_start' => 'nullable|date',
            'created_end'   => 'nullable|date|after_or_equal:created_start',
            'search_column' => 'nullable|string',
            'name'          => 'nullable|string|max:255',
            'membership_status' => 'nullable|in:all,active,empty',
        ]);

        $startInput   = $request->input('created_start');
        $endInput     = $request->input('created_end');
        $search       = $request->input('name');
        $searchColumn = $request->input('search_column');
        $statusFilter = $request->input('membership_status', 'all');

        if (empty($statusFilter)) {
            $statusFilter = 'all';
        }

        $start = $startInput ? Carbon::parse($startInput)->startOfDay() : null;
        $end   = $endInput   ? Carbon::parse($endInput)->endOfDay()   : null;

        if ($start && !$end) {
            $end = (clone $start)->endOfDay();
        } elseif (!$start && $end) {
            $start = Carbon::createFromTimestamp(0)->startOfDay();
        }

        $allowedColumns = [
            'id', 'name', 'description', 'price', 'month', 'members_approved', 'members_pending', 'members_reject',
            'created_at', 'updated_at',
        ];
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }

        $dateColumns = ['created_at', 'updated_at'];
        $rangeColumn = in_array($searchColumn, $dateColumns, true) ? $searchColumn : 'created_at';

        $query = Membership::query()
            ->where('is_archive', 0)
            ->withCount([
                'membershipPayments as members_approved' => function ($query) {
                    $query->where('isapproved', 1);
                },
                'membershipPayments as members_pending' => function ($query) {
                    $query->where('isapproved', 0);
                },
                'membershipPayments as members_reject' => function ($query) {
                    $query->where('isapproved', 2);
                },
            ])
            ->when($search && $searchColumn, function ($query) use ($search, $searchColumn) {
                $countColumns = ['members_approved', 'members_pending', 'members_reject'];
                if (in_array($searchColumn, $countColumns, true)) {
                    return $query->having($searchColumn, '=', (int) $search);
                }

                $exactColumns = ['id', 'price', 'month'];
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
            ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                if ($statusFilter === 'active') {
                    return $query->whereHas('membershipPayments', function ($q) {
                        $q->where('isapproved', 1);
                    });
                }

                if ($statusFilter === 'empty') {
                    return $query->whereDoesntHave('membershipPayments', function ($q) {
                        $q->where('isapproved', 1);
                    });
                }

                return $query;
            })
            ->orderBy('created_at', 'desc');

        $data = $query->get();

        $suffix = '';
        if ($start && $end) {
            $suffix = '_' . $start->format('Ymd') . '_to_' . $end->format('Ymd');
        }
        $fileName = "memberships_data{$suffix}_" . date('Y-m-d') . ".docx";

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

        $title = 'Memberships';
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
        $phpWord->addTableStyle('MembershipsTable', $tableStyle, $firstRowStyle);
        $table = $section->addTable('MembershipsTable');

        $headers = [
            'ID',
            'Name',
            'Description',
            'Price',
            'Month',
            'Total Members Approved',
            'Total Members Pending',
            'Total Members Reject',
            'Created At',
            'Updated At',
        ];
        $headerRow = $table->addRow();
        foreach ($headers as $header) {
            $headerRow->addCell()->addText($header, ['bold' => true]);
        }

        foreach ($data as $item) {
            $row = $table->addRow();
            $row->addCell()->addText((string) $item->id);
            $row->addCell()->addText((string) ($item->name ?? ''));
            $row->addCell()->addText((string) ($item->description ?? ''));
            $row->addCell()->addText((string) ($item->price ?? ''));
            $row->addCell()->addText((string) ($item->month ?? 0));
            $row->addCell()->addText((string) ($item->members_approved ?? 0));
            $row->addCell()->addText((string) ($item->members_pending ?? 0));
            $row->addCell()->addText((string) ($item->members_reject ?? 0));
            $row->addCell()->addText((string) $item->created_at);
            $row->addCell()->addText((string) $item->updated_at);
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
