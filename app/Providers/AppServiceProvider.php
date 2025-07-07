<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GoogleClientService;
use App\Services\GoogleCalendarService;


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
    }

    public function boot()
    {
        //
    }
}