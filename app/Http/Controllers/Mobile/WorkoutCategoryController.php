<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkoutCategory;

class WorkoutCategoryController extends Controller
{
    public function index(Request $request)
    {
        $data = WorkoutCategory::all();
        
        if ($data->isEmpty()) {
            return response()->json(['message' => 'Workout Categories is Empty']);
        }
    
        $formattedData = $data->map(function ($category) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'trainer_id' => $category->trainer_id,
                'calories' => $category->calories,
                'equipment' => $category->equipment,
                'benefits' => json_decode($category->benefits, true),
                'session_details' => json_decode($category->session_details, true),
                'video_url' => asset($category->video_url),
                'image_url' => asset($category->image_url),
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
                'user_id' => $category->user_id,
                'user_role' => $category->user_role
            ];
        });
    
        return response()->json(['data' => $formattedData]);
    }
    
    public function create(Request $request)
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
        $data->trainer_id = 0;
        $data->calories = $request->calories;
        $data->equipment = $request->equipment;
        $data->benefits = $request->benefits;
        $data->session_details = $request->session_details;
        $data->user_id = $request->user()->id;
        $data->user_role = 'Trainer';
        
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
        
        return response()->json([
            'message' => 'Workout Category created successfully.',
            'data' => $data,
        ]);
    }
    
    public function delete(Request $request)
    {
        $workout_category_id = $request->id;
        $user = $request->user();
        
        $data = WorkoutCategory::find($workout_category_id);
        
        if (!$data) {
            return response()->json(['message' => 'Workout Category not found']);
        }
        
        $data->delete();
        
        return response()->json([
            'message' => 'Workout Category deleted successfully.',
        ]);
    }
}
