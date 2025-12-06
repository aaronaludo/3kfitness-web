<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MembershipPayment;
use App\Models\Membership;
use App\Models\User;
use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Language;

class MembershipPaymentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search_column' => 'nullable|string',
            'name'          => 'nullable|string|max:255',
            'member_name'   => 'nullable|string|max:255',
            'start_date'    => 'nullable|date_format:Y-m-d',
            'end_date'      => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'status'        => 'nullable|in:all,pending,approved,rejected',
        ]);

        $keyword      = $request->input('name', $request->input('member_name'));
        $searchColumn = $request->input('search_column');
        $startDate    = $request->input('start_date');
        $endDate      = $request->input('end_date');
        $statusFilter = $request->input('status', 'all');
        if (empty($statusFilter)) {
            $statusFilter = 'all';
        }

        $allowedColumns = [
            'id', 'member_name', 'member_user_code', 'membership', 'expiration_at', 'created_at', 'updated_at', 'status',
        ];
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }

        $dateColumns = ['created_at', 'updated_at', 'expiration_at'];
        $rangeColumn = in_array($searchColumn, $dateColumns, true) ? $searchColumn : 'created_at';

        $start = $startDate ? Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay() : null;
        $end   = $endDate   ? Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay()   : null;

        $statusTallies = [
            'all'      => MembershipPayment::where('is_archive', 0)->count(),
            'pending'  => MembershipPayment::where('is_archive', 0)->where('isapproved', 0)->count(),
            'approved' => MembershipPayment::where('is_archive', 0)->where('isapproved', 1)->count(),
            'rejected' => MembershipPayment::where('is_archive', 0)->where('isapproved', 2)->count(),
        ];

        $baseQuery = $this->buildMembershipPaymentQuery($keyword, $searchColumn, $start, $end, $rangeColumn, $statusFilter);

        $queryParamsWithoutArchivePage = $request->except('archive_page');
        $queryParamsWithoutMainPage = $request->except('page');

        $activeQuery = (clone $baseQuery)->where('is_archive', 0);
        $archivedQuery = (clone $baseQuery)->where('is_archive', 1);

        // Clone before paginating so print-all queries are not limited to the current page.
        $printAllActive = (clone $activeQuery)->get();
        $printAllArchived = (clone $archivedQuery)->get();

        $data = (clone $activeQuery)
            ->paginate(10)
            ->appends($queryParamsWithoutArchivePage);

        $archivedData = (clone $archivedQuery)
            ->paginate(10, ['*'], 'archive_page')
            ->appends($queryParamsWithoutMainPage);

        return view('admin.membership-payments.index', [
            'data' => $data,
            'archivedData' => $archivedData,
            'statusTallies' => $statusTallies,
            'printAllActive' => $printAllActive,
            'printAllArchived' => $printAllArchived,
        ]);
    }

    public function view($id)
    {
        $data = MembershipPayment::findOrFail($id);

        return view('admin.membership-payments.view', compact('data'));
    }

    public function receipt($id)
    {
        $record = MembershipPayment::with(['membership', 'user'])->findOrFail($id);
        $createdAt = $record->created_at ? Carbon::parse($record->created_at) : Carbon::now();

        return view('admin.payments.receipt', [
            'record' => $record,
            'createdAt' => $createdAt,
        ]);
    }

    public function isapprove(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:membership_payments,id',
            'isapproved' => 'required|integer'
        ]);

        $data = MembershipPayment::findOrFail($request->id);
        $data->isapproved = $request->isapproved;
        $data->save();

        return redirect()->route('admin.staff-account-management.membership-payments')->with('success', 'Membership Payment updated successfully');
    }

    public function delete(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:membership_payments,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $user = $request->user();

        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }

        $data = MembershipPayment::findOrFail($request->id);
        $data->loadMissing(['user', 'membership']);
        $payer = optional($data->user);
        $payerName = trim(sprintf('%s %s', $payer->first_name ?? '', $payer->last_name ?? ''));
        $payerLabel = $payerName !== ''
            ? $payerName
            : ($payer->email ?? 'member');
        $membershipName = optional($data->membership)->name ?? 'membership';
        $paymentLabel = sprintf('#%d (%s - %s)', $data->id, $payerLabel, $membershipName);

        if ((int) $data->is_archive === 1) {
            $data->delete();
            $message = 'Membership payment deleted permanently';
            $this->logAdminActivity("deleted membership payment {$paymentLabel} permanently");
        } else {
            $data->is_archive = 1;
            $data->save();
            $message = 'Membership payment moved to archive';
            $this->logAdminActivity("archived membership payment {$paymentLabel}");
        }

        return redirect()->route('admin.staff-account-management.membership-payments')->with('success', $message);
    }

    public function restore(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:membership_payments,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $user = $request->user();

        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }

        $data = MembershipPayment::findOrFail($request->id);
        $data->loadMissing(['user', 'membership']);
        $payer = optional($data->user);
        $payerName = trim(sprintf('%s %s', $payer->first_name ?? '', $payer->last_name ?? ''));
        $payerLabel = $payerName !== ''
            ? $payerName
            : ($payer->email ?? 'member');
        $membershipName = optional($data->membership)->name ?? 'membership';
        $paymentLabel = sprintf('#%d (%s - %s)', $data->id, $payerLabel, $membershipName);

        if ((int) $data->is_archive === 0) {
            return redirect()->route('admin.staff-account-management.membership-payments')->with('success', 'Membership payment is already active');
        }

        $data->is_archive = 0;
        $data->save();

        $this->logAdminActivity("restored membership payment {$paymentLabel}");

        return redirect()->route('admin.staff-account-management.membership-payments')->with('success', 'Membership payment restored successfully');
    }

    public function print(Request $request)
    {
        $request->validate([
            'created_start' => 'nullable|date',
            'created_end'   => 'nullable|date|after_or_equal:created_start',
            'search_column' => 'nullable|string',
            'name'          => 'nullable|string|max:255',
            'member_name'   => 'nullable|string|max:255',
            'status'        => 'nullable|in:all,pending,approved,rejected',
        ]);

        $startInput   = $request->input('created_start');
        $endInput     = $request->input('created_end');
        $keyword      = $request->input('name', $request->input('member_name'));
        $searchColumn = $request->input('search_column');
        $statusFilter = $request->input('status', 'all');

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
            'id', 'member_name', 'member_user_code', 'membership', 'expiration_at', 'created_at', 'updated_at', 'status',
        ];
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }

        $dateColumns = ['created_at', 'updated_at', 'expiration_at'];
        $rangeColumn = in_array($searchColumn, $dateColumns, true) ? $searchColumn : 'created_at';

        $query = $this->buildMembershipPaymentQuery($keyword, $searchColumn, $start, $end, $rangeColumn, $statusFilter);
        $data  = $query->get();

        $suffix = '';
        if ($start && $end) {
            $suffix = '_' . $start->format('Ymd') . '_to_' . $end->format('Ymd');
        }
        $fileName = "membership_payments{$suffix}_" . date('Y-m-d') . ".docx";

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

        $title = 'Membership Payments';
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
        $phpWord->addTableStyle('MembershipPaymentsTable', $tableStyle, $firstRowStyle);
        $table = $section->addTable('MembershipPaymentsTable');

        $headers = [
            'ID',
            'Member Name',
            'User Code',
            'Membership',
            'Expiration Date',
            'Created Date',
            'Updated Date',
            'Status',
        ];
        $headerRow = $table->addRow();
        foreach ($headers as $header) {
            $headerRow->addCell()->addText($header, ['bold' => true]);
        }

        $statusLabels = [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Rejected',
        ];

        foreach ($data as $item) {
            $memberName = trim(($item->user->first_name ?? '') . ' ' . ($item->user->last_name ?? ''));
            $memberCode = $item->user->user_code ?? '';
            $membership = optional($item->membership)->name ?? '';
            $status     = $statusLabels[$item->isapproved] ?? 'Pending';

            $row = $table->addRow();
            $row->addCell()->addText((string) $item->id);
            $row->addCell()->addText($memberName);
            $row->addCell()->addText($memberCode);
            $row->addCell()->addText($membership);
            $row->addCell()->addText((string) $item->expiration_at);
            $row->addCell()->addText((string) $item->created_at);
            $row->addCell()->addText((string) $item->updated_at);
            $row->addCell()->addText($status);
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
     * Build the base query with shared filtering logic for index and export.
     */
    protected function buildMembershipPaymentQuery(?string $keyword, ?string $searchColumn, ?Carbon $start, ?Carbon $end, string $rangeColumn, string $statusFilter = 'all')
    {
        $query = MembershipPayment::query()
            ->with([
                'user' => function ($userQuery) {
                    $userQuery->select('id', 'first_name', 'last_name', 'email', 'user_code')
                        ->with([
                            'userSchedules' => function ($userScheduleQuery) {
                                $userScheduleQuery->select('id', 'user_id', 'schedule_id')
                                    ->with([
                                        'schedule' => function ($scheduleQuery) {
                                            $scheduleQuery->select('id', 'name', 'class_code');
                                        },
                                    ]);
                            },
                        ]);
                },
                'membership:id,name,currency,price',
            ]);

        if ($keyword && !$searchColumn) {
            $searchColumn = 'member_name';
        }

        $query->when($keyword && $searchColumn, function ($query) use ($keyword, $searchColumn) {
            $keyword = trim($keyword);
            switch ($searchColumn) {
                case 'id':
                    return $query->where('id', $keyword);
                case 'member_name':
                    return $query->whereHas('user', function ($subQuery) use ($keyword) {
                        $subQuery->where(function ($builder) use ($keyword) {
                            $builder->where('first_name', 'like', "%{$keyword}%")
                                ->orWhere('last_name', 'like', "%{$keyword}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$keyword}%"]);
                        });
                    });
                case 'member_user_code':
                    return $query->whereHas('user', function ($subQuery) use ($keyword) {
                        $subQuery->where('user_code', 'like', "%{$keyword}%");
                    });
                case 'membership':
                    return $query->whereHas('membership', function ($subQuery) use ($keyword) {
                        $subQuery->where('name', 'like', "%{$keyword}%");
                    });
                case 'status':
                    $normalized = strtolower($keyword);
                    $statusMap  = [
                        'pending'  => 0,
                        'approved' => 1,
                        'rejected' => 2,
                    ];
                    $statusValue = null;
                    foreach ($statusMap as $label => $value) {
                        if ($normalized === $label || str_starts_with($label, $normalized)) {
                            $statusValue = $value;
                            break;
                        }
                    }
                    if ($statusValue === null && is_numeric($keyword)) {
                        $candidate = (int) $keyword;
                        if (in_array($candidate, array_values($statusMap), true)) {
                            $statusValue = $candidate;
                        }
                    }
                    if ($statusValue !== null) {
                        return $query->where('isapproved', $statusValue);
                    }
                    return $query;
                case 'expiration_at':
                case 'created_at':
                case 'updated_at':
                    try {
                        $parsed = Carbon::parse($keyword);
                        return $query->whereDate($searchColumn, $parsed->toDateString());
                    } catch (\Exception $e) {
                        return $query->where($searchColumn, 'like', "%{$keyword}%");
                    }
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

        if ($statusFilter !== 'all') {
            $statusMap = [
                'pending'  => 0,
                'approved' => 1,
                'rejected' => 2,
            ];

            if (array_key_exists($statusFilter, $statusMap)) {
                $query->where('isapproved', $statusMap[$statusFilter]);
            }
        }

        return $query->orderByDesc('id');
    }
}
