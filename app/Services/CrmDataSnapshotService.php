<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\BalanceTransaction;
use App\Models\Client;
use App\Models\ClientProjectInvestment;
use App\Models\ConstructionStage;
use App\Models\CustomField;
use App\Models\Product;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Str;

/**
 * Сжатая выборка из БД для ИИ (только заранее описанные запросы, без произвольного SQL).
 * Полный дамп БД не отдаётся — ограничения в config/services.php (crm_snapshot_*).
 */
class CrmDataSnapshotService
{
    private const SNAPSHOT_VERSION = 2;

    private static function clampInt(int $v, int $min, int $max): int
    {
        return max($min, min($max, $v));
    }

    /**
     * @return array<string, mixed>
     */
    private static function mapApartmentRow(Apartment $a): array
    {
        return [
            'id' => $a->id,
            'project_id' => $a->project_id,
            'project' => $a->project?->name,
            'apartment_number' => $a->apartment_number,
            'entrance' => $a->entrance,
            'floor' => $a->floor,
            'rooms_count' => $a->rooms_count,
            'living_area_m2' => $a->living_area !== null ? (string) $a->living_area : null,
            'price' => $a->price !== null ? (string) $a->price : null,
            'status' => $a->status,
            'status_label' => Apartment::statusLabels()[$a->status] ?? $a->status,
            'client_id' => $a->client_id,
            'client_name' => $a->client ? trim(($a->client->first_name ?? '').' '.($a->client->last_name ?? '')) : null,
            'ddu_contract_number' => $a->ddu_contract_number,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function build(): array
    {
        $apartmentByStatus = Apartment::query()
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->map(fn ($n) => (int) $n)
            ->all();

        $labels = Apartment::statusLabels();
        $apartmentsHuman = [];
        foreach ($apartmentByStatus as $st => $cnt) {
            $apartmentsHuman[$labels[$st] ?? $st] = $cnt;
        }

        $freeByProject = Project::query()
            ->withCount([
                'apartments as free_apartments_count' => function ($q) {
                    $q->where('status', Apartment::STATUS_AVAILABLE);
                },
            ])
            ->orderByDesc('free_apartments_count')
            ->limit(15)
            ->get(['id', 'name'])
            ->map(fn (Project $p) => [
                'project' => $p->name,
                'free_apartments' => (int) $p->free_apartments_count,
            ])
            ->all();

        $soldApartments = Apartment::query()
            ->where('status', Apartment::STATUS_SOLD)
            ->with(['project:id,name', 'client:id,first_name,last_name']);

        $soldLivingAreaSum = (float) Apartment::query()
            ->where('status', Apartment::STATUS_SOLD)
            ->sum('living_area');

        $soldCount = (int) Apartment::query()->where('status', Apartment::STATUS_SOLD)->count();

        $soldListLimit = self::clampInt((int) config('services.crm_snapshot_sold_apartments_limit', 300), 50, 400);
        $soldList = $soldApartments->clone()
            ->orderByDesc('id')
            ->limit($soldListLimit)
            ->get()
            ->map(fn (Apartment $a) => self::mapApartmentRow($a))
            ->all();

        $buyerClientIds = Apartment::query()
            ->where('status', Apartment::STATUS_SOLD)
            ->whereNotNull('client_id')
            ->distinct()
            ->pluck('client_id');

        $soldBuyersFio = Client::query()
            ->whereIn('id', $buyerClientIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Client $c) => [
                'id' => $c->id,
                'full_name' => $c->full_name,
            ])
            ->values()
            ->all();

        $txLimit = self::clampInt((int) config('services.crm_snapshot_transactions_limit', 60), 10, 150);
        $recentTx = BalanceTransaction::query()
            ->with(['client:id,first_name,last_name', 'project:id,name', 'product:id,name'])
            ->orderByDesc('id')
            ->limit($txLimit)
            ->get()
            ->map(function (BalanceTransaction $t) {
                return [
                    'id' => $t->id,
                    'date' => $t->created_at?->format('Y-m-d H:i'),
                    'client' => $t->client?->full_name,
                    'client_id' => $t->client_id,
                    'project' => $t->project?->name,
                    'project_id' => $t->project_id,
                    'product' => $t->product?->name,
                    'type' => $t->type,
                    'amount' => (string) $t->amount,
                    'balance_after' => (string) $t->balance_after,
                    'operation' => $t->operation_type_label,
                    'comment' => $t->comment ? Str::limit($t->comment, 120) : null,
                ];
            })
            ->all();

        $taskByStatus = Task::query()
            ->selectRaw('status, COUNT(*) as cnt')
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->map(fn ($n) => (int) $n)
            ->all();

        $taskLabels = [];
        foreach ($taskByStatus as $st => $cnt) {
            $taskLabels[Task::statusLabels()[$st] ?? $st] = $cnt;
        }

        $clientsLimit = self::clampInt((int) config('services.crm_snapshot_clients_limit', 250), 20, 600);
        $clientsSample = Client::query()
            ->orderByDesc('id')
            ->limit($clientsLimit)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone', 'status', 'balance', 'registered_at'])
            ->map(fn (Client $c) => [
                'id' => $c->id,
                'full_name' => $c->full_name,
                'email' => $c->email,
                'phone' => $c->phone,
                'status' => $c->status,
                'balance' => (string) $c->balance,
                'registered_at' => $c->registered_at?->format('Y-m-d'),
            ])
            ->all();

        $availLimit = self::clampInt((int) config('services.crm_snapshot_available_apartments_limit', 200), 0, 500);
        $pledgeLimit = self::clampInt((int) config('services.crm_snapshot_in_pledge_apartments_limit', 200), 0, 500);

        $availableSample = $availLimit > 0
            ? Apartment::query()
                ->where('status', Apartment::STATUS_AVAILABLE)
                ->with(['project:id,name', 'client:id,first_name,last_name'])
                ->orderByDesc('id')
                ->limit($availLimit)
                ->get()
                ->map(fn (Apartment $a) => self::mapApartmentRow($a))
                ->all()
            : [];

        $inPledgeSample = $pledgeLimit > 0
            ? Apartment::query()
                ->where('status', Apartment::STATUS_IN_PLEDGE)
                ->with(['project:id,name', 'client:id,first_name,last_name'])
                ->orderByDesc('id')
                ->limit($pledgeLimit)
                ->get()
                ->map(fn (Apartment $a) => self::mapApartmentRow($a))
                ->all()
            : [];

        $projectsList = Project::query()
            ->withCount([
                'apartments',
                'apartments as apartments_available_count' => fn ($q) => $q->where('status', Apartment::STATUS_AVAILABLE),
                'apartments as apartments_sold_count' => fn ($q) => $q->where('status', Apartment::STATUS_SOLD),
                'apartments as apartments_in_pledge_count' => fn ($q) => $q->where('status', Apartment::STATUS_IN_PLEDGE),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'show_on_site'])
            ->map(fn (Project $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'show_on_site' => (bool) $p->show_on_site,
                'apartments_total' => (int) $p->apartments_count,
                'apartments_available' => (int) $p->apartments_available_count,
                'apartments_sold' => (int) $p->apartments_sold_count,
                'apartments_in_pledge' => (int) $p->apartments_in_pledge_count,
            ])
            ->all();

        $tasksOpenLimit = self::clampInt((int) config('services.crm_snapshot_tasks_open_limit', 150), 10, 400);
        $tasksCompletedLimit = self::clampInt((int) config('services.crm_snapshot_tasks_completed_limit', 50), 0, 200);

        $mapTask = function (Task $t): array {
            return [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description ? Str::limit($t->description, 400) : null,
                'status' => $t->status,
                'status_label' => $t->status_label,
                'due_date' => $t->due_date?->format('Y-m-d'),
                'budget' => $t->budget !== null ? (string) $t->budget : null,
                'project_id' => $t->project_id,
                'project' => $t->project?->name,
                'client_id' => $t->client_id,
                'client' => $t->client?->full_name,
                'responsible_user' => $t->responsibleUser?->name,
            ];
        };

        $tasksOpen = Task::query()
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->with(['project:id,name', 'client:id,first_name,last_name', 'responsibleUser:id,name'])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->limit($tasksOpenLimit)
            ->get()
            ->map($mapTask)
            ->all();

        $tasksCompletedRecent = $tasksCompletedLimit > 0
            ? Task::query()
                ->where('status', Task::STATUS_COMPLETED)
                ->with(['project:id,name', 'client:id,first_name,last_name', 'responsibleUser:id,name'])
                ->orderByDesc('id')
                ->limit($tasksCompletedLimit)
                ->get()
                ->map($mapTask)
                ->all()
            : [];

        $stagesLimit = self::clampInt((int) config('services.crm_snapshot_construction_stages_limit', 80), 0, 300);
        $constructionStagesSample = $stagesLimit > 0
            ? ConstructionStage::query()
                ->with(['project:id,name', 'client:id,first_name,last_name'])
                ->orderByDesc('id')
                ->limit($stagesLimit)
                ->get()
                ->map(function (ConstructionStage $s) {
                    return [
                        'id' => $s->id,
                        'name' => $s->name,
                        'status' => $s->status,
                        'status_label' => $s->status_label,
                        'project_id' => $s->project_id,
                        'project' => $s->project?->name,
                        'client_id' => $s->client_id,
                        'client' => $s->client?->full_name,
                        'budget' => $s->budget !== null ? (string) $s->budget : null,
                        'contractor' => $s->contractor,
                        'planned_start_date' => $s->planned_start_date?->format('Y-m-d'),
                        'planned_end_date' => $s->planned_end_date?->format('Y-m-d'),
                        'actual_start_date' => $s->actual_start_date?->format('Y-m-d'),
                        'actual_end_date' => $s->actual_end_date?->format('Y-m-d'),
                    ];
                })
                ->all()
            : [];

        $stageByStatus = ConstructionStage::query()
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->map(fn ($n) => (int) $n)
            ->all();

        $constructionStageLabels = [];
        foreach ($stageByStatus as $st => $cnt) {
            $constructionStageLabels[ConstructionStage::statusLabels()[$st] ?? $st] = $cnt;
        }

        $invLimit = self::clampInt((int) config('services.crm_snapshot_investments_limit', 100), 0, 300);
        $investmentsSample = $invLimit > 0
            ? ClientProjectInvestment::query()
                ->with(['project:id,name', 'client:id,first_name,last_name'])
                ->orderByDesc('id')
                ->limit($invLimit)
                ->get()
                ->map(function (ClientProjectInvestment $i) {
                    return [
                        'id' => $i->id,
                        'project_id' => $i->project_id,
                        'project' => $i->project?->name,
                        'client_id' => $i->client_id,
                        'client' => $i->client?->full_name,
                        'expense_item_name' => $i->expense_item_name,
                        'amount' => (string) $i->amount,
                        'comment' => $i->comment ? Str::limit($i->comment, 200) : null,
                    ];
                })
                ->all()
            : [];

        $customFieldsCatalog = CustomField::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'label', 'type'])
            ->map(fn (CustomField $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'label' => $f->label,
                'type' => $f->type,
            ])
            ->all();

