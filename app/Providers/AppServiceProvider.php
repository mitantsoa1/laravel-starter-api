<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

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
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function ($user, string $token) {
            return env('FRONTEND_URL') . '/reset-password?token=' . $token . '&email=' . $user->email;
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Use GenericPolicy as a fallback for models without a specific policy
        \Illuminate\Support\Facades\Gate::guessPolicyNamesUsing(function (string $modelClass) {
            $policyName = 'App\\Policies\\' . class_basename($modelClass) . 'Policy';

            if (class_exists($policyName)) {
                return $policyName;
            }

            return \App\Policies\GenericPolicy::class;
        });
    }
}
