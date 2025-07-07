<?php

return [

    'scopes' => [
    'https://www.googleapis.com/auth/calendar',
    // 'https://www.googleapis.com/auth/gmail.readonly', // Gmail
    // 'https://www.googleapis.com/auth/drive.file',     // Drive
    // Add more scopes as needed
    ],

    'application_name' => env('APP_NAME', 'Laravel App'),
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    'scopes' => [
        'https://www.googleapis.com/auth/calendar',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
    ],
];