<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ([
            \App\Models\User::class, \App\Models\Barbershop::class,
            \App\Models\OpeningHour::class, \App\Models\Barber::class,
            \App\Models\Service::class, \App\Models\Product::class,
            \App\Models\Appointment::class, \App\Models\Order::class,
            \App\Models\OrderItem::class, \App\Models\Plan::class,
            \App\Models\Subscription::class,
        ] as $model) {
            $model::observe(\App\Observers\DemoProtectionObserver::class);
        }

        // Usar config() em vez de env() porque estamos a usar config:cache no Render!
        if (config('app.env') === 'production') {
            // 1. Força o Laravel a usar o domínio exato do Render
            URL::forceRootUrl(config('app.url'));
            
            // 2. Força a criação de links seguros
            URL::forceScheme('https');
            
            // 3. Convence o núcleo do PHP de que a ligação é 100% segura para os Cookies
            request()->server->set('HTTPS', 'on');
        }
    }
}
