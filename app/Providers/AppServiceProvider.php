<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AIPersonalizationService;
use App\Services\ReminderSchedulerService;
use App\Services\VoiceService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VoiceService::class, function ($app) {
            return new VoiceService();
        });

        $this->app->singleton(AIPersonalizationService::class, function ($app) {
            return new AIPersonalizationService();
        });

        $this->app->singleton(ReminderSchedulerService::class, function ($app) {
            return new ReminderSchedulerService(
                $app->make(VoiceService::class),
                $app->make(AIPersonalizationService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
