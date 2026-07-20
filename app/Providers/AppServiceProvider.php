<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
         //if(env('APP_ENV') !== 'development') {
           //  URL::forceSchema('https');
         //}
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
         //if(env('APP_ENV') !== 'development') {
           //  $this->app['request']->server->set('HTTPS', true);
         //}
    }
}
