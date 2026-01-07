<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Blade; 
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(['primary' => Color::Amber])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([Widgets\AccountWidget::class, Widgets\FilamentInfoWidget::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class])
            ->plugins([
                // === AQUI MUDOU: Removemos o ->config() para não dar conflito ===
                FilamentFullCalendarPlugin::make()
            ])
         // ...
->renderHook(
    'panels::head.end',
    fn (): string => Blade::render('
    <style>
        /* Layout Básico */
        .fc { max-width: 480px !important; margin: 0 auto !important; font-family: inherit; }
        
        /* === CORREÇÃO CRÍTICA DO CLIQUE === */
        /* O Frame do dia DEVE ser clicável */
        .fc-daygrid-day-frame { cursor: pointer !important; pointer-events: auto !important; }
        
        /* Os enfeites (números/bolinhas) NÃO podem bloquear o clique */
        .fc-daygrid-day-number, .fc-bg-event { pointer-events: none !important; }

        /* === VISUAL === */
        /* Bolinhas Azuis/Vermelhas */
        .bg-evento-azul { background-color: #3b82f6 !important; opacity: 1 !important; border-radius: 50%; width: 32px; height: 32px; transform: translate(-50%, -50%); top: 50%; left: 50%; }
        .bg-evento-vermelho { background-color: #ef4444 !important; opacity: 1 !important; border-radius: 50%; width: 32px; height: 32px; transform: translate(-50%, -50%); top: 50%; left: 50%; }

        /* === NOVO: O QUADRADO AMARELO CONTROLADO PELO PHP === */
        .dia-selecionado-php {
            background-color: rgba(245, 158, 11, 0.2) !important; /* Fundo Amarelo Claro */
            border: 2px solid #f59e0b !important; /* Borda Laranja */
            border-radius: 4px;
            opacity: 1 !important;
            z-index: 0 !important; /* Fica atrás do número */
        }
    </style>
    ')
);
    }
} 