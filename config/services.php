<?php

return [
    'vapid' => [
        'public' => env('VAPID_PUBLIC_KEY', ''),
        'private' => env('VAPID_PRIVATE_KEY', ''),
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],
    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY', ''),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
    ],
    /** Кеш снимка CRM для ИИ (сек). Снижает задержку при частых сообщениях в Telegram. */
    'crm_snapshot_cache_ttl' => (int) env('CRM_SNAPSHOT_CACHE_TTL', 45),
    /** Макс. строк проданных квартир в JSON для ИИ (остальное — через агрегаты sold). */
    'crm_snapshot_sold_apartments_limit' => (int) env('CRM_SNAPSHOT_SOLD_APARTMENTS_LIMIT', 300),
    /** Лимиты расширенного снимка CRM для ИИ (агент видит выборки, не всю БД целиком). */
    'crm_snapshot_clients_limit' => (int) env('CRM_SNAPSHOT_CLIENTS_LIMIT', 250),
    'crm_snapshot_available_apartments_limit' => (int) env('CRM_SNAPSHOT_AVAILABLE_APARTMENTS_LIMIT', 200),
    'crm_snapshot_in_pledge_apartments_limit' => (int) env('CRM_SNAPSHOT_IN_PLEDGE_APARTMENTS_LIMIT', 200),
    'crm_snapshot_tasks_open_limit' => (int) env('CRM_SNAPSHOT_TASKS_OPEN_LIMIT', 150),
    'crm_snapshot_tasks_completed_limit' => (int) env('CRM_SNAPSHOT_TASKS_COMPLETED_LIMIT', 50),
    'crm_snapshot_construction_stages_limit' => (int) env('CRM_SNAPSHOT_CONSTRUCTION_STAGES_LIMIT', 80),
    'crm_snapshot_transactions_limit' => (int) env('CRM_SNAPSHOT_TRANSACTIONS_LIMIT', 60),
    'crm_snapshot_investments_limit' => (int) env('CRM_SNAPSHOT_INVESTMENTS_LIMIT', 100),
    /** Пауза между запросами ИИ от одного пользователя в группе (сек). 0 = выкл. Было жёстко 2 с — часто резало второе сообщение. */
    'telegram_ai_cooldown_seconds' => (int) env('TELEGRAM_AI_COOLDOWN_SECONDS', 0),
    'jitsi' => [
        // Базовый URL сервера Jitsi Meet (без завершающего слэша).
        // Публичный: https://meet.jit.si
        // Свой сервер: https://meet.ваш-домен.ru
        'server_url' => env('JITSI_SERVER_URL', 'https://meet.jit.si'),
    ],
    'lombard' => [
        'name' => env('LOMBARD_NAME', 'Ломбард'),
        'phone' => env('LOMBARD_PHONE', '+7 (383) 291-00-51'),
    ],
];
