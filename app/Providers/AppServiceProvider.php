<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
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
        Model::unguard();
        Model::preventLazyLoading(! app()->isProduction());

        // Customizing email verification notification
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject(__('notification-email.email-verify.subject'))
                ->greeting(__('notification-email.email-verify.greeting', ['name' => $notifiable->name]))
                ->line(__('notification-email.email-verify.line_1'))
                ->action(__('notification-email.email-verify.action'), $url)
                ->line(__('notification-email.email-verify.line_2'));
        });

        // Customizing password reset email notification
        ResetPassword::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject(__('notification-email.password-reset.subject'))
                ->greeting(__('notification-email.password-reset.greeting', ['name' => $notifiable->name]))
                ->line(__('notification-email.password-reset.line_1'))
                ->action(__('notification-email.password-reset.action'), $url)
                ->line(__('notification-email.password-reset.line_2', ['count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire')]))
                ->line(__('notification-email.password-reset.line_3'));
        });
    }
}
