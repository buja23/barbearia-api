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