<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Client;
use App\Models\ClientProjectInvestment;
use App\Models\ConstructionStage;
use App\Models\ConstructionStageWork;
use App\Models\Project;
use App\Models\ProjectExpenseItem;
use App\Models\ProjectPhoto;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::withCount('expenseItems')->latest()->paginate(20);
        return view('admin.projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('admin.projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ]);
        Project::create($validated);
        return redirect()->route('admin.projects.index')->with('success', 'Проект создан.');
    }

    public function show(Project $project): View
    {
        $project->load(['expenseItems', 'apartments', 'sitePhotos', 'constructionStages' => function ($q) { return $q->with(['client', 'works']); }]);
        $project->load(['balanceTransactions' => function ($q) { return $q->with(['client', 'projectExpenseItem'])->latest(); }]);

        $summaryByItem = $project->expenseItems->map(function ($item) {
            $total = $item->balanceTransactions()->sum('amount');
            $count = $item->balanceTransactions()->count();
            return [
                'item' => $item,
                'total' => $total,
                'count' => $count,
            ];
        });
        $grandTotal = $project->balanceTransactions()->sum('amount');

        $investmentsByClient = ClientProjectInvestment::where('project_id', $project->id)
            ->with('client')
            ->get()
            ->groupBy('client_id')
            ->map(function ($investments, $clientId) {
                $client = $investments->first()->client;
                $byArticle = $investments->groupBy('expense_item_name')->map(function ($items) { return $items->sum('amount'); });
                $total = $investments->sum('amount');
                return [
                    'client' => $client,
                    'byArticle' => $byArticle,
                    'total' => $total,
                ];
            })->values();
        $investmentsGrandTotal = ClientProjectInvestment::where('project_id', $project->id)->sum('amount');

        return view('admin.projects.show', [
            'project' => $project,
            'summaryByItem' => $summaryByItem,
            'grandTotal' => $grandTotal,
            'investmentsByClient' => $investmentsByClient,
            'investmentsGrandTotal' => $investmentsGrandTotal,
        ]);
    }

    public function edit(Project $project): View
    {
        return view('admin.projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ]);
        $project->update($validated);
        return redirect()->route('admin.projects.show', $project)->with('success', 'Проект сохранён.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();
        return redirect()->route('admin.projects.index')->with('success', 'Проект удалён.');
    }

    public function storeExpenseItem(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $validated['project_id'] = $project->id;
        $validated['sort_order'] = $project->expenseItems()->max('sort_order') + 1;
        ProjectExpenseItem::create($validated);
        return back()->with('success', 'Статья расхода добавлена.');
    }

    public function destroyExpenseItem(Project $project, ProjectExpenseItem $expenseItem): RedirectResponse
    {
        if ($expenseItem->project_id !== $project->id) {
            abort(404);
        }
        $expenseItem->delete();
        return back()->with('success', 'Статья расхода удалена.');
    }

    public function storeConstructionStage(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'budget' => 'nullable|numeric|min:0',
            'contractor' => 'nullable|string|max:255',
            'status' => 'nullable|in:not_started,in_progress,completed',
            'planned_start_date' => 'nullable|date',
            'planned_end_date' => 'nullable|date',
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date|after_or_equal:actual_start_date',
        ]);
        $validated['project_id'] = $project->id;
        $validated['sort_order'] = $project->constructionStages()->max('sort_order') + 1;
        $validated['budget'] = isset($validated['budget']) && $validated['budget'] !== '' ? (float) $validated['budget'] : null;
        $validated['client_id'] = $validated['client_id'] ?? null;
        $validated['status'] = $validated['status'] ?? ConstructionStage::STATUS_NOT_STARTED;
        foreach (['planned_start_date', 'planned_end_date', 'actual_start_date', 'actual_end_date'] as $d) {
            $validated[$d] = !empty($validated[$d]) ? $validated[$d] : null;
        }
        $stage = ConstructionStage::create($validated);
        TelegramService::notifyConstructionStageCreated($stage);
        return back()->with('success', 'Этап строительства добавлен.');
    }

    public function editConstructionStage(Project $project, ConstructionStage $constructionStage): View
    {
        if ($constructionStage->project_id !== $project->id) {
            abort(404);
        }
        $constructionStage->load('works');
        $clients = Client::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        return view('admin.projects.construction-stages.edit', compact('project', 'constructionStage', 'clients'));
    }

    public function storeConstructionStageWork(Request $request, Project $project, ConstructionStage $constructionStage): RedirectResponse
    {
        if ($constructionStage->project_id !== $project->id) {
            abort(404);
        }
        $validated = $request->validate([
            'work_start_date' => 'nullable|date',
            'materials_name' => 'nullable|string|max:500',
            'materials_cost' => 'nullable|numeric|min:0',
            'works_name' => 'nullable|string|max:500',
            'works_cost' => 'nullable|numeric|min:0',
            'contractor' => 'nullable|string|max:255',
        ]);
        $validated['construction_stage_id'] = $constructionStage->id;
        $validated['sort_order'] = $constructionStage->works()->max('sort_order') + 1;
        foreach (['materials_cost', 'works_cost'] as $k) {
            $validated[$k] = isset($validated[$k]) && $validated[$k] !== '' ? (float) $validated[$k] : null;
        }
        $validated['work_start_date'] = !empty($validated['work_start_date']) ? $validated['work_start_date'] : null;
        ConstructionStageWork::create($validated);
        return redirect()->route('admin.projects.construction-stages.edit', [$project, $constructionStage])
            ->with('success_work', 'Вид работ добавлен.');
    }

    public function destroyConstructionStageWork(Project $project, ConstructionStage $constructionStage, ConstructionStageWork $construction_stage_work): RedirectResponse
    {
        if ($constructionStage->project_id !== $project->id || $construction_stage_work->construction_stage_id !== $constructionStage->id) {
            abort(404);
        }
        $construction_stage_work->delete();
        return redirect()->route('admin.projects.construction-stages.edit', [$project, $constructionStage])
            ->with('success_work', 'Вид работ удалён.');
    }

    public function updateConstructionStage(Request $request, Project $project, ConstructionStage $constructionStage): RedirectResponse
    {
        if ($constructionStage->project_id !== $project->id) {
            abort(404);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'budget' => 'nullable|numeric|min:0',
            'contractor' => 'nullable|string|max:255',
            'status' => 'required|in:not_started,in_progress,completed',
            'planned_start_date' => 'nullable|date',
            'planned_end_date' => 'nullable|date',
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date',
        ]);
        $validated['budget'] = isset($validated['budget']) && $validated['budget'] !== '' ? (float) $validated['budget'] : null;
        $validated['client_id'] = $validated['client_id'] ?? null;
        foreach (['planned_start_date', 'planned_end_date', 'actual_start_date', 'actual_end_date'] as $d) {
            $validated[$d] = !empty($validated[$d]) ? $validated[$d] : null;
        }
        $constructionStage->update($validated);
        TelegramService::notifyConstructionStageUpdated($constructionStage);
        return redirect()->route('admin.projects.show', $project)->with('success', 'Этап строительства обновлён.');
    }

    public function destroyConstructionStage(Project $project, ConstructionStage $constructionStage): RedirectResponse
    {
        if ($constructionStage->project_id !== $project->id) {
            abort(404);
        }
        $stageName = $constructionStage->name;
        $projectName = $constructionStage->project?->name;
        $constructionStage->delete();
        TelegramService::notifyConstructionStageDeleted($stageName, $projectName);
        return back()->with('success', 'Этап строительства удалён.');
    }

    public function createApartment(Project $project): View
    {
        $clients = Client::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        return view('admin.projects.apartments.create', compact('project', 'clients'));
    }

    public function storeApartment(Request $request, Project $project): RedirectResponse
    {
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
        return redirect()->route('admin.projects.apartments.show', [$project, $apartment])->with('success', 'Квартира добавлена.');
    }

    public function apartmentShow(Project $project, Apartment $apartment): View
    {
        if ($apartment->project_id !== $project->id) {
            abort(404);
        }
        $apartment->load('client');
        return view('admin.projects.apartments.show', compact('project', 'apartment'));
    }

    public function editApartment(Project $project, Apartment $apartment): View
    {
        if ($apartment->project_id !== $project->id) {
            abort(404);
        }
        $clients = Client::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        return view('admin.projects.apartments.edit', compact('project', 'apartment', 'clients'));
    }

    public function updateApartment(Request $request, Project $project, Apartment $apartment): RedirectResponse
    {
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
        return redirect()->route('admin.projects.apartments.show', [$project, $apartment])->with('success', 'Квартира сохранена.');
    }

    public function destroyApartment(Project $project, Apartment $apartment): RedirectResponse
    {
        if ($apartment->project_id !== $project->id) {
            abort(404);
        }
        if ($apartment->layout_photo_path) {
            Storage::disk('public')->delete($apartment->layout_photo_path);
        }
        $apartment->delete();
        return redirect()->route('admin.projects.show', $project)->with('success', 'Квартира удалена.');
    }

    public function uploadApartmentLayoutPhoto(Request $request, Project $project, Apartment $apartment): RedirectResponse
    {
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

    public function updateSiteSettings(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'show_on_site' => 'nullable|boolean',
            'site_description' => 'nullable|string|max:10000',
            'map_embed_url' => 'nullable|string|max:1000',
        ]);
        $project->show_on_site = $request->boolean('show_on_site');
        $project->site_description = $validated['site_description'] ?? null;
        $project->map_embed_url = $validated['map_embed_url'] ?? null;
        $project->save();
        return back()->with('success', 'Настройки размещения сохранены.');
    }

    public function uploadSitePhoto(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        $sortOrder = $project->sitePhotos()->max('sort_order') + 1;
        $path = $request->file('photo')->store('project-photos', 'public');
        ProjectPhoto::create([
            'project_id' => $project->id,
            'path' => $path,
            'sort_order' => $sortOrder,
        ]);
        return back()->with('success', 'Фото добавлено.');
    }

    public function destroySitePhoto(Project $project, ProjectPhoto $photo): RedirectResponse
    {
        if ($photo->project_id !== $project->id) {
            abort(404);
        }
        Storage::disk('public')->delete($photo->path);
        $photo->delete();
        return back()->with('success', 'Фото удалено.');
    }
}
