<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Persist an admin-facing activity log entry when an authenticated admin performs an action.
     */
    protected function logAdminActivity(string $action, ?string $roleName = null): void
    {
        $admin = auth()->guard('admin')->user();

        if (!$admin) {
            return;
        }

        $fullName = trim("{$admin->first_name} {$admin->last_name}");

        $log = new Log();
        $log->message = ($fullName !== '' ? $fullName : 'Admin') . ' ' . $action;
        $log->role_name = $roleName ?? $this->resolveAdminRoleName($admin->role_id, optional($admin->role)->name);
        $log->save();
    }

    /**
     * Determine a human readable role label for an admin user.
     */
    protected function resolveAdminRoleName(?int $roleId, ?string $roleName): string
    {
        if ($roleName) {
            return $roleName;
        }

        return match ($roleId) {
            1 => 'Admin',
            2 => 'Staff',
            4 => 'Super Admin',
            default => 'Admin',
        };
    }
}
