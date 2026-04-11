<?php

namespace App\Services;

/**
 * Логика ассистента по переписке в Telegram-группе (триггеры, будущие сводки).
 */
class TelegramGroupAssistantService
{
    /** Ответ на запрос «помоги получить текущую информацию» и похожие формулировки. */
    public const HELP_CURRENT_INFO_REPLY = 'К вашим услугам! Что нужно найти?';

    /**
     * Запрос актуальной/текущей информации (в т.ч. «помоги получить текущию информацию»).
     */
    public static function isHelpCurrentInfoRequest(?string $text): bool
    {
        if ($text === null || trim($text) === '') {
            return false;
        }
        $t = mb_strtolower($text);

        if (preg_match('/помоги\s+получить\s+текущ(ую|ию)\s+информац/u', $t)) {
            return true;
        }

        // «помоги получить информацию» без слова «текущая»
        if (preg_match('/помоги/u', $t) && preg_match('/получить/u', $t) && preg_match('/информац/u', $t)) {
            return true;
        }

        $hasCurrent = (bool) preg_match('/текущ|актуальн/u', $t);
        $hasInfo = (bool) preg_match('/информац|сведен/u', $t);
        if (!$hasCurrent || !$hasInfo) {
            return false;
        }

        return (bool) preg_match('/помоги|получить|нужна|нужно|дайте|дай\b/u', $t);
    }
}
