<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\BalanceTransaction;
use App\Models\Client;
use App\Models\ClientProjectInvestment;
use App\Models\ConstructionStage;
use App\Models\ConstructionStageComment;
use App\Models\ConstructionStagePhoto;
use App\Models\Project;
use App\Services\TelegramService;
use App\Models\ProjectDocument;
use App\Models\ProjectDocumentField;
use App\Models\PushSubscription;
use App\Models\Task;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CabinetController extends Controller
{
    private function getClient(Request $request): Client
    {
        $clientId = $request->session()->get('client_id');
        if (!$clientId) {
            abort(403);
        }
        return Client::findOrFail($clientId);
    }

    public function dashboard(Request $request): View
    {
        $client = $this->getClient($request);
        $client->load('balanceTransactions.product');
        return view('cabinet.dashboard', compact('client'));
    }

    public function transactions(Request $request): View
    {
        $client = $this->getClient($request);
        $client->load('balanceTransactions.product');
        return view('cabinet.transactions', compact('client'));
    }

    public function board(Request $request): View
    {
        $this->getClient($request);
        $statuses = Task::statusesForBoard();
        $responsibleId = $request->get('responsible_id');
        $clients = \App\Models\Client::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        $query = Task::where('show_on_board', true)->with('client');
        if ($responsibleId !== null && $responsibleId !== '') {
            $query->where('client_id', $responsibleId);
        }

        $tasksByStatus = [];
        foreach ($statuses as $status) {
            $tasksByStatus[$status] = (clone $query)
                ->where('status', $status)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        return view('cabinet.board', [
            'statuses' => $statuses,
            'tasksByStatus' => $tasksByStatus,
            'clients' => $clients,
            'filterResponsibleId' => $responsibleId,
        ]);
    }

    public function createTask(Request $request): View
    {
        $this->getClient($request);
        $clients = Client::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        $projects = Project::orderBy('name')->get(['id', 'name']);
        return view('cabinet.tasks.create', compact('clients', 'projects'));
    }

    public function storeTask(Request $request): RedirectResponse
    {
        $this->getClient($request);
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
        return redirect()->route('cabinet.board')->with('success', 'Задача создана.');
    }

    public function updateTaskStatus(Request $request, Task $task): JsonResponse
    {
        $this->getClient($request);
        $validated = $request->validate([
            'status' => 'required|in:in_development,processing,execution,completed',
        ]);
        $task->update(['status' => $validated['status']]);
        TelegramService::notifyTaskUpdated($task);
        return response()->json(['ok' => true, 'status' => $task->status]);
    }

    public function projectsIndex(Request $request): View
    {
        $this->getClient($request);
        $projects = Project::withCount(['balanceTransactions'])->orderBy('name')->get();
        return view('cabinet.projects.index', compact('projects'));
    }

    public function projectShow(Request $request, Project $project): View
    {
        $client = $this->getClient($request);
        $project->load([
            'expenseItems',
            'apartments',
            'documentFields',
            'documents',
            'tasks' => function ($q) { return $q->with('client'); },
            'balanceTransactions' => function ($q) { return $q->with(['client', 'projectExpenseItem'])->latest(); },
            'constructionStages' => function ($q) { return $q->with(['client', 'works']); },
        ]);

        $summaryByItem = $project->expenseItems->map(function ($item) {
            $total = $item->balanceTransactions()->sum('amount');
            $count = $item->balanceTransactions()->count();
            return [
                'item' => $item,
                'total' => $total,
                'count' => $count,
            ];
        });
        $grandTotal = $project->balanceTransactions->sum('amount');

        $summaryByClient = $project->balanceTransactions->groupBy('client_id')->map(function ($transactions, $clientId) {
            $first = $transactions->first();
            return [
                'client' => $first->client,
                'total' => $transactions->sum('amount'),
                'count' => $transactions->count(),
            ];
        })->values();

        $myInvestments = ClientProjectInvestment::where('project_id', $project->id)
            ->where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->get();
        $myInvestmentsTotal = $myInvestments->sum('amount');
        $expenseItemSuggestions = $project->expenseItems->pluck('name')->merge(
            $myInvestments->pluck('expense_item_name')->unique()->filter()
        )->unique()->values()->all();

        $investmentsByClient = ClientProjectInvestment::where('project_id', $project->id)
            ->with('client')
            ->get()
            ->groupBy('client_id')
            ->map(function ($investments, $clientId) {
                $c = $investments->first()->client;
                $byArticle = $investments->groupBy('expense_item_name')->map(function ($items) { return $items->sum('amount'); });
                $total = $investments->sum('amount');
                return ['client' => $c, 'byArticle' => $byArticle, 'total' => $total];
            })->values();
        $investmentsGrandTotal = ClientProjectInvestment::where('project_id', $project->id)->sum('amount');

        $apartmentsByClient = $project->apartments
            ->whereNotNull('client_id')
            ->load('client')
            ->groupBy('client_id')
            ->map(function ($apts) {
                $client = $apts->first()->client;
                if (!$client) return null;
                $sold = $apts->where('status', 'sold');
                return [
                    'client' => $client,
                    'count' => $apts->count(),
                    'area' => $apts->sum(function ($a) { return (float) ($a->living_area ?? 0); }),
                    'soldSum' => $sold->sum(function ($a) { return (float) ($a->price ?? 0); }),
                ];
            })
            ->filter()
            ->values();

        $projectClientIds = collect()
            ->merge($summaryByClient->pluck('client')->pluck('id'))
            ->merge($investmentsByClient->pluck('client')->pluck('id'))
            ->merge($project->apartments->pluck('client_id')->filter())
            ->unique()
            ->filter();
        $loansByClient = collect();
        foreach ($projectClientIds as $cid) {
            $loans = BalanceTransaction::where('client_id', $cid)
                ->where('operation_type', BalanceTransaction::OPERATION_LOAN)
                ->sum('amount');
            $repayments = BalanceTransaction::where('client_id', $cid)
                ->where('operation_type', BalanceTransaction::OPERATION_LOAN_REPAYMENT)
                ->sum('amount');
            $outstanding = round($loans - $repayments, 2);
            $c = Client::find($cid);
            if ($c) {
                $loansByClient->push(['client' => $c, 'amount' => $outstanding]);
            }
        }

        return view('cabinet.projects.show', [
            'project' => $project,
            'summaryByItem' => $summaryByItem,
            'summaryByClient' => $summaryByClient,
            'grandTotal' => $grandTotal,
            'myInvestments' => $myInvestments,
            'myInvestmentsTotal' => $myInvestmentsTotal,
            'expenseItemSuggestions' => $expenseItemSuggestions,
            'investmentsByClient' => $investmentsByClient,
            'investmentsGrandTotal' => $investmentsGrandTotal,
            'apartmentsByClient' => $apartmentsByClient,
            'loansByClient' => $loansByClient,
        ]);
    }

    public function storeInvestment(Request $request, Project $project): RedirectResponse
    {
        $client = $this->getClient($request);
        $validated = $request->validate([
            'expense_item_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'comment' => 'nullable|string|max:5000',
        ]);
        ClientProjectInvestment::create([
            'project_id' => $project->id,
            'client_id' => $client->id,
            'expense_item_name' => $validated['expense_item_name'],
            'amount' => (float) $validated['amount'],
            'comment' => $validated['comment'] ?? null,
        ]);
        return redirect()->route('cabinet.projects.show', $project)->with('success', 'Расход добавлен.')->with('active_tab', 'investments');
    }

    public function destroyInvestment(Request $request, Project $project, ClientProjectInvestment $investment): RedirectResponse
    {
        $client = $this->getClient($request);
        if ($investment->project_id !== $project->id || $investment->client_id !== $client->id) {
            abort(404);
        }
        $investment->delete();
        return redirect()->route('cabinet.projects.show', $project)->with('success', 'Расход удалён.')->with('active_tab', 'investments');
    }

    public function createApartment(Request $request, Project $project): View
    {
        $this->getClient($request);
        $clients = Client::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        return view('cabinet.apartments.create', compact('project', 'clients'));
    }

    public function storeApartment(Request $request, Project $project): RedirectResponse
    {
        $this->getClient($request);
        $validated = $request->validate([
            'apartment_number' => 'required|string|max:50',
            'entrance' => 'nullable|string|max:20',
            'floor' => 'nullable|integer|min:0|max:999',
            'living_area' => 'nullable|numeric|min:0',
            'rooms_count' => 'nullable|integer|min:1|max:20',
            'status' => 'required|in:available,in_pledge,sold',
            'owner_data' => 'nullable|string|max:5000',
            'ddu_contract_number' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'client_id' => 'nullable|exists:clients,id',
        ]);
        $validated['project_id'] = $project->id;
        $validated['price'] = isset($validated['price']) && $validated['price'] !== '' ? (float) $validated['price'] : null;
        $validated['client_id'] = $validated['client_id'] ?? null;
        $apartment = Apartment::create($validated);
        return redirect()->route('cabinet.projects.apartments.show', [$project, $apartment])->with('success', 'Квартира добавлена.');
    }

    public function apartmentShow(Request $request, Project $project, Apartment $apartment): View
    {
        $this->getClient($request);
        if ($apartment->project_id !== $project->id) {
            abort(404);
        }
        $apartment->load('client');
        return view('cabinet.apartments.show', compact('project', 'apartment'));
    }

    public function editApartment(Request $request, Project $project, Apartment $apartment): View
    {
        $this->getClient($request);
        if ($apartment->project_id !== $project->id) {
            abort(404);
        }
        $clients = Client::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        return view('cabinet.apartments.edit', compact('project', 'apartment', 'clients'));
    }

    public function updateApartment(Request $request, Project $project, Apartment $apartment): RedirectResponse
    {
        $this->getClient($request);
        if ($apartment->project_id !== $project->id) {
            abort(404);
        }
        $validated = $request->validate([
            'apartment_number' => 'required|string|max:50',
            'entrance' => 'nullable|string|max:20',
            'floor' => 'nullable|integer|min:0|max:999',
            'living_area' => 'nullable|numeric|min:0',
            'rooms_count' => 'nullable|integer|min:1|max:20',
            'status' => 'required|in:available,in_pledge,sold',
            'owner_data' => 'nullable|string|max:5000',
            'ddu_contract_number' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'client_id' => 'nullable|exists:clients,id',
        ]);
        $apartment->fill($validated);
        $apartment->owner_data = $validated['owner_data'] ?? null;
        $apartment->price = isset($validated['price']) && $validated['price'] !== '' ? (float) $validated['price'] : null;
        $apartment->client_id = $validated['client_id'] ?? null;
        $apartment->save();
        return redirect()->route('cabinet.projects.apartments.show', [$project, $apartment])->with('success', 'Квартира сохранена.');
    }

    public function destroyApartment(Request $request, Project $project, Apartment $apartment): RedirectResponse
    {
        $this->getClient($request);
        if ($apartment->project_id !== $project->id) {
            abort(404);
        }
        if ($apartment->layout_photo_path) {
            Storage::disk('public')->delete($apartment->layout_photo_path);
        }
        $apartment->delete();
        return redirect()->route('cabinet.projects.show', $project)->with('success', 'Квартира удалена.');
    }

    public function uploadApartmentLayoutPhoto(Request $request, Project $project, Apartment $apartment): RedirectResponse
    {
        $this->getClient($request);
        if ($apartment->project_id !== $project->id) {
            abort(404);
        }
        $request->validate([
            'layout_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        if ($apartment->layout_photo_path) {
            Storage::disk('public')->delete($apartment->layout_photo_path);
        }
        $path = $request->file('layout_photo')->store('apartments', 'public');
        $apartment->update(['layout_photo_path' => $path]);
        return back()->with('success', 'Планировка загружена.');
    }

    public function storeDocumentField(Request $request, Project $project): RedirectResponse
    {
        $this->getClient($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'nullable|string|max:5000',
        ]);
        $validated['project_id'] = $project->id;
        $validated['sort_order'] = $project->documentFields()->max('sort_order') + 1;
        ProjectDocumentField::create($validated);
        return back()->with('success', 'Поле добавлено.');
    }

    public function destroyDocumentField(Request $request, Project $project, ProjectDocumentField $documentField): RedirectResponse
    {
        $this->getClient($request);
        if ($documentField->project_id !== $project->id) {
            abort(404);
        }
        $documentField->delete();
        return back()->with('success', 'Поле удалено.');
    }

    public function storeDocument(Request $request, Project $project): RedirectResponse
    {
        $this->getClient($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp,zip',
        ]);
        $doc = ProjectDocument::create([
            'project_id' => $project->id,
            'name' => $validated['name'],
        ]);
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('project-documents', 'public');
            $doc->update(['file_path' => $path]);
        }
        return back()->with('success', 'Документ добавлен.');
    }

    public function updateDocument(Request $request, Project $project, ProjectDocument $document): RedirectResponse
    {
        $this->getClient($request);
        if ($document->project_id !== $project->id) {
            abort(404);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp,zip',
        ]);
        $document->name = $validated['name'];
        if ($request->hasFile('file')) {
            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
            }
            $document->file_path = $request->file('file')->store('project-documents', 'public');
        }
        $document->save();
        return back()->with('success', 'Документ сохранён.');
    }

    public function destroyDocument(Request $request, Project $project, ProjectDocument $document): RedirectResponse
    {
        $this->getClient($request);
        if ($document->project_id !== $project->id) {
            abort(404);
        }
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();
        return back()->with('success', 'Документ удалён.');
    }

    public function videoConference(Request $request): View
    {
        $this->getClient($request);
        $room = $request->query('room');
        if ($room !== null && $room !== '') {
            $room = preg_replace('/[^a-zA-Z0-9\-_]/', '', $room);
            if (strlen($room) < 4) {
                $room = Str::random(16);
            }
            $inviteUrl = route('cabinet.video', ['room' => $room]);
        } else {
            $room = null;
            $inviteUrl = null;
        }
        $startRoom = Str::random(16);
        return view('cabinet.video', compact('room', 'inviteUrl', 'startRoom'));
    }

    public function pushSubscribe(Request $request): JsonResponse
    {
        $client = $this->getClient($request);
        $data = $request->validate([
            'endpoint' => 'required|string|max:500',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);
        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'client_id' => $client->id,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
            ]
        );
        return response()->json(['ok' => true]);
    }

    public function profile(Request $request): View
    {
        $client = $this->getClient($request);
        return view('cabinet.profile', compact('client'));
    }

    public function calendarSync(Request $request): View
    {
        $client = $this->getClient($request);
        if (!$client->calendar_feed_token) {
            $client->calendar_feed_token = Str::random(48);
            $client->save();
        }
        $feedUrl = url()->route('cabinet.calendar.feed.alt', ['token' => $client->calendar_feed_token]);
        return view('cabinet.calendar', compact('client', 'feedUrl'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $client = $this->getClient($request);
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.min' => 'Пароль должен быть не менее 6 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        if (!$client->verifyCabinetPassword($request->current_password)) {
            return redirect()->route('cabinet.profile')->withErrors(['current_password' => 'Неверный текущий пароль.']);
        }

        $client->update([
            'cabinet_password' => Hash::make($request->password),
        ]);

        return redirect()->route('cabinet.profile')->with('success', 'Пароль успешно изменён.');
    }

    public function stageDetail(Request $request, Project $project, ConstructionStage $stage): View
    {
        $client = $this->getClient($request);
        if ($stage->project_id !== $project->id) {
            abort(404);
        }
        $stage->load(['photos', 'comments.client', 'works']);
        return view('cabinet.projects.stages.modal-content', compact('project', 'stage', 'client'));
    }

    public function updateStageStatus(Request $request, Project $project, ConstructionStage $stage): JsonResponse
    {
        $this->getClient($request);
        if ($stage->project_id !== $project->id) {
            abort(404);
        }
        $validated = $request->validate([
            'status' => 'required|in:not_started,in_progress,completed',
        ]);
        $stage->update(['status' => $validated['status']]);
        TelegramService::notifyConstructionStageUpdated($stage);
        return response()->json(['ok' => true, 'status' => $stage->status, 'status_label' => $stage->status_label]);
    }

    public function uploadStagePhoto(Request $request, Project $project, ConstructionStage $stage): JsonResponse
    {
        $this->getClient($request);
        if ($stage->project_id !== $project->id) {
            abort(404);
        }
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);
        $path = $request->file('photo')->store('construction-stages/' . $stage->id, 'public');
        $sortOrder = $stage->photos()->max('sort_order') + 1;
        $photo = ConstructionStagePhoto::create([
            'construction_stage_id' => $stage->id,
            'path' => $path,
            'sort_order' => $sortOrder,
        ]);
        $url = Storage::disk('public')->url($path);
        return response()->json([
            'ok' => true,
            'id' => $photo->id,
            'url' => $url,
            'caption' => $photo->caption,
        ]);
    }

    public function storeStageComment(Request $request, Project $project, ConstructionStage $stage): JsonResponse
    {
        $client = $this->getClient($request);
        if ($stage->project_id !== $project->id) {
            abort(404);
        }
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);
        $comment = ConstructionStageComment::create([
            'construction_stage_id' => $stage->id,
            'client_id' => $client->id,
            'body' => $validated['body'],
        ]);
        $comment->load('client');
        return response()->json([
            'ok' => true,
            'id' => $comment->id,
            'body' => $comment->body,
            'created_at' => $comment->created_at->format('d.m.Y H:i'),
            'client_name' => $comment->client ? $comment->client->full_name : 'Гость',
        ]);
    }
}
