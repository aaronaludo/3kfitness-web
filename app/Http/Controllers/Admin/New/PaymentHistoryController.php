<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Language;

class PaymentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search'        => 'nullable|string|max:255',
            'membership_id' => 'nullable|exists:memberships,id',
            'status'        => 'nullable|in:all,pending,approved,rejected',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'show_archived' => 'nullable|boolean',
        ]);

        $filters = [
            'search'        => trim((string) $request->input('search', '')),
            'membership_id' => $request->input('membership_id'),
            'status'        => $request->input('status', 'all'),
            'start_date'    => $request->input('start_date'),
            'end_date'      => $request->input('end_date'),
            'show_archived' => $request->boolean('show_archived'),
        ];

        $statusMap = [
            'pending'  => 0,
            'approved' => 1,
            'rejected' => 2,
        ];

        if (!array_key_exists($filters['status'], $statusMap)) {
            $filters['status'] = 'all';
        }

        $baseQuery = MembershipPayment::with(['user.role', 'membership'])
            ->where('is_archive', $filters['show_archived'] ? 1 : 0);

        if ($filters['membership_id']) {
            $baseQuery->where('membership_id', $filters['membership_id']);
        }

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';

            $baseQuery->where(function ($query) use ($like) {
                $query
                    ->whereHas('user', function ($userQuery) use ($like) {
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
                    })
                    ->orWhereHas('membership', function ($membershipQuery) use ($like) {
                        $membershipQuery->where('name', 'like', $like);
                    })
                    ->orWhere('id', 'like', $like);
            });
        }

        if ($filters['start_date']) {
            $baseQuery->whereDate('created_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $baseQuery->whereDate('created_at', '<=', $filters['end_date']);
        }

        $statusTallies = [
            'all'      => (clone $baseQuery)->count(),
            'pending'  => (clone $baseQuery)->where('isapproved', $statusMap['pending'])->count(),
            'approved' => (clone $baseQuery)->where('isapproved', $statusMap['approved'])->count(),
            'rejected' => (clone $baseQuery)->where('isapproved', $statusMap['rejected'])->count(),
        ];

        $historyQuery = clone $baseQuery;
        if ($filters['status'] !== 'all') {
            $historyQuery->where('isapproved', $statusMap[$filters['status']]);
        }

        $queryParams = $request->query();

        $payments = (clone $historyQuery)
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($queryParams);

        $stats = [
            'total'       => (clone $historyQuery)->count(),
            'members'     => (clone $historyQuery)->distinct('user_id')->count('user_id'),
            'memberships' => (clone $historyQuery)->distinct('membership_id')->count('membership_id'),
        ];

        $membershipOptions = Membership::orderBy('name')->get(['id', 'name', 'price', 'month']);
        $payrollRuns = PayrollRun::with('user')
            ->orderByDesc('processed_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $printAllPayments = (clone $historyQuery)
            ->orderByDesc('id')
            ->get();

        return view('admin.history.payments', [
            'payments'          => $payments,
            'membershipOptions' => $membershipOptions,
            'filters'           => $filters,
            'stats'             => $stats,
            'statusTallies'     => $statusTallies,
            'payrollRuns'       => $payrollRuns,
            'printAllPayments'  => $printAllPayments,
        ]);
    }

    public function print(Request $request)
    {
        $request->validate([
            'search'        => 'nullable|string|max:255',
            'membership_id' => 'nullable|exists:memberships,id',
            'status'        => 'nullable|in:all,pending,approved,rejected',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'show_archived' => 'nullable|boolean',
        ]);

        $filters = [
            'search'        => trim((string) $request->input('search', '')),
            'membership_id' => $request->input('membership_id'),
            'status'        => $request->input('status', 'all'),
            'start_date'    => $request->input('start_date'),
            'end_date'      => $request->input('end_date'),
            'show_archived' => $request->boolean('show_archived'),
        ];

        $statusMap = [
            'pending'  => 0,
            'approved' => 1,
            'rejected' => 2,
        ];

        if (!array_key_exists($filters['status'], $statusMap)) {
            $filters['status'] = 'all';
        }

        $query = MembershipPayment::with(['user.role', 'membership'])
            ->where('is_archive', $filters['show_archived'] ? 1 : 0);

        if ($filters['membership_id']) {
            $query->where('membership_id', $filters['membership_id']);
        }

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';

            $query->where(function ($builder) use ($like) {
                $builder
                    ->whereHas('user', function ($userQuery) use ($like) {
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
                    })
                    ->orWhereHas('membership', function ($membershipQuery) use ($like) {
                        $membershipQuery->where('name', 'like', $like);
                    })
                    ->orWhere('id', 'like', $like);
            });
        }

        if ($filters['start_date']) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if ($filters['status'] !== 'all') {
            $query->where('isapproved', $statusMap[$filters['status']]);
        }

        $records = $query->orderByDesc('id')->get();

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

        $rangeLabel = '';
        if ($filters['start_date'] && $filters['end_date']) {
            $rangeLabel = ' | ' . Carbon::parse($filters['start_date'])->format('M d, Y') .
                ' - ' . Carbon::parse($filters['end_date'])->format('M d, Y');
        } elseif ($filters['start_date']) {
            $rangeLabel = ' | From ' . Carbon::parse($filters['start_date'])->format('M d, Y');
        } elseif ($filters['end_date']) {
            $rangeLabel = ' | Until ' . Carbon::parse($filters['end_date'])->format('M d, Y');
        }

        $title = $filters['show_archived'] ? 'Archived Payments History' : 'Payments History';
        if ($filters['status'] !== 'all') {
            $title .= ' — ' . ucfirst($filters['status']);
        }

        $section->addText($title . $rangeLabel, ['bold' => true, 'size' => 16]);
        $section->addText('Generated: ' . now()->format('M d, Y H:i'));

        $filterSummary = [];
        if ($filters['search'] !== '') {
            $filterSummary[] = "Search='{$filters['search']}'";
        }
        if ($filters['membership_id']) {
            $membership = Membership::find($filters['membership_id']);
            $filterSummary[] = 'Membership=' . ($membership ? $membership->name : $filters['membership_id']);
        }
        if ($filters['status'] !== 'all') {
            $filterSummary[] = 'Status=' . ucfirst($filters['status']);
        }
        $filterSummary[] = 'Showing ' . ($filters['show_archived'] ? 'archived' : 'active') . ' records';

        if (!empty($filterSummary)) {
            $section->addText('Filters: ' . implode('; ', $filterSummary));
        }
        $section->addTextBreak(1);

        $tableStyle = [
            'borderColor' => '777777',
            'borderSize'  => 6,
            'cellMargin'  => 80,
        ];
        $firstRowStyle = ['bgColor' => 'DDDDDD'];
        $phpWord->addTableStyle('PaymentHistoryTable', $tableStyle, $firstRowStyle);
        $table = $section->addTable('PaymentHistoryTable');

        $headers = [
            'ID',
            'Member',
            'Member Code',
            'Role',
            'Email',
            'Phone',
            'Membership',
            'Price',
            'Status',
            'Purchased',
            'Expires',
            'Archive',
        ];
        $headerRow = $table->addRow();
        foreach ($headers as $header) {
            $headerRow->addCell()->addText($header, ['bold' => true]);
        }

        foreach ($records as $record) {
            $member = $record->user;
            $membership = $record->membership;
            $purchasedAt = $record->created_at ? Carbon::parse($record->created_at)->format('Y-m-d H:i') : '—';
            $expiresAt = $record->expiration_at ? Carbon::parse($record->expiration_at)->format('Y-m-d H:i') : '—';
            $statusMeta = [
                0 => 'Pending',
                1 => 'Approved',
                2 => 'Rejected',
            ];

            $row = $table->addRow();
            $cells = [
                $record->id,
                $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : 'Unknown member',
                $member->user_code ?? '—',
                $member && $member->role ? ($member->role->name ?? '') : '',
                $member ? ($member->email ?? '—') : '—',
                $member ? ($member->phone_number ?? '—') : '—',
                $membership ? $membership->name : 'Unknown membership',
                $membership && isset($membership->price) ? number_format((float) $membership->price, 2) : '—',
                $statusMeta[$record->isapproved] ?? 'Pending',
                $purchasedAt,
                $expiresAt,
                (int) ($record->is_archive ?? 0) === 1 ? 'Archived' : 'Active',
            ];

            foreach ($cells as $value) {
                $row->addCell()->addText((string) $value);
            }
        }

        $tempPath = storage_path('app/temp_exports');
        if (!is_dir($tempPath)) {
            @mkdir($tempPath, 0775, true);
        }

        $fileName = 'payment_history_' . now()->format('Y-m-d_H-i') . '.docx';
        $fullPath = $tempPath . DIRECTORY_SEPARATOR . $fileName;
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($fullPath);

        return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
    }
}
