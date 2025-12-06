<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Log;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $searchColumn = $request->input('search_column');
        $roleFilter = $request->input('role_filter');
        $sortDirection = strtoupper($request->input('sort_column', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $allowedColumns = ['id', 'message', 'role_name', 'user_code', 'created_at', 'updated_at'];
        $roleFilters = ['Admin', 'Staff', 'Member', 'Trainer'];
        $isRoleFilter = in_array($roleFilter, $roleFilters, true);
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }

        $query = Log::query()->with('user:id,user_code');

        // Role filter chips (no search term needed)
        if ($isRoleFilter) {
            $query->where('role_name', $roleFilter);
        }

        if (!empty($search)) {
            $query->where(function ($subQuery) use ($search, $searchColumn) {
                if ($searchColumn === 'id') {
                    return $subQuery->where('id', $search);
                }

                if ($searchColumn === 'role_name') {
                    return $subQuery->where('role_name', 'LIKE', "%{$search}%");
                }

                if ($searchColumn === 'user_code') {
                    return $subQuery->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('user_code', 'LIKE', "%{$search}%");
                    });
                }

                if (in_array($searchColumn, ['created_at', 'updated_at'], true)) {
                    return $subQuery->where($searchColumn, 'LIKE', "%{$search}%");
                }

                // Default to message search or when search_column is message/blank
                $subQuery->where('message', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy('created_at', $sortDirection);

        // Capture full result set before pagination so print-all isn't limited to the current page.
        $allLogs = (clone $query)->get();
        $data = $query->paginate(10);
        
        return view('admin.logs.index', [
            'data' => $data,
            'printAllLogs' => $allLogs,
        ]);
    }
    
    public function print(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string',
            'search_column' => 'nullable|string',
            'role_filter' => 'nullable|string',
            'sort_column' => 'nullable|in:ASC,DESC',
        ]);

        $search = $request->input('search');
        $roleFilter = $request->input('role_filter');
        $searchColumn = $request->input('search_column');
        $allowedRoles = ['Admin', 'Staff', 'Member', 'Trainer'];
        $allowedColumns = ['id', 'message', 'role_name', 'user_code', 'created_at', 'updated_at'];
        $isRoleFilter = in_array($roleFilter, $allowedRoles, true);
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }
        $sortDirection = strtoupper($request->input('sort_column', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $data = Log::with('user:id,user_code')
            ->when($isRoleFilter, function ($query) use ($roleFilter) {
                $query->where('role_name', $roleFilter);
            })
            ->when(!empty($search), function ($query) use ($search, $searchColumn) {
                $query->where(function ($subQuery) use ($search, $searchColumn) {
                    if ($searchColumn === 'id') {
                        return $subQuery->where('id', $search);
                    }

                    if ($searchColumn === 'role_name') {
                        return $subQuery->where('role_name', 'LIKE', "%{$search}%");
                    }

                    if ($searchColumn === 'user_code') {
                        return $subQuery->whereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('user_code', 'LIKE', "%{$search}%");
                        });
                    }

                    if (in_array($searchColumn, ['created_at', 'updated_at'], true)) {
                        return $subQuery->where($searchColumn, 'LIKE', "%{$search}%");
                    }

                    $subQuery->where('message', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('created_at', $sortDirection)
            ->get();
        
        $fileName = "logs_data_" . date('Y-m-d') . ".csv";
    
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Pragma: no-cache");
        header("Expires: 0");
    
        $output = fopen('php://output', 'w');
    
        fputcsv($output, [
            'ID', 'Message', 'Role Name', 'User Code', 'Created At', 'Updated At',
        ]);
                    
        foreach ($data as $item) {
            fputcsv($output, [
                $item->id,
                $item->message,
                $item->role_name,
                optional($item->user)->user_code,
                $item->created_at,
                $item->updated_at
            ]);
        }

    
        fclose($output);
        exit;
        
    }
}
