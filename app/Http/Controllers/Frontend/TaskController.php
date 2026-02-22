<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function board(): View
    {
        $statuses = Task::statusesForBoard();
        $tasksByStatus = [];
        foreach ($statuses as $status) {
            $tasksByStatus[$status] = Task::where('status', $status)
                ->with('client')
                ->where('show_on_board', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }
        return view('frontend.tasks.board', [
            'statuses' => $statuses,
            'tasksByStatus' => $tasksByStatus,
        ]);
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:in_development,processing,execution,completed',
        ]);
        $task->update(['status' => $validated['status']]);
        return response()->json(['ok' => true, 'status' => $task->status]);
    }
}
