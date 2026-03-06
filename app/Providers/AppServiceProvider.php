<?php

namespace App\Providers;

use App\Delivery\Contracts\ProviderInterface;
use App\Delivery\Providers\WebhookSiteProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProviderInterface::class, WebhookSiteProvider::class);
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(600)->by($request->ip());
        });
    }
}
