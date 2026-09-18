<?php

namespace App\Providers;

use App\Services\SidebarNotificationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        $this->configureRateLimiting();

        if ($this->app->environment('local')) {
            TrustProxies::at('*');

            $this->app->booted(function (): void {
                if ($this->app->runningInConsole() || ! $this->app->bound('request')) {
                    return;
                }

                $request = $this->app->make('request');

                if ($request->getHost() !== '') {
                    URL::forceRootUrl($request->getSchemeAndHttpHost());
                }
            });
        }

        View::composer(['layouts.app', 'partials.sidebar-*'], function ($view): void {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $service = app(SidebarNotificationService::class);

            $view->with([
                'sidebarNotifications' => $service->forUser($user),
                'inboxPreviewItems' => $user->isAdmin()
                    ? $service->inboxItems($user, 8)
                    : collect(),
            ]);
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(10)->by($key);
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('autosave', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('form-actions', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('sidebar-notifications', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('status-permohonan-sync', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}
