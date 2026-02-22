<?php

return [
    'vapid' => [
        'public' => env('VAPID_PUBLIC_KEY', ''),
        'private' => env('VAPID_PRIVATE_KEY', ''),
    ],
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
