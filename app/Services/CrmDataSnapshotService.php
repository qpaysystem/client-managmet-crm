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
