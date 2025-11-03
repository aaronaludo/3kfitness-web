<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Feedback;

use App\Models\Membership;
use App\Models\Schedule;
use App\Models\UserMembership;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $gym_members_count = User::where('role_id', 3)->count();
        $staffs_count = User::where('role_id', 2)->count();
        $feedbacks_count = Feedback::count();
        $memberships_count = Membership::count();
        $classes_count = Schedule::count();
        $user_membership_count = UserMembership::where('isapproved', 0)->count();
        
        $gym_members = User::where('role_id', 3)->limit(10)->get();
        $logs = Log::orderBy('id', 'desc')->limit(10)->get();

        // Build last 6 month labels (oldest -> newest)
        $months = collect(range(5, 0))->map(function ($i) {
            return Carbon::now()->subMonths($i);
        });
        $chartLabels = $months->map(fn ($d) => $d->format('M Y'));

        // Helpers
        $countByMonth = function ($query, string $dateColumn = 'created_at') use ($months) {
            $map = $months->mapWithKeys(function ($d) {
                return [$d->format('Y-m') => 0];
            });

            $rows = (clone $query)
                ->selectRaw("DATE_FORMAT($dateColumn, '%Y-%m') as ym, COUNT(*) as c")
                ->where($dateColumn, '>=', $months->first()->copy()->startOfMonth())
                ->groupBy('ym')
                ->orderBy('ym')
                ->pluck('c', 'ym');

            foreach ($rows as $ym => $c) {
                if ($map->has($ym)) {
                    $map[$ym] = (int) $c;
                }
            }
            return $map->values(); // aligned counts
        };

        // Dynamic datasets
        $membersPerMonth = $countByMonth(User::where('role_id', 3));
        $membershipsPerMonth = $countByMonth(Membership::query());
        $classesPerMonth = $countByMonth(Schedule::query());
        $approvedUserMembershipsPerMonth = $countByMonth(UserMembership::where('isapproved', 1), 'updated_at');

        return view(
            'admin.dashboard.index',
            compact(
                'gym_members_count',
                'staffs_count',
                'feedbacks_count',
                'gym_members',
                'memberships_count',
                'classes_count',
                'user_membership_count',
                'logs'
            ) + [
                'chartLabels' => $chartLabels,
                'membersPerMonth' => $membersPerMonth,
                'membershipsPerMonth' => $membershipsPerMonth,
                'classesPerMonth' => $classesPerMonth,
                'approvedUserMembershipsPerMonth' => $approvedUserMembershipsPerMonth,
            ]
        );
    }
}
