<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('firebase', function ($app) {
            // Service-account JSON from the Firebase console. Path is relative to
            // the project root; set FIREBASE_CREDENTIALS in .env to change it.
            $credentials = base_path(config('services.firebase.credentials'));

            if (!is_file($credentials)) {
                throw new \RuntimeException("Firebase credentials file not found: {$credentials}");
            }

            return (new Factory)
                ->withServiceAccount($credentials)
                ->createMessaging();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
