<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkoutCategory;

class WorkoutCategoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search_column' => 'nullable|string',
            'name' => 'nullable|string|max:255',
        ]);
        
        $search = $request->name;
        $search_column = $request->search_column;
        
        $allowed_columns = [
            'id', 'title', 'trainer', 'calories', 'equipment',
            'net_duration', 'benefits', 'session_details', 'created_at', 'updated_at',
        ];
        
        if (!in_array($search_column, $allowed_columns)) {
            $search_column = null;
        }
        
        $data = WorkoutCategory::query()
            ->when($search && $search_column, function ($query) use ($search, $search_column) {
                if ($search_column === 'net_duration') {
                    // i want to query the collect(json_decode($data->session_details, true))->sum('duration')
                }else {
                    return $query->where($search_column, 'like', "%{$search}%");
                }
            })
            ->paginate(10);
            
        return view('admin.workoutcategories.index', compact('data'));
    }
    
    public function view($id)
    {
        $data = WorkoutCategory::findOrFail($id);

        return view('admin.workoutcategories.view', compact('data'));
    }

    public function create()
    {
        return view('admin.workoutcategories.create');
    }

    public function edit($id)
    {
        $data = WorkoutCategory::findOrFail($id);

        return view('admin.workoutcategories.edit', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'calories' => 'required',
            'equipment' => 'required',
            'benefits' => 'required',
            'session_details' => 'required',
            'video_url' => 'nullable|mimes:mp4,mov,avi,flv|max:20480',
            'image_url' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        $data = new WorkoutCategory;
        $data->title = $request->title;
        $data->trainer_id = 0; // Set default trainer ID if needed
        $data->calories = $request->calories;
        $data->equipment = $request->equipment;
        $data->benefits = $request->benefits;
        $data->session_details = $request->session_details;
        $data->user_id = auth()->guard('admin')->user()->id;
        $data->user_role = 'Admin';
        
        $destinationPath = public_path('uploads');
    
        if ($request->hasFile('video_url')) {
            $video_url = $request->file('video_url');
            $videoUrlName = time() . '_video.' . $video_url->getClientOriginalExtension();
            $video_url->move($destinationPath, $videoUrlName);
            $data->video_url = 'uploads/' . $videoUrlName;
        }
    
        if ($request->hasFile('image_url')) {
            $image_url = $request->file('image_url');
            $imageUrlName = time() . '_image.' . $image_url->getClientOriginalExtension();
            $image_url->move($destinationPath, $imageUrlName);
            $data->image_url = 'uploads/' . $imageUrlName;
        }
    
        $data->save();
    
        return redirect()->route('admin.workout-categories.index')
                         ->with('success', 'Workout Category added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'calories' => 'required',
            'equipment' => 'required',
            'benefits' => 'required',
            'session_details' => 'required',
            'video_url' => 'nullable|mimes:mp4,mov,avi,flv|max:20480',
            'image_url' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        $data = WorkoutCategory::findOrFail($id);
        $data->title = $request->title;
        $data->calories = $request->calories;
        $data->equipment = $request->equipment;
        $data->benefits = $request->benefits;
        $data->session_details = $request->session_details;
        $data->user_id = auth()->guard('admin')->user()->id;
        $data->user_role = 'Admin';
        
        $destinationPath = public_path('uploads');
    
        if ($request->hasFile('video_url')) {
            if ($data->video_url && file_exists(public_path($data->video_url))) {
                unlink(public_path($data->video_url));
            }
    
            $video_url = $request->file('video_url');
            $videoUrlName = time() . '_video.' . $video_url->getClientOriginalExtension();
            $video_url->move($destinationPath, $videoUrlName);
            $data->video_url = 'uploads/' . $videoUrlName;
        }
    
        if ($request->hasFile('image_url')) {
            if ($data->image_url && file_exists(public_path($data->image_url))) {
                unlink(public_path($data->image_url));
            }
    
            $image_url = $request->file('image_url');
            $imageUrlName = time() . '_image.' . $image_url->getClientOriginalExtension();
            $image_url->move($destinationPath, $imageUrlName);
            $data->image_url = 'uploads/' . $imageUrlName;
        }
    
        $data->save();
    
        return redirect()->route('admin.workout-categories.index')
                         ->with('success', 'Workout Category updated successfully');
    }

    public function delete(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:workout_categories,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        
        $user = $request->user();
    
        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }
        

        $data = WorkoutCategory::findOrFail($request->id);
        $data->delete();

        return redirect()->route('admin.workout-categories.index')->with('success', 'Workout Category deleted successfully');
    }
    
    public function print(Request $request)
    {
        $data = WorkoutCategory::all();
        
        $fileName = "workout_categories_data_" . date('Y-m-d') . ".csv";
    
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Pragma: no-cache");
        header("Expires: 0");
    
        $output = fopen('php://output', 'w');
    
        fputcsv($output, [
            'ID', 'Title', 'Trainer', 'Calories', 'Equipment', 'Net Duration' ,'Benefits','Session Details','Created At', 'Updated At',
        ]);
                    
        foreach ($data as $item) {
            fputcsv($output, [
                $item->id,
                $item->title,
                $item->trainer_id,
                $item->calories,
                $item->equipment,
                collect(json_decode($item->session_details, true))->sum('duration'),
                $item->benefits,
                $item->session_details,
                $item->created_at,
                $item->updated_at
            ]);
        }

    
        fclose($output);
        exit;
        
    }
}
