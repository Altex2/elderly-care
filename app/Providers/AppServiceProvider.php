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
        // Add OpenAI API key to services config if it's set in the environment
        if (env('OPENAI_API_KEY')) {
            config(['services.openai.api_key' => env('OPENAI_API_KEY')]);
        }
        
        // Set default timezone for Carbon
        \Carbon\Carbon::setLocale('ro');
        date_default_timezone_set('Europe/Bucharest');
        
        // Set default date and time format for Carbon to use 24-hour format (Romanian style)
        \Carbon\Carbon::setToStringFormat('Y-m-d H:i:s');
    }
}
