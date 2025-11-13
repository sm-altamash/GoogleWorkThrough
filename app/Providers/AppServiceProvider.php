<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GoogleClientService;
use App\Services\GoogleCalendarService;
use App\Services\GoogleDriveService;
use App\Services\DatabaseBackupService;



class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(GoogleClientService::class, function ($app) {
            return new GoogleClientService();
        });

        $this->app->singleton(GoogleCalendarService::class, function ($app) {
            return new GoogleCalendarService($app->make(GoogleClientService::class));
        });

        $this->app->singleton(GoogleDriveService::class, function ($app) {
            return new GoogleDriveService($app->make(GoogleClientService::class));
        });

        $this->app->singleton(DatabaseBackupService::class, function ($app) {
            return new DatabaseBackupService();
        });
    }

    public function boot()
    {
        //
    }
}
