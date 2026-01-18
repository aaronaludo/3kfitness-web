<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MembershipPayment;
use App\Models\Membership;
use App\Models\User;
use App\Mail\MembershipApproved;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log as Logger;
use Illuminate\Support\Facades\Mail;
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

        $staffApprovers = User::where('role_id', 2)
            ->get(['user_code', 'first_name', 'last_name'])
            ->map(function ($user) {
                $fullName = strtolower(trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));
                return [
                    'code' => strtolower($user->user_code ?? ''),
                    'name' => $fullName,
                ];
            });

        $resolveApproverCode = function ($createdBy) use ($staffApprovers) {
            $needle = strtolower(trim($createdBy ?? ''));
            if ($needle === '') {
                return '';
            }

            $byCode = $staffApprovers->firstWhere('code', $needle);
            if ($byCode && !empty($byCode['code'])) {
                return $byCode['code'];
            }

            $byName = $staffApprovers->firstWhere('name', $needle);
            if ($byName && !empty($byName['code'])) {
                return $byName['code'];
            }

            return $createdBy;
        };

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
            'resolveApproverCode' => $resolveApproverCode,
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

        $data = MembershipPayment::with(['user', 'membership'])->findOrFail($request->id);
        $previousStatus = (int) $data->isapproved;
        $nextStatus = (int) $request->isapproved;

        $data->isapproved = $nextStatus;
        $data->created_by = $request->user()->user_code ?: trim($request->user()->first_name . " " .  $request->user()->last_name);
        $data->save();

        if ($nextStatus === 1 && $previousStatus !== 1) {
            $member = $data->user;
            if ($member && !empty($member->email)) {
                try {
                    Mail::to($member->email)->send(new MembershipApproved($data, $data->created_by));
                    Logger::info('Membership approval email sent.', [
                        'membership_payment_id' => $data->id,
                        'user_id' => $member->id,
                    ]);
                } catch (\Throwable $e) {
                    Logger::error('Failed to send membership approval email.', [
                        'membership_payment_id' => $data->id,
                        'user_id' => $member->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

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

        $parentMembership = $data->membership;
        $parentUser = $data->user;

        if ($parentUser && (int) $parentUser->is_archive === 1) {
            return redirect()->back()->with('error', 'Cannot restore this payment while its member is archived. Restore the member first.');
        }
        if ($parentMembership && (int) $parentMembership->is_archive === 1) {
            return redirect()->back()->with('error', 'Cannot restore this payment while its membership is archived. Restore the membership first.');
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
     * Apply the all-in-one search across membership payment list columns.
     */
    protected function applyMembershipPaymentSearch($query, string $keyword)
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return $query;
        }

        $like = '%' . $keyword . '%';
        $lowerKeyword = strtolower($keyword);
        $numericKeyword = is_numeric($keyword) ? $keyword : null;
        $integerKeyword = ctype_digit($keyword) ? (int) $keyword : null;
        $statusMap = [
            'pending' => 0,
            'approved' => 1,
            'rejected' => 2,
        ];

        return $query->where(function ($query) use ($like, $lowerKeyword, $numericKeyword, $integerKeyword, $statusMap) {
            if ($integerKeyword !== null) {
                $query->orWhere('id', $integerKeyword);
            }

            $query->orWhere('created_by', 'like', $like)
                ->orWhere('expiration_at', 'like', $like)
                ->orWhere('created_at', 'like', $like)
                ->orWhere('updated_at', 'like', $like);

            $query->orWhereHas('user', function ($userQuery) use ($like) {
                $userQuery->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$like])
                    ->orWhere('email', 'like', $like)
                    ->orWhere('user_code', 'like', $like);
            });

            $query->orWhereHas('membership', function ($membershipQuery) use ($like, $numericKeyword) {
                $membershipQuery->where('name', 'like', $like)
                    ->orWhere('currency', 'like', $like);

                if ($numericKeyword !== null) {
                    $membershipQuery->orWhere('price', $numericKeyword);
                }
            });

            $query->orWhereHas('user.userSchedules.schedule', function ($scheduleQuery) use ($like) {
                $scheduleQuery->where('name', 'like', $like)
                    ->orWhere('class_code', 'like', $like);
            });

            if ($integerKeyword !== null && $integerKeyword >= 0 && $integerKeyword <= 2) {
                $query->orWhere('isapproved', $integerKeyword);
            }

            foreach ($statusMap as $label => $value) {
                if (strpos($lowerKeyword, $label) !== false || strpos($label, $lowerKeyword) === 0) {
                    $query->orWhere('isapproved', $value);
                }
            }
        });
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

        if ($keyword !== null && trim($keyword) !== '') {
            $this->applyMembershipPaymentSearch($query, $keyword);
        }

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
