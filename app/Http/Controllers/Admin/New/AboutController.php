<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\About;

class AboutController extends Controller
{
    public function index()
    {
        $data = About::first();
        
        return view('admin.abouts.index', compact('data'));
    }
    
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'terms_and_conditions' => 'required',
            'data_policy' => 'required',
        ]);
        
        if($request->id == 0){
            $data = new About;
        }else{
            $data = About::find($request->id);
        }
        
        $data->terms_and_conditions = $request->terms_and_conditions;
        $data->data_policy = $request->data_policy;
        $data->save();
        
        return redirect()->route('admin.abouts.index')->with('success', 'About updated successfully');
    }
}
