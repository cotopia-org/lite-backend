<?php

namespace App\Providers;

use Agence104\LiveKit\EgressServiceClient;
use App\Utilities\PersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EgressServiceClient::class, function ($app) {
            return new EgressServiceClient(config('livekit.host'), config('livekit.apiKey'), config('livekit.apiSecret'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

    }
}
