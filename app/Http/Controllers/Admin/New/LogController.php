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
        $query = Log::query()->with('user:id,user_code');
        
        if (!empty($search)) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('message', 'LIKE', "%$search%");
            });
        }
        
        if ($request->has('search_column') && $request->search_column) {
            $query->where('role_name', $request->search_column);
        }
        
        if ($request->has('sort_column') && ($request->sort_column) == 'ASC') {
            $query->orderBy('created_at', 'ASC');
        }else{
            $query->orderBy('created_at', 'DESC');
        }
        
        $data = $query->paginate(10);
        
        return view('admin.logs.index', compact('data'));
    }
    
    public function print(Request $request)
    {
        $data = Log::with('user:id,user_code')->get();
        
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
