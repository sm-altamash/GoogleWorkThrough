<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Basic App and Google Integration Settings
    |--------------------------------------------------------------------------
    */
    'application_name' => env('APP_NAME', 'Laravel App'),

    // Google OAuth credentials
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | Google Workspace Domain and Admin
    |--------------------------------------------------------------------------
    */
    'domain' => env('GOOGLE_WORKSPACE_DOMAIN', 'leads.edu.pk'),
    'admin_email' => env('GOOGLE_ADMIN_EMAIL', 'vol@leads.edu.pk'),

    /*
    |--------------------------------------------------------------------------
    | Service Account Credentials
    |--------------------------------------------------------------------------
    */
    'service_account_path' => env('GOOGLE_SERVICE_ACCOUNT_PATH', storage_path('app/google/service-account.json')),
    'credentials_path' => env('GOOGLE_CREDENTIALS_PATH'),

    /*
    |--------------------------------------------------------------------------
    | API Scopes - Define all required Google APIs here
    |--------------------------------------------------------------------------
    */
    'scopes' => [
        // Admin SDK scopes
        'https://www.googleapis.com/auth/admin.directory.user',
        'https://www.googleapis.com/auth/admin.directory.group',
        'https://www.googleapis.com/auth/admin.directory.orgunit',

        // Gmail
        'https://www.googleapis.com/auth/gmail.compose',
        'https://www.googleapis.com/auth/gmail.labels',
        'https://www.googleapis.com/auth/gmail.settings.basic',
        'https://www.googleapis.com/auth/gmail.settings.sharing',
        'https://mail.google.com/',

        // Calendar
        'https://www.googleapis.com/auth/calendar',
        'https://www.googleapis.com/auth/calendar.events',

        // User Info
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',

        // YouTube
        'https://www.googleapis.com/auth/youtube',
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube.readonly',
        'https://www.googleapis.com/auth/youtubepartner',

        // Drive
        'https://www.googleapis.com/auth/drive',
        'https://www.googleapis.com/auth/drive.readonly',

        // Google Classroom
        'https://www.googleapis.com/auth/classroom.courses',
        'https://www.googleapis.com/auth/classroom.courses.readonly',
        'https://www.googleapis.com/auth/classroom.coursework.me',
        'https://www.googleapis.com/auth/classroom.coursework.me.readonly',
        'https://www.googleapis.com/auth/classroom.coursework.students',
        'https://www.googleapis.com/auth/classroom.coursework.students.readonly',
        'https://www.googleapis.com/auth/classroom.rosters',
        'https://www.googleapis.com/auth/classroom.rosters.readonly',
        'https://www.googleapis.com/auth/classroom.profile.emails',
        'https://www.googleapis.com/auth/classroom.profile.photos',
        'https://www.googleapis.com/auth/classroom.student-submissions.me.readonly',
        'https://www.googleapis.com/auth/classroom.student-submissions.students.readonly',
        'https://www.googleapis.com/auth/classroom.announcements',
        'https://www.googleapis.com/auth/classroom.announcements.readonly',

        // Google Forms
        'https://www.googleapis.com/auth/forms.body',
        'https://www.googleapis.com/auth/forms.responses.readonly',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'timeout' => env('GOOGLE_API_TIMEOUT', 30),
        'retries' => env('GOOGLE_API_RETRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Defaults
    |--------------------------------------------------------------------------
    */
    'default_org_unit' => env('GOOGLE_DEFAULT_ORG_UNIT', '/'),

    'default_org_units' => [
        'students' => '/Students',
        'faculty' => '/Faculty',
        'staff' => '/Staff',
    ],

    'default_password_length' => 8,
    'force_password_change' => true,

    /*
    |--------------------------------------------------------------------------
    | Google Classroom Defaults
    |--------------------------------------------------------------------------
    */
    'classroom' => [
        'default_page_size' => 50,
        'max_page_size' => 100,
        'default_course_state' => 'ACTIVE',
        'default_coursework_state' => 'PUBLISHED',
    ],
];
