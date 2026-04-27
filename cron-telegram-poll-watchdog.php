<?php

/**
 * Timeweb cron watchdog for Telegram long poll.
 *
 * Use in hosting panel cron: run every minute and specify this file path.
 *
 * Goal:
 * - Ensure exactly one `artisan telegram:poll` process is running
 * - Restart it if it died (reboot, SSH disconnect, crash)
 *
 * Notes:
 * - `telegram:poll` must NOT run together with Telegram webhook (we always pass --delete-webhook)
 * - This script does not require root
 */

$projectDir = __DIR__;
$php = '/opt/php82/bin/php';
$artisan = $projectDir . '/artisan';
$log = $projectDir . '/storage/logs/telegram-poll.log';

if (!is_file($artisan)) {
    // project path mismatch
    exit(0);
}

$pattern = 'artisan telegram:poll';
$cmdPgrep = 'pgrep -f ' . escapeshellarg($pattern);

$existing = trim((string) shell_exec($cmdPgrep));
$pids = $existing !== '' ? preg_split('/\s+/', $existing) : [];
$pids = array_values(array_filter(array_map('intval', $pids ?: [])));

// If multiple pollers exist -> kill extras to avoid Telegram getUpdates 409 conflict.
if (count($pids) > 1) {
    foreach (array_slice($pids, 1) as $pid) {
        @shell_exec('kill ' . (int) $pid . ' 2>/dev/null');
    }
}

// Re-check if any poller remains.
$existing = trim((string) shell_exec($cmdPgrep));
if ($existing !== '') {
    exit(0);
}

$cmd = 'cd ' . escapeshellarg($projectDir)
    . ' && ' . escapeshellarg($php) . ' ' . escapeshellarg($artisan)
    . ' telegram:poll --delete-webhook --timeout=50'
    . ' >> ' . escapeshellarg($log) . ' 2>&1 &';

@shell_exec($cmd);

