<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\EligibilityService::class);
        $this->app->singleton(\App\Services\PaymentService::class);
        $this->app->singleton(\App\Services\AnthropicService::class);
        $this->app->singleton(\App\Services\LumosImportService::class);
    }

    public function boot(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $mailer = \App\Models\Setting::get('mail_mailer');
                if ($mailer) {
                    config([
                        'mail.default'                 => $mailer,
                        'mail.mailers.smtp.host'       => \App\Models\Setting::get('mail_host'),
                        'mail.mailers.smtp.port'       => \App\Models\Setting::get('mail_port', 587),
                        'mail.mailers.smtp.username'   => \App\Models\Setting::get('mail_username'),
                        'mail.mailers.smtp.password'   => \App\Models\Setting::get('mail_password'),
                        'mail.mailers.smtp.encryption' => \App\Models\Setting::get('mail_encryption', 'tls'),
                        'mail.from.address'            => \App\Models\Setting::get('mail_from_address'),
                        'mail.from.name'               => \App\Models\Setting::get('mail_from_name', 'Advernology Service'),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Don't break the app if settings table isn't ready yet
        }
    }
}
