<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MotivationalVideo;

class MotivationalVideoController extends Controller
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
            'id', 'title', 'description', 'video', 'created_at', 'updated_at',
        ];
        
        if (!in_array($search_column, $allowed_columns)) {
            $search_column = null;
        }
        
        $data = MotivationalVideo::query()
            ->when($search && $search_column, function ($query) use ($search, $search_column) {
                return $query->where($search_column, 'like', "%{$search}%");
            })
            ->paginate(10);
            
        return view('admin.motivationalvideos.index', compact('data'));
    }
    
    public function view($id)
    {
        $data = MotivationalVideo::findOrFail($id);

        return view('admin.motivationalvideos.view', compact('data'));
    }

    public function create()
    {
        return view('admin.motivationalvideos.create');
    }

    public function edit($id)
    {
        $data = MotivationalVideo::findOrFail($id);

        return view('admin.motivationalvideos.edit', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'video' => 'nullable|mimes:mp4,mov,avi,flv|max:20480',
        ]);

        $data = new MotivationalVideo;
        $data->title = $request->title;
        $data->description = $request->description;

        $destinationPath = public_path('uploads');

         if ($request->hasFile('video')) {
            $video = $request->file('video');
            $videoName = time() . '_video.' . $video->getClientOriginalExtension();
            $video->move($destinationPath, $videoName);
            $data->video = 'uploads/' . $videoName;
        }
        
        $data->save();

        return redirect()->route('admin.motivational-videos.index')->with('success', 'Motivational Video added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'video' => 'nullable|mimes:mp4,mov,avi,flv|max:20480',
        ]);

        $data = MotivationalVideo::findOrFail($id);
        $data->title = $request->title;
        $data->description = $request->description;

        $destinationPath = public_path('uploads');

         if ($request->hasFile('video')) {
            $video = $request->file('video');
            $videoName = time() . '_video.' . $video->getClientOriginalExtension();
            $video->move($destinationPath, $videoName);
            $data->video = 'uploads/' . $videoName;
        }
        
        $data->save();

        return redirect()->route('admin.motivational-videos.index')->with('success', 'Motivational Video updated successfully');
    }

    public function delete(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:motivational_videos,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        
        $user = $request->user();
    
        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }
        

        $data = MotivationalVideo::findOrFail($request->id);
        $data->delete();

        return redirect()->route('admin.motivational-videos.index')->with('success', 'Motivational Video deleted successfully');
    }
    
public function print(Request $request)
    {
        $data = MotivationalVideo::all();
        
        $fileName = "motivatinal_videos_data_" . date('Y-m-d') . ".csv";
    
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Pragma: no-cache");
        header("Expires: 0");
    
        $output = fopen('php://output', 'w');
    
        fputcsv($output, [
            'ID', 'Title', 'Description', 'Video', 'Created At', 'Updated At',
        ]);
                    
        foreach ($data as $item) {
            fputcsv($output, [
                $item->id,
                $item->title,
                $item->description,
                $item->video,
                $item->created_at,
                $item->updated_at
            ]);
        }

    
        fclose($output);
        exit;
        
    }
}
