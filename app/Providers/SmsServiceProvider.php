<?php

namespace App\Providers;

use App\Services\Sms\GatewayRouter;
use App\Services\Sms\SmsService;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // GatewayRouter — stateless, singleton per request/job lifecycle
        $this->app->singleton(GatewayRouter::class, fn () => new GatewayRouter());

        // SmsService — singleton, injected into controllers
        $this->app->singleton(SmsService::class, function ($app) {
            return new SmsService();
        });
    }

    public function boot(): void
    {
        //
    }
}