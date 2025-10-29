<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DietCategory;

class DietCategoryController extends Controller
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
            'id', 'title', 'description', 'protein', 'fat',
            'calories', 'ingredients', 'recipe_description', 'created_at', 'updated_at',
        ];
        
        if (!in_array($search_column, $allowed_columns)) {
            $search_column = null;
        }
        
        $data = DietCategory::query()
            ->when($search && $search_column, function ($query) use ($search, $search_column) {
                return $query->where($search_column, 'like', "%{$search}%");
            })
            ->paginate(10);
            
        return view('admin.dietcategories.index', compact('data'));
    }
    
    public function view($id)
    {
        $data = DietCategory::findOrFail($id);

        return view('admin.dietcategories.view', compact('data'));
    }

    public function create()
    {
        return view('admin.dietcategories.create');
    }

    public function edit($id)
    {
        $data = DietCategory::findOrFail($id);

        return view('admin.dietcategories.edit', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'protein' => 'required',
            'fat' => 'required',
            'calories' => 'required',
            'ingredients' => 'required',
            'recipe_description' => 'required',
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

        return redirect()->route('admin.diet-categories.index')->with('success', 'Diet Category added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'protein' => 'required',
            'fat' => 'required',
            'calories' => 'required',
            'ingredients' => 'required',
            'recipe_description' => 'required',
            'video_url' => 'nullable|mimes:mp4,mov,avi,flv|max:20480',
            'image_url' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = DietCategory::findOrFail($id);
        $data->title = $request->title;
        $data->description = $request->description;
        $data->protein = $request->protein;
        $data->fat = $request->fat;
        $data->calories = $request->calories;
        $data->ingredients = $request->ingredients;
        $data->recipe_description = $request->recipe_description;
        
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

        return redirect()->route('admin.diet-categories.index')->with('success', 'Diet Category updated successfully');
    }

    public function delete(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:diet_categories,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        
        $user = $request->user();
    
        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }
        

        $data = DietCategory::findOrFail($request->id);
        $data->delete();

        return redirect()->route('admin.diet-categories.index')->with('success', 'Diet Category deleted successfully');
    }
    
    public function print(Request $request)
    {
        $data = DietCategory::all();
        
        $fileName = "diet_categories_data_" . date('Y-m-d') . ".csv";
    
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Pragma: no-cache");
        header("Expires: 0");
    
        $output = fopen('php://output', 'w');
    
        fputcsv($output, [
            'ID', 'Title', 'Description', 'Protein', 'Fat', 'Calories', 'Ingredients', 'Recipe Description','Created At', 'Updated At',
        ]);
                    
        foreach ($data as $item) {
            fputcsv($output, [
                $item->id,
                $item->title,
                $item->description,
                $item->protein,
                $item->fat,
                $item->calories,
                $item->ingredients,
                $item->recipe_description,
                $item->created_at,
                $item->updated_at
            ]);
        }

    
        fclose($output);
        exit;
        
    }
}
