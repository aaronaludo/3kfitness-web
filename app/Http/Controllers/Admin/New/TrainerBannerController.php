<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TrainerBanner;

class TrainerBannerController extends Controller
{
    public function index()
    {
        $data = TrainerBanner::first();

        return view('admin.trainer-banners.index', compact('data'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'title' => 'required',
            'description' => 'required',
            'button_text' => 'required',
            'pricing_text' => 'required',
            'tag_icon' => 'nullable|string|max:50',
            'tag_text' => 'nullable|string|max:255',
            'schedule_button_icon' => 'nullable|string|max:50',
            'schedule_button_text' => 'nullable|string|max:255',
            'footnote_prefix' => 'nullable|string|max:255',
            'footnote_price' => 'nullable|string|max:255',
            'footnote_suffix' => 'nullable|string|max:255',
            'stat_one_icon' => 'nullable|string|max:50',
            'stat_one_value' => 'nullable|string|max:255',
            'stat_one_label' => 'nullable|string|max:255',
            'stat_two_icon' => 'nullable|string|max:50',
            'stat_two_value' => 'nullable|string|max:255',
            'stat_two_label' => 'nullable|string|max:255',
            'stat_three_icon' => 'nullable|string|max:50',
            'stat_three_value' => 'nullable|string|max:255',
            'stat_three_label' => 'nullable|string|max:255',
        ]);

        $data = $request->id == 0
            ? new TrainerBanner
            : TrainerBanner::find($request->id);

        if (!$data) {
            $data = new TrainerBanner;
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
        $data->tag_icon = $request->tag_icon;
        $data->tag_text = $request->tag_text;
        $data->schedule_button_icon = $request->schedule_button_icon;
        $data->schedule_button_text = $request->schedule_button_text;
        $data->footnote_prefix = $request->footnote_prefix;
        $data->footnote_price = $request->footnote_price;
        $data->footnote_suffix = $request->footnote_suffix;
        $data->stat_one_icon = $request->stat_one_icon;
        $data->stat_one_value = $request->stat_one_value;
        $data->stat_one_label = $request->stat_one_label;
        $data->stat_two_icon = $request->stat_two_icon;
        $data->stat_two_value = $request->stat_two_value;
        $data->stat_two_label = $request->stat_two_label;
        $data->stat_three_icon = $request->stat_three_icon;
        $data->stat_three_value = $request->stat_three_value;
        $data->stat_three_label = $request->stat_three_label;
        $data->save();

        return redirect()->route('admin.trainer-banners.index')->with('success', 'Trainer banner updated successfully');
    }
}
