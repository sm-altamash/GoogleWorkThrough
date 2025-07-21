<?php

return [
    'application_name' => env('APP_NAME', 'Laravel App'),
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    
    'scopes' => [
        'https://www.googleapis.com/auth/calendar ',
        'https://www.googleapis.com/auth/calendar.events ',
        'https://www.googleapis.com/auth/userinfo.email ',
        'https://www.googleapis.com/auth/userinfo.profile ',
        'https://www.googleapis.com/auth/youtube ',
        'https://www.googleapis.com/auth/youtube.upload ',
        'https://www.googleapis.com/auth/youtube.readonly ',
        'https://www.googleapis.com/auth/youtubepartner ',
        'https://www.googleapis.com/auth/drive ',
        'https://www.googleapis.com/auth/gmail.compose' ,
        'https://www.googleapis.com/auth/gmail.labels' ,
        'https://www.googleapis.com/auth/gmail.settings.basic' ,
        'https://www.googleapis.com/auth/gmail.settings.sharing' ,
        'https://mail.google.com/',
    ],
];