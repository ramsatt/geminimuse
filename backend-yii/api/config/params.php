<?php
return [
    'geminiApiKey' => getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY',

    'allowedOrigins' => [
        'https://codingtamilan.in',
        'http://localhost:4200',    // Angular dev server
        'capacitor://localhost',    // Capacitor iOS
        'http://localhost',         // Capacitor Android
    ],

    'copyRateLimitPerDay' => 20,
];
