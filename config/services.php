<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
    'admin_email' => env('GOOGLE_ADMIN_EMAIL', 'vol@leads.edu.pk'),
    'service_account_path' => storage_path('app/google/service-account-key.json'),
    'domain' => env('GOOGLE_DOMAIN', 'leads.edu.pk'),
    ],


    'twilio' => [
    'sid' => env('TWILIO_SID'),
    'token' => env('TWILIO_AUTH_TOKEN'),
    'whatsapp_number' => env('TWILIO_WHATSAPP_NUMBER'),
    ],

    'ai_agent' => [
    'base_url' => env('AI_AGENT_BASE_URL', 'http://localhost:8000'),
    'api_key' => env('AI_AGENT_API_KEY'),
    'timeout' => env('AI_AGENT_TIMEOUT', 30),
    ],


    'department_org_units' => [
        'Computer Science' => '/Departments/Computer Science',
        'Business Administration' => '/Departments/Business',
        'Engineering' => '/Departments/Engineering',
        'Management Sciences' => '/Departments/Management',
        'Information Technology' => '/Departments/IT',
        'Software Engineering' => '/Departments/Software',
        // Add more departments as needed
    ],


    
    'account_defaults' => [
        'change_password_at_next_login' => true,
        'include_in_global_address_list' => true,
        'is_admin' => false,
        'is_delegated_admin' => false,
        'suspended' => false,
        'password_length' => 16,
    ],

    
    'rate_limiting' => [
        'max_bulk_operations' => 50,
        'delay_between_requests' => 500000,
        'max_retries' => 3,
        'cache_duration' => 300,
    ],
    
];
