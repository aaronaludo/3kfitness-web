<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;

class BannerController extends Controller
{
    public function index()
    {
        $data = Banner::first();
        
        return view('admin.banners.index', compact('data'));
    }
    
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'title' => 'required',
            'description' => 'required',
            'button_text' => 'required',
            'pricing_text' => 'required',
        ]);
        
        if($request->id == 0){
            $data = new Banner;
        }else{
            $data = Banner::find($request->id);
        }
        
        if ($request->hasFile('background_image')) {
            $image = $request->file('background_image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            
            $destinationPath = public_path('uploads');
            $image->move($destinationPath, $imageName);
            $data->background_image = 'uploads/' . $imageName;
        }
        
        $data->title = $request->title;
        $data->description = $request->description;
        $data->button_text = $request->button_text;
        $data->pricing_text = $request->pricing_text;
        $data->save();
        
        return redirect()->route('admin.banners.index')->with('success', 'Banner updated successfully');
    }
}
