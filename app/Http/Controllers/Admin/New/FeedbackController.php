<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Feedback;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $baseQuery = Feedback::query()
            ->with('user')
            ->when($search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($startDate, function ($query, $startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query, $endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            });

        $statusTallies = [
            'all' => (clone $baseQuery)->count(),
            'unread' => (clone $baseQuery)->where('isadminread', 0)->count(),
            'read' => (clone $baseQuery)->where('isadminread', 1)->count(),
        ];

        $feedbacks = (clone $baseQuery)
            ->when($status === 'read', function ($query) {
                $query->where('isadminread', 1);
            })
            ->when($status === 'unread', function ($query) {
                $query->where('isadminread', 0);
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.feedbacks.index', [
            'feedbacks' => $feedbacks,
            'search' => $search,
            'status' => $status,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'statusTallies' => $statusTallies,
        ]);
    }

    public function create()
    {
        return view('admin.feedbacks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'description' => 'required|string|max:1000',
            'user_id' => 'nullable|exists:users,id',
            'isadminread' => 'nullable|boolean',
        ]);

        $feedback = Feedback::create([
            'user_id' => $validated['user_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'isadminread' => $request->boolean('isadminread'),
        ]);

        return redirect()
            ->route('admin.feedbacks.show', $feedback)
            ->with('success', 'Feedback created successfully.');
    }

    public function show(Feedback $feedback)
    {
        $feedback->load('user');

        if (!$feedback->isadminread) {
            $feedback->isadminread = 1;
            $feedback->save();
        }

        return view('admin.feedbacks.show', compact('feedback'));
    }

    public function edit(Feedback $feedback)
    {
        $feedback->load('user');
        return view('admin.feedbacks.edit', compact('feedback'));
    }

    public function update(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'description' => 'required|string|max:1000',
            'user_id' => 'nullable|exists:users,id',
            'isadminread' => 'nullable|boolean',
        ]);

        $feedback->update([
            'user_id' => $validated['user_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'isadminread' => $request->boolean('isadminread'),
        ]);

        return redirect()
            ->route('admin.feedbacks.show', $feedback)
            ->with('success', 'Feedback updated successfully.');
    }

    public function destroy(Feedback $feedback)
    {
        $feedback->delete();

        return redirect()
            ->route('admin.feedbacks.index')
            ->with('success', 'Feedback deleted successfully.');
    }
}
