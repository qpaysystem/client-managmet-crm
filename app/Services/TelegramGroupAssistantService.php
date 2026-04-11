<?php

namespace App\Services;

/**
 * Логика ассистента по переписке в Telegram-группе (триггеры, будущие сводки).
 */
class TelegramGroupAssistantService
{
    /** Ответ на запрос «помоги получить текущую информацию» и похожие формулировки. */
    public const HELP_CURRENT_INFO_REPLY = 'К вашим услугам! Что нужно найти?';

    /** Ответ агента, если вопрос не про CRM / данные из снимка. */
    public const OFF_TOPIC_REPLY = 'Это не входит в компетенции агента.';

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

    /**
     * Текст вопроса для ИИ по данным CRM: команда /вопрос или /ask, либо «естественный» вопрос про цифры/транзакции.
     */
    public static function extractCrmAiQuestion(string $text): ?string
    {
        $cmd = self::extractCrmCommandQuestion($text);
        if ($cmd !== null && trim($cmd) !== '') {
            return trim($cmd);
        }
        if (self::isNaturalCrmDataQuestion($text)) {
            return trim($text);
        }

        return null;
    }

    /**
     * /вопрос текст… или /ask text… (поддержка @BotName в группах).
     */
    public static function extractCrmCommandQuestion(string $text): ?string
    {
        $t = trim($text);
        if (preg_match('/^\/(вопрос|ask)(@[\w]+)?\s+(.+)$/uis', $t, $m)) {
            return trim($m[3]);
        }

        return null;
    }

    /**
     * Короткий вопрос без команды: квартиры, транзакции, задачи (не пересекается с isHelpCurrentInfoRequest).
     */
    public static function isNaturalCrmDataQuestion(string $text): bool
    {
        if (trim($text) === '') {
            return false;
        }
        if (self::isHelpCurrentInfoRequest($text)) {
            return false;
        }
        $t = mb_strtolower(trim($text));
        if (mb_strlen($t) > 400) {
            return false;
        }
        if (preg_match('/сколько|какие|какая|какой|какое/u', $t) && preg_match('/квартир|транзакц|операц|баланс|клиент|задач/u', $t)) {
            return true;
        }
        if (preg_match('/последн(ие|их|ая|ую)/u', $t) && preg_match('/транзакц|операц|пополнен|списан|займ/u', $t)) {
            return true;
        }

        return false;
    }
}
