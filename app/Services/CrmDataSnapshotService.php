<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\BalanceTransaction;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Str;

/**
 * Сжатая выборка из БД для ИИ (только заранее описанные запросы, без произвольного SQL).
 */
class CrmDataSnapshotService
{
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

        $soldListLimit = min(400, max(50, (int) config('services.crm_snapshot_sold_apartments_limit', 300)));
        $soldList = $soldApartments->clone()
            ->orderByDesc('id')
            ->limit($soldListLimit)
            ->get()
            ->map(function (Apartment $a) {
                return [
                    'id' => $a->id,
                    'project' => $a->project?->name,
                    'apartment_number' => $a->apartment_number,
                    'entrance' => $a->entrance,
                    'floor' => $a->floor,
                    'client_name' => $a->client ? trim(($a->client->first_name ?? '').' '.($a->client->last_name ?? '')) : null,
                    'client_id' => $a->client_id,
                    'living_area_m2' => $a->living_area !== null ? (string) $a->living_area : null,
                    'price' => $a->price !== null ? (string) $a->price : null,
                ];
            })
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

        $recentTx = BalanceTransaction::query()
            ->with(['client:id,first_name,last_name'])
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(function (BalanceTransaction $t) {
                return [
                    'id' => $t->id,
                    'date' => $t->created_at?->format('Y-m-d H:i'),
                    'client' => $t->client?->full_name,
                    'type' => $t->type,
                    'amount' => (string) $t->amount,
                    'balance_after' => (string) $t->balance_after,
                    'operation' => $t->operation_type_label,
                    'comment' => $t->comment ? Str::limit($t->comment, 100) : null,
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

        return [
            'generated_at' => now()->toIso8601String(),
            'clients' => [
                'total' => Client::query()->count(),
                'active' => Client::query()->where('status', 'active')->count(),
            ],
            'projects' => [
                'total' => Project::query()->count(),
            ],
            'apartments' => [
                'total' => Apartment::query()->count(),
                'by_status_counts' => $apartmentsHuman,
                'free_total' => (int) (Apartment::query()->where('status', Apartment::STATUS_AVAILABLE)->count()),
                'free_by_project_top' => $freeByProject,
                'sold' => [
                    'count' => $soldCount,
                    'living_area_total_m2' => round($soldLivingAreaSum, 2),
                    'unique_buyers_count' => count($soldBuyersFio),
                    'buyers_fio' => $soldBuyersFio,
                    'sold_apartments_sample' => $soldList,
                    'note' => 'buyers_fio — покупатели проданных квартир (по привязке client_id). sold_apartments_sample — до '.$soldListLimit.' последних проданных; для полного списка ориентируйся на агрегаты.',
                ],
            ],
            'balance_transactions' => [
                'recent' => $recentTx,
            ],
            'tasks' => [
                'non_completed_by_status' => $taskLabels,
                'non_completed_total' => Task::query()->where('status', '!=', Task::STATUS_COMPLETED)->count(),
            ],
        ];
    }
}
