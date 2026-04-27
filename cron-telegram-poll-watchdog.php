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

$lockPath = $projectDir . '/storage/telegram-poll-watchdog.lock';
$lockDir = dirname($lockPath);
if (!is_dir($lockDir)) {
    @mkdir($lockDir, 0775, true);
}
$lockHandle = @fopen($lockPath, 'c');
if ($lockHandle === false) {
    exit(0);
}
// Avoid concurrent cron runs spawning duplicates
if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    exit(0);
}

// IMPORTANT:
// `pgrep -f 'artisan telegram:poll'` matches the `pgrep` process itself (because the pattern is present
// in pgrep command line), which makes the watchdog think there are duplicates and kill the real poller.
//
// Use a more specific pattern and the `[p]hp` trick so the pattern does NOT match the pgrep process.
$pattern = '[p]hp.*artisan telegram:poll';
$cmdPgrep = 'pgrep -f ' . escapeshellarg($pattern);

$existing = trim((string) shell_exec($cmdPgrep));
$pids = $existing !== '' ? preg_split('/\s+/', $existing) : [];
$pids = array_values(array_filter(array_map('intval', $pids ?: [])));

// If multiple pollers exist -> kill extras to avoid Telegram getUpdates 409 conflict.
if (count($pids) > 1) {
    // Kill all and restart a single clean instance (more reliable than guessing "main" PID)
    foreach ($pids as $pid) {
        @shell_exec('kill ' . (int) $pid . ' 2>/dev/null');
    }
    // give OS a moment to reap processes
    usleep(200000);
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

