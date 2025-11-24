<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Membership;
use Illuminate\Support\Facades\Schema;

class MembershipController extends Controller
{
    public function index()
    {
        $memberships = Membership::select('id', 'name', 'currency', 'description', 'price', 'year', 'month', 'week', 'class_limit_per_month')
            ->when(Schema::hasColumn('memberships', 'is_archive'), fn ($query) => $query->where('is_archive', 0))
            ->orderBy('price')
            ->get();

        if ($memberships->isEmpty()) {
            return response()->json(['message' => 'Membership list is empty']);
        }

        return response()->json(['data' => $memberships]);
    }
}
