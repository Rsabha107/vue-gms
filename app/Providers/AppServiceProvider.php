<?php

namespace App\Providers;

use App\Models\GeneralSettings\Setting;
use App\Services\Gms\GmsMockData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Share events with all GMS Inertia pages
        Inertia::share([
            'gmsEvents' => function () {
                // Only load events for GMS routes
                if (request()->is('gms*')) {
                    try {
                        return GmsMockData::getEvents();
                    } catch (\Throwable $e) {
                        return [];
                    }
                }
                return [];
            },
        ]);

        // if (app()->runningInConsole()) {
        //     return;
        // }

        // try {
        //     if (Schema::hasTable('settings')) {

        //         // Cache for 24 hours (adjust as needed)
        //         $settings = Cache::remember('app_settings', now()->addHours(24), function () {
        //             return Setting::pluck('value', 'key')->toArray();
        //         });

        //         config(['settings' => $settings]);
        //     }
        // } catch (\Throwable $e) {
        //     // Avoid crashing if DB not ready
        //     // optional: Log::warning('Settings not loaded: '.$e->getMessage());
        // }

        Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            // $event->extendSocialite('google', \SocialiteProviders\Google\Provider::class);
            $event->extendSocialite('microsoft', \SocialiteProviders\Microsoft\Provider::class);
        });

        if ($this->app->environment('azure')) {
            URL::forceScheme('https');
        }

        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols();
        });
    }
}
