<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $query = Task::query()->with(['client', 'project'])->orderBy('status')->orderBy('sort_order')->orderBy('id');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $tasks = $query->paginate(20)->withQueryString();
        return view('admin.tasks.index', compact('tasks'));
    }

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
        return view('admin.tasks.board', [
            'statuses' => $statuses,
            'tasksByStatus' => $tasksByStatus,
        ]);
    }

    public function create(): View
    {
        $clients = Client::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        $projects = Project::orderBy('name')->get(['id', 'name']);
        return view('admin.tasks.create', compact('clients', 'projects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status' => 'required|in:in_development,processing,execution,completed',
            'show_on_board' => 'boolean',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'budget' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);
        $validated['show_on_board'] = $request->boolean('show_on_board');
        $validated['client_id'] = $validated['client_id'] ?? null;
        $validated['project_id'] = $validated['project_id'] ?? null;
        $validated['budget'] = isset($validated['budget']) && $validated['budget'] !== '' ? (float) $validated['budget'] : null;
        $validated['due_date'] = !empty($validated['due_date']) ? $validated['due_date'] : null;
        $validated['sort_order'] = Task::where('status', $validated['status'])->max('sort_order') + 1;

        $task = Task::create($validated);
        TelegramService::notifyTaskCreated($task);
        return redirect()->route('admin.tasks.index')->with('success', 'Задача создана.');
    }

    public function edit(Task $task): View
    {
        $clients = Client::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        $projects = Project::orderBy('name')->get(['id', 'name']);
        return view('admin.tasks.edit', compact('task', 'clients', 'projects'));
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status' => 'required|in:in_development,processing,execution,completed',
            'show_on_board' => 'boolean',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'budget' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);
        $validated['show_on_board'] = $request->boolean('show_on_board');
        $validated['client_id'] = $validated['client_id'] ?? null;
        $validated['project_id'] = $validated['project_id'] ?? null;
        $validated['budget'] = isset($validated['budget']) && $validated['budget'] !== '' ? (float) $validated['budget'] : null;
        $validated['due_date'] = !empty($validated['due_date']) ? $validated['due_date'] : null;

        $task->update($validated);
        TelegramService::notifyTaskUpdated($task);
        return redirect()->route('admin.tasks.index')->with('success', 'Задача обновлена.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $title = $task->title;
        $projectName = $task->project?->name;
        $task->delete();
        TelegramService::notifyTaskDeleted($title, $projectName);
        return redirect()->route('admin.tasks.index')->with('success', 'Задача удалена.');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:in_development,processing,execution,completed',
        ]);
        $task->update(['status' => $validated['status']]);
        TelegramService::notifyTaskUpdated($task);
        return back()->with('success', 'Статус обновлён.');
    }
}
