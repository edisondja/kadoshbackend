<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // GoDaddy / SMTP SSL (puerto 465): evita fallos de verificación en algunos servidores
        if (config('mail.driver') === 'smtp') {
            $this->app->afterResolving('swift.transport', function ($transport) {
                if ($transport instanceof \Swift_Transport_EsmtpTransport) {
                    $transport->setStreamOptions([
                        'ssl' => [
                            'verify_peer' => true,
                            'verify_peer_name' => true,
                            'allow_self_signed' => false,
                        ],
                    ]);
                }
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
