<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DietCategory;

class DietCategoryController extends Controller
{
    public function index(Request $request)
    {
        $data = DietCategory::all();
        
        if ($data->isEmpty()) {
            return response()->json(['message' => 'Diet Categories are empty']);
        }
    
        $formattedData = $data->map(function ($category) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
                'protein' => $category->protein,
                'fat' => $category->fat,
                'calories' => $category->calories,
                'ingredients' => json_decode($category->ingredients, true),
                'recipe_description' => $category->recipe_description,
                'video_url' => asset($category->video_url),
                'image_url' => asset($category->image_url),
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
                'user_id' => $category->user_id,
                'user_role' => $category->user_role,
            ];
        });
    
        return response()->json(['data' => $formattedData]);
    }
    
    public function create(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'protein' => 'required|numeric|min:0',
            'fat' => 'required|numeric|min:0',
            'calories' => 'required|numeric|min:0',
            'ingredients' => 'required|json',
            'recipe_description' => 'required|string',
            'video_url' => 'nullable|mimes:mp4,mov,avi,flv|max:20480',
            'image_url' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = new DietCategory;
        $data->title = $request->title;
        $data->description = $request->description;
        $data->protein = $request->protein;
        $data->fat = $request->fat;
        $data->calories = $request->calories;
        $data->ingredients = $request->ingredients;
        $data->recipe_description = $request->recipe_description;
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
            'message' => 'Diet Category created successfully.',
            'data' => $data,
        ]);
    }
    
    public function delete(Request $request)
    {
        $diet_category_id = $request->id;
        $user = $request->user();
        
        $data = DietCategory::find($diet_category_id);
        
        if (!$data) {
            return response()->json(['message' => 'Diet Category not found']);
        }
        
        $data->delete();
        
        return response()->json([
            'message' => 'Diet Category deleted successfully.',
        ]);
    }
}