        $productsCatalog = Product::query()
            ->orderBy('id')
            ->limit(200)
            ->get(['id', 'name', 'kind', 'type', 'estimated_cost'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'kind' => $p->kind,
                'type' => $p->type,
                'estimated_cost' => $p->estimated_cost !== null ? (string) $p->estimated_cost : null,
            ])
            ->all();

        return [
            'snapshot_version' => self::SNAPSHOT_VERSION,
            'generated_at' => now()->toIso8601String(),
            'meta' => [
                'description' => 'Снимок CRM для ИИ: агрегаты и ограниченные выборки; не весь объём БД. Лимиты задаются env CRM_SNAPSHOT_* и config services.crm_snapshot_*.',
            ],
            'clients' => [
                'total' => Client::query()->count(),
                'active' => Client::query()->where('status', 'active')->count(),
                'sample' => $clientsSample,
                'sample_limit' => $clientsLimit,
            ],
            'projects' => [
                'total' => Project::query()->count(),
                'list' => $projectsList,
            ],
            'apartments' => [
                'total' => Apartment::query()->count(),
                'by_status_counts' => $apartmentsHuman,
                'free_total' => (int) (Apartment::query()->where('status', Apartment::STATUS_AVAILABLE)->count()),
                'free_by_project_top' => $freeByProject,
                'available_sample' => $availableSample,
                'available_sample_limit' => $availLimit,
                'in_pledge_sample' => $inPledgeSample,
                'in_pledge_sample_limit' => $pledgeLimit,
                'sold' => [
                    'count' => $soldCount,
                    'living_area_total_m2' => round($soldLivingAreaSum, 2),
                    'unique_buyers_count' => count($soldBuyersFio),
                    'buyers_fio' => $soldBuyersFio,
                    'sold_apartments_sample' => $soldList,
                    'sold_apartments_sample_limit' => $soldListLimit,
                    'note' => 'buyers_fio — покупатели проданных квартир (по привязке client_id). sold_apartments_sample — до '.$soldListLimit.' последних проданных; полный перечень в выборке не гарантируется.',
                ],
            ],
            'balance_transactions' => [
                'recent' => $recentTx,
                'recent_limit' => $txLimit,
            ],
            'tasks' => [
                'non_completed_by_status' => $taskLabels,
                'non_completed_total' => Task::query()->where('status', '!=', Task::STATUS_COMPLETED)->count(),
                'open_tasks' => $tasksOpen,
                'open_tasks_limit' => $tasksOpenLimit,
                'completed_recent' => $tasksCompletedRecent,
                'completed_recent_limit' => $tasksCompletedLimit,
            ],
            'construction_stages' => [
                'total' => ConstructionStage::query()->count(),
                'by_status_counts' => $constructionStageLabels,
                'sample' => $constructionStagesSample,
                'sample_limit' => $stagesLimit,
            ],
            'client_project_investments' => [
                'total' => ClientProjectInvestment::query()->count(),
                'sample' => $investmentsSample,
                'sample_limit' => $invLimit,
            ],
            'catalogs' => [
                'custom_fields' => $customFieldsCatalog,
                'products' => $productsCatalog,
            ],
        ];
    }
}
