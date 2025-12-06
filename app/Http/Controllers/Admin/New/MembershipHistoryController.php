<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\MembershipPayment;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Language;

class MembershipHistoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search'        => 'nullable|string|max:255',
            'membership_id' => 'nullable|exists:memberships,id',
            'status'        => 'nullable|in:all,pending,approved,rejected',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'search_column' => 'nullable|string',
        ]);

        $filters = [
            'search'        => trim((string) $request->input('search', '')),
            'membership_id' => $request->input('membership_id'),
            'status'        => $request->input('status', 'all'),
            'start_date'    => $request->input('start_date'),
            'end_date'      => $request->input('end_date'),
            'search_column' => $request->input('search_column'),
        ];

        $statusMap = [
            'pending'  => 0,
            'approved' => 1,
            'rejected' => 2,
        ];

        if (!array_key_exists($filters['status'], $statusMap)) {
            $filters['status'] = 'all';
        }

        $allowedSearchColumns = [
            'id',
            'member_name',
            'member_code',
            'member_email',
            'member_phone',
            'member_role',
            'membership_name',
            'price',
            'status',
            'purchased_at',
            'expiration_at',
        ];
        if (!in_array($filters['search_column'], $allowedSearchColumns, true)) {
            $filters['search_column'] = null;
        }

        $dateColumns = ['created_at', 'expiration_at'];
        $rangeColumn = in_array($filters['search_column'], $dateColumns, true) ? $filters['search_column'] : 'created_at';
        $startDateObj = $filters['start_date'] ? \Carbon\Carbon::createFromFormat('Y-m-d', $filters['start_date']) : null;
        $endDateObj   = $filters['end_date'] ? \Carbon\Carbon::createFromFormat('Y-m-d', $filters['end_date']) : null;

        $baseQuery = MembershipPayment::with(['user.role', 'membership'])
            ->where('is_archive', 0);

        if ($filters['membership_id']) {
            $baseQuery->where('membership_id', $filters['membership_id']);
        }

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $searchColumn = $filters['search_column'];
            $searchTerm = $filters['search'];

            if ($searchColumn === 'id') {
                $baseQuery->where('id', $searchTerm);
            } elseif ($searchColumn === 'member_name') {
                $baseQuery->whereHas('user', function ($userQuery) use ($like) {
                    $userQuery->where(function ($nameQuery) use ($like) {
                        $nameQuery->whereRaw(
                            "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                            [$like]
                        )
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like);
                    });
                });
            } elseif ($searchColumn === 'member_code') {
                $baseQuery->whereHas('user', function ($userQuery) use ($like) {
                    $userQuery->where('user_code', 'like', $like);
                });
            } elseif ($searchColumn === 'member_email') {
                $baseQuery->whereHas('user', function ($userQuery) use ($like) {
                    $userQuery->where('email', 'like', $like);
                });
            } elseif ($searchColumn === 'member_phone') {
                $baseQuery->whereHas('user', function ($userQuery) use ($like) {
                    $userQuery->where('phone_number', 'like', $like);
                });
            } elseif ($searchColumn === 'member_role') {
                $baseQuery->whereHas('user.role', function ($roleQuery) use ($like) {
                    $roleQuery->where('name', 'like', $like);
                });
            } elseif ($searchColumn === 'membership_name') {
                $baseQuery->whereHas('membership', function ($membershipQuery) use ($like) {
                    $membershipQuery->where('name', 'like', $like);
                });
            } elseif ($searchColumn === 'price') {
                $baseQuery->where('total_price', 'like', $like)
                    ->orWhereHas('membership', function ($membershipQuery) use ($like) {
                        $membershipQuery->where('price', 'like', $like);
                    });
            } elseif ($searchColumn === 'status') {
                $normalizedStatus = strtolower(trim($searchTerm));
                $statusValue = $statusMap[$normalizedStatus] ?? null;
                if (!is_null($statusValue)) {
                    $baseQuery->where('isapproved', $statusValue);
                } elseif (is_numeric($searchTerm)) {
                    $baseQuery->where('isapproved', (int) $searchTerm);
                }
            } elseif ($searchColumn === 'purchased_at') {
                $parsed = null;
                try {
                    $parsed = \Carbon\Carbon::parse($searchTerm)->toDateString();
                } catch (\Exception $e) {
                    $parsed = null;
                }
                if ($parsed) {
                    $baseQuery->whereDate('created_at', $parsed);
                } else {
                    $baseQuery->where('created_at', 'like', $like);
                }
            } elseif ($searchColumn === 'expiration_at') {
                $parsed = null;
                try {
                    $parsed = \Carbon\Carbon::parse($searchTerm)->toDateString();
                } catch (\Exception $e) {
                    $parsed = null;
                }
                if ($parsed) {
                    $baseQuery->whereDate('expiration_at', $parsed);
                } else {
                    $baseQuery->where('expiration_at', 'like', $like);
                }
            } else {
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
        }

        if ($startDateObj || $endDateObj) {
            $start = $startDateObj ? $startDateObj->toDateString() : null;
            $end = $endDateObj ? $endDateObj->toDateString() : null;

            if ($rangeColumn === 'expiration_at') {
                if ($start) {
                    $baseQuery->whereDate('expiration_at', '>=', $start);
                }
                if ($end) {
                    $baseQuery->whereDate('expiration_at', '<=', $end);
                }
            } else {
                if ($start) {
                    $baseQuery->whereDate('created_at', '>=', $start);
                }
                if ($end) {
                    $baseQuery->whereDate('created_at', '<=', $end);
                }
            }
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
        
        $printAllPayments = (clone $historyQuery)
            ->orderByDesc('id')
            ->get();

        $stats = [
            'total'       => (clone $historyQuery)->count(),
            'members'     => (clone $historyQuery)->distinct('user_id')->count('user_id'),
            'memberships' => (clone $historyQuery)->distinct('membership_id')->count('membership_id'),
        ];

        $membershipOptions = Membership::orderBy('name')->get(['id', 'name', 'price', 'month']);

        return view('admin.history.memberships', [
            'payments'          => $payments,
            'membershipOptions' => $membershipOptions,
            'filters'           => $filters,
            'stats'             => $stats,
            'statusTallies'     => $statusTallies,
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
            'search_column' => 'nullable|string',
        ]);

        $filters = [
            'search'        => trim((string) $request->input('search', '')),
            'membership_id' => $request->input('membership_id'),
            'status'        => $request->input('status', 'all'),
            'start_date'    => $request->input('start_date'),
            'end_date'      => $request->input('end_date'),
            'search_column' => $request->input('search_column'),
        ];

        $statusMap = [
            'pending'  => 0,
            'approved' => 1,
            'rejected' => 2,
        ];

        if (!array_key_exists($filters['status'], $statusMap)) {
            $filters['status'] = 'all';
        }

        $allowedSearchColumns = [
            'id',
            'member_name',
            'member_code',
            'member_email',
            'member_phone',
            'member_role',
            'membership_name',
            'price',
            'status',
            'purchased_at',
            'expiration_at',
        ];
        if (!in_array($filters['search_column'], $allowedSearchColumns, true)) {
            $filters['search_column'] = null;
        }

        $dateColumns = ['created_at', 'expiration_at'];
        $rangeColumn = in_array($filters['search_column'], $dateColumns, true) ? $filters['search_column'] : 'created_at';
        $startDateObj = $filters['start_date'] ? \Carbon\Carbon::createFromFormat('Y-m-d', $filters['start_date']) : null;
        $endDateObj   = $filters['end_date'] ? \Carbon\Carbon::createFromFormat('Y-m-d', $filters['end_date']) : null;

        $query = MembershipPayment::with(['user.role', 'membership'])
            ->where('is_archive', 0);

        if ($filters['membership_id']) {
            $query->where('membership_id', $filters['membership_id']);
        }

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $searchColumn = $filters['search_column'];
            $searchTerm = $filters['search'];

            if ($searchColumn === 'id') {
                $query->where('id', $searchTerm);
            } elseif ($searchColumn === 'member_name') {
                $query->whereHas('user', function ($userQuery) use ($like) {
                    $userQuery->where(function ($nameQuery) use ($like) {
                        $nameQuery->whereRaw(
                            "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                            [$like]
                        )
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like);
                    });
                });
            } elseif ($searchColumn === 'member_code') {
                $query->whereHas('user', function ($userQuery) use ($like) {
                    $userQuery->where('user_code', 'like', $like);
                });
            } elseif ($searchColumn === 'member_email') {
                $query->whereHas('user', function ($userQuery) use ($like) {
                    $userQuery->where('email', 'like', $like);
                });
            } elseif ($searchColumn === 'member_phone') {
                $query->whereHas('user', function ($userQuery) use ($like) {
                    $userQuery->where('phone_number', 'like', $like);
                });
            } elseif ($searchColumn === 'member_role') {
                $query->whereHas('user.role', function ($roleQuery) use ($like) {
                    $roleQuery->where('name', 'like', $like);
                });
            } elseif ($searchColumn === 'membership_name') {
                $query->whereHas('membership', function ($membershipQuery) use ($like) {
                    $membershipQuery->where('name', 'like', $like);
                });
            } elseif ($searchColumn === 'price') {
                $query->where('total_price', 'like', $like)
                    ->orWhereHas('membership', function ($membershipQuery) use ($like) {
                        $membershipQuery->where('price', 'like', $like);
                    });
            } elseif ($searchColumn === 'status') {
                $normalizedStatus = strtolower(trim($searchTerm));
                $statusValue = $statusMap[$normalizedStatus] ?? null;
                if (!is_null($statusValue)) {
                    $query->where('isapproved', $statusValue);
                } elseif (is_numeric($searchTerm)) {
                    $query->where('isapproved', (int) $searchTerm);
                }
            } elseif ($searchColumn === 'purchased_at') {
                $parsed = null;
                try {
                    $parsed = \Carbon\Carbon::parse($searchTerm)->toDateString();
                } catch (\Exception $e) {
                    $parsed = null;
                }
                if ($parsed) {
                    $query->whereDate('created_at', $parsed);
                } else {
                    $query->where('created_at', 'like', $like);
                }
            } elseif ($searchColumn === 'expiration_at') {
                $parsed = null;
                try {
                    $parsed = \Carbon\Carbon::parse($searchTerm)->toDateString();
                } catch (\Exception $e) {
                    $parsed = null;
                }
                if ($parsed) {
                    $query->whereDate('expiration_at', $parsed);
                } else {
                    $query->where('expiration_at', 'like', $like);
                }
            } else {
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
        }

        if ($startDateObj || $endDateObj) {
            $start = $startDateObj ? $startDateObj->toDateString() : null;
            $end = $endDateObj ? $endDateObj->toDateString() : null;

            if ($rangeColumn === 'expiration_at') {
                if ($start) {
                    $query->whereDate('expiration_at', '>=', $start);
                }
                if ($end) {
                    $query->whereDate('expiration_at', '<=', $end);
                }
            } else {
                if ($start) {
                    $query->whereDate('created_at', '>=', $start);
                }
                if ($end) {
                    $query->whereDate('created_at', '<=', $end);
                }
            }
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
            $rangeLabel = ' | ' . \Carbon\Carbon::parse($filters['start_date'])->format('M d, Y') .
                ' - ' . \Carbon\Carbon::parse($filters['end_date'])->format('M d, Y');
        } elseif ($filters['start_date']) {
            $rangeLabel = ' | From ' . \Carbon\Carbon::parse($filters['start_date'])->format('M d, Y');
        } elseif ($filters['end_date']) {
            $rangeLabel = ' | Until ' . \Carbon\Carbon::parse($filters['end_date'])->format('M d, Y');
        }

        $title = 'Membership History';
        if ($filters['status'] !== 'all') {
            $title .= ' — ' . ucfirst($filters['status']);
        }

        $section->addText($title . $rangeLabel, ['bold' => true, 'size' => 16]);
        $section->addText('Generated: ' . now()->format('M d, Y H:i'));

        $filterSummary = [];
        if ($filters['search'] !== '') {
            $searchSummary = "Search='{$filters['search']}'";
            if ($filters['search_column']) {
                $searchSummary .= " (By={$filters['search_column']})";
            }
            $filterSummary[] = $searchSummary;
        }
        if ($filters['membership_id']) {
            $membership = Membership::find($filters['membership_id']);
            $filterSummary[] = 'Membership=' . ($membership ? $membership->name : $filters['membership_id']);
        }
        if ($filters['status'] !== 'all') {
            $filterSummary[] = 'Status=' . ucfirst($filters['status']);
        }

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
        $phpWord->addTableStyle('MembershipHistoryTable', $tableStyle, $firstRowStyle);
        $table = $section->addTable('MembershipHistoryTable');

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
        ];
        $headerRow = $table->addRow();
        foreach ($headers as $header) {
            $headerRow->addCell()->addText($header, ['bold' => true]);
        }

        foreach ($records as $record) {
            $member = $record->user;
            $membership = $record->membership;
            $purchasedAt = $record->created_at ? (string) $record->created_at : '—';
            $expiresAt = $record->expiration_at ? (string) $record->expiration_at : '—';
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
            ];

            foreach ($cells as $value) {
                $row->addCell()->addText((string) $value);
            }
        }

        $tempPath = storage_path('app/temp_exports');
        if (!is_dir($tempPath)) {
            @mkdir($tempPath, 0775, true);
        }

        $fileName = 'membership_history_' . now()->format('Y-m-d_H-i') . '.docx';
        $fullPath = $tempPath . DIRECTORY_SEPARATOR . $fileName;
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($fullPath);

        return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
    }
}
