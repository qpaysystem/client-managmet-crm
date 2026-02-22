<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CalendarFeedController extends Controller
{
    /**
     * ICS calendar feed for client tasks (subscribe in iPhone Calendar).
     * URL: /cabinet/calendar/feed.ics?token=CLIENT_TOKEN
     */
    public function feed(Request $request): Response
    {
        $token = $request->query('token');
        if (!$token) {
            return response('Token required', 404)->header('Content-Type', 'text/plain');
        }

        $client = Client::where('calendar_feed_token', $token)->first();
        if (!$client) {
            return response('Invalid token', 404)->header('Content-Type', 'text/plain');
        }

        $tasks = Task::where('client_id', $client->id)
            ->where('show_on_board', true)
            ->orderBy('due_date')
            ->orderBy('created_at')
            ->get();

        $ics = $this->buildIcs($tasks, $client->full_name);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="tasks.ics"',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    private function buildIcs($tasks, string $calendarName): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//' . config('app.name') . '//Tasks//RU',
            'CALSCALE:GREGORIAN',
            'X-WR-CALNAME:Задачи - ' . $this->escapeIcs($calendarName),
        ];

        foreach ($tasks as $task) {
            $date = $task->due_date ? $task->due_date->format('Ymd') : $task->created_at->format('Ymd');
            $uid = 'task-' . $task->id . '@' . parse_url(config('app.url'), PHP_URL_HOST ?? 'localhost');
            $summary = $this->escapeIcs(Str::limit($task->title, 200));
            $desc = $this->escapeIcs(($task->description ?: '') . "\nСтатус: " . ($task->status_label ?? ''));
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . $uid;
            $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z', $task->updated_at->timestamp);
            $lines[] = 'DTSTART;VALUE=DATE:' . $date;
            $lines[] = 'DTEND;VALUE=DATE:' . $date;
            $lines[] = 'SUMMARY:' . $summary;
            if ($desc) {
                $lines[] = 'DESCRIPTION:' . str_replace("\n", "\\n", $desc);
            }
            $lines[] = 'STATUS:' . ($task->status === Task::STATUS_COMPLETED ? 'CONFIRMED' : 'TENTATIVE');
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }

    private function escapeIcs(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $s);
    }
}
