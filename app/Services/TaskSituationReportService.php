<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Collection;

class TaskSituationReportService
{
    /**
     * Текстовая сводка по незавершённым задачам (текущая ситуация).
     */
    public static function build(): string
    {
        $tasks = Task::query()
            ->with(['project', 'client', 'responsibleUser'])
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->orderBy('project_id')
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        $lines = [];
        $lines[] = '📋 Сводка по текущим задачам CRM';
        $lines[] = 'Сформировано: ' . now()->format('d.m.Y H:i');
        $lines[] = '';

        if ($tasks->isEmpty()) {
            $lines[] = 'Незавершённых задач нет.';
            return implode("\n", $lines);
        }

        $byStatus = $tasks->groupBy('status');
        $lines[] = 'Всего незавершённых: ' . $tasks->count();
        foreach (Task::statusLabels() as $st => $label) {
            if ($st === Task::STATUS_COMPLETED) {
                continue;
            }
            $n = $byStatus->get($st)?->count() ?? 0;
            $lines[] = '— ' . $label . ': ' . $n;
        }

        $today = now()->startOfDay();
        $overdueIds = $tasks->filter(static function (Task $t) use ($today) {
            return $t->due_date && $t->due_date->lt($today);
        })->pluck('id')->all();

        if ($overdueIds !== []) {
            $lines[] = '';
            $lines[] = '⚠️ Просрочено по сроку: ' . count($overdueIds);
        }

        /** @var Collection<string, Collection<int, Task>> $byProject */
        $byProject = $tasks->groupBy(fn (Task $t) => $t->project_id ? (string) $t->project_id : '_none');

        $lines[] = '';
        $lines[] = 'По проектам';
        foreach ($byProject->sortKeys() as $pid => $group) {
            $projectName = $pid === '_none'
                ? 'Без проекта'
                : ($group->first()->project?->name ?? 'Проект #' . $pid);
            $lines[] = '';
            $lines[] = $projectName . ' (' . $group->count() . ')';
            $sorted = $group->sortBy(function (Task $t) use ($overdueIds) {
                $isOverdue = in_array($t->id, $overdueIds, true);
                return [$isOverdue ? 0 : 1, $t->due_date?->timestamp ?? PHP_INT_MAX, $t->id];
            });
            foreach ($sorted as $task) {
                $lines[] = self::formatTaskLine($task, in_array($task->id, $overdueIds, true));
            }
        }

        return implode("\n", $lines);
    }

    private static function formatTaskLine(Task $task, bool $isOverdue): string
    {
        $mark = $isOverdue ? '⚠️ ' : '• ';
        $parts = [];
        $parts[] = $mark . $task->title;
        $parts[] = '[' . $task->status_label . ']';
        if ($task->responsibleUser) {
            $parts[] = 'Отв.: ' . $task->responsibleUser->name;
        } else {
            $parts[] = 'Отв.: —';
        }
        if ($task->client) {
            $parts[] = 'Клиент: ' . $task->client->full_name;
        }
        if ($task->due_date) {
            $parts[] = 'Срок: ' . $task->due_date->format('d.m.Y');
        }
        if ($task->budget !== null && $task->budget !== '') {
            $parts[] = 'Бюджет: ' . $task->budget;
        }
        return implode(' · ', $parts);
    }
}
