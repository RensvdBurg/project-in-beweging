<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API van de UpMove-server
    |--------------------------------------------------------------------------
    |
    | De schermen van de app halen al hun gegevens via de API (docs/08-api.md).
    | Op de telefoon: zet UPMOVE_API_URL in .env op de server, bijvoorbeeld
    | https://upmove.example.nl/api/v1 (of http://<ip-van-je-computer>:8000/api/v1
    | tijdens het ontwikkelen op hetzelfde wifi-netwerk).
    |
    */

    'api_url' => rtrim((string) env('UPMOVE_API_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/api/v1'), '/'),

    // Seconden voordat de app opgeeft en "Geen verbinding met de server" toont.
    'api_timeout' => (int) env('UPMOVE_API_TIMEOUT', 15),

    // Alleen voor tests: API-verzoeken binnen hetzelfde proces afhandelen in plaats van via HTTP.
    'api_in_process' => (bool) env('UPMOVE_API_IN_PROCESS', false),

];
