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
        // O martelo definitivo para Proxies, Docker e Livewire
        if (env('APP_ENV') === 'production') {
            // 1. Força o Laravel a usar o domínio exato do Render (Ignora o IP do Apache)
            URL::forceRootUrl(env('APP_URL'));
            
            // 2. Força a criação de links seguros
            URL::forceScheme('https');
            
            // 3. Convence o núcleo do PHP de que a ligação é 100% segura para os Cookies
            request()->server->set('HTTPS', 'on');
        }
    }
}