<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (mixed $notifiable, string $token): string {
            $storedUrl = SiteSetting::getValue('url');
            $frontendUrl = rtrim(
                $storedUrl ?: config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')),
                '/'
            );

            return "{$frontendUrl}/auth/reset-password?token={$token}&email=".urlencode($notifiable->email);
        });
    }
}
