<?php

return [
    'application_name' => env('APP_NAME', 'Laravel App'),
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    'domain' => env('GOOGLE_WORKSPACE_DOMAIN', 'leads.edu.pk'),
    'service_account_path' => env('GOOGLE_SERVICE_ACCOUNT_PATH'),
    'admin_email' => env('GOOGLE_ADMIN_EMAIL'),
    'credentials_path' => env('GOOGLE_CREDENTIALS_PATH'),

    
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



    
    'api' => [
        'timeout' => env('GOOGLE_API_TIMEOUT', 30),
        'retries' => env('GOOGLE_API_RETRIES', 3),
    ],


    'default_org_units' => [
        'students' => '/Students',
        'faculty' => '/Faculty',
        'staff' => '/Staff',
    ],
];