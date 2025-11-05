<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MembershipPayment;
use App\Models\Membership;
use App\Models\User;
use Carbon\Carbon;

class WalkInPaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = MembershipPayment::with(['membership', 'user'])
            ->orderByDesc('created_at')
            ->paginate(10)
            ->appends($request->query());

        return view('admin.payments.index', compact('payments'));
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
}


