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
            ->renderHook(
                'panels::head.end',
                fn (): string => Blade::render('
                <style>
                    /* Layout */
                    .fc { max-width: 480px !important; margin: 0 auto !important; font-family: inherit; }
                    .fc-toolbar { justify-content: center !important; gap: 20px; margin-bottom: 20px !important; }
                    .fc-toolbar-title { font-size: 1.2rem !important; font-weight: 700; }
                    .fc-button { background: transparent !important; border: none !important; color: #a1a1aa !important; }
                    .fc-theme-standard td, .fc-theme-standard th, .fc-scrollgrid { border: none !important; }

                    /* Clique e Interação */
                    .fc-daygrid-day-frame { cursor: pointer !important; }
                    .fc-daygrid-day-number, .fc-bg-event { pointer-events: none !important; }
                    
                    /* Visual */
                    .fc-daygrid-day-number { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; position: relative; z-index: 2; color: #e4e4e7; text-decoration: none !important; }
                    .fc-bg-event { opacity: 1 !important; border-radius: 50%; width: 32px !important; height: 32px !important; left: 50% !important; top: 50% !important; transform: translate(-50%, -50%) !important; z-index: 1 !important; }
                    .bg-evento-azul { background-color: #3b82f6 !important; }
                    .bg-evento-vermelho { background-color: #ef4444 !important; }
                    .dia-selecionado { box-shadow: inset 0 0 0 2px #f59e0b !important; border-radius: 50% !important; }
                </style>
                ')
            );
    }
} 