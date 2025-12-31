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
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
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
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->config([
                        'initialView' => 'dayGridMonth',
                        'headerToolbar' => [
                            'left' => 'prev',
                            'center' => 'title',
                            'right' => 'next',
                        ],
                    ]),
            ])
            ->renderHook(
                'panels::head.end',
                fn (): string => Blade::render('
                <style>
                    /* 1. Reset e Container */
                    .fc {
                        max-width: 380px !important;
                        margin: 0 auto !important;
                        font-family: inherit;
                        background: transparent;
                    }

                    /* 2. Cabeçalho Minimalista */
                    .fc-toolbar {
                        justify-content: center !important;
                        gap: 20px;
                        margin-bottom: 20px !important;
                    }
                    .fc-toolbar-title {
                        font-size: 1.1rem !important;
                        font-weight: 700;
                        color: white;
                    }
                    .fc-button {
                        background: transparent !important;
                        border: none !important;
                        color: #a1a1aa !important;
                        box-shadow: none !important;
                    }
                    .fc-button:hover { color: white !important; }

                    /* 3. Limpeza da Grade */
                    .fc-theme-standard td, .fc-theme-standard th, .fc-scrollgrid {
                        border: none !important;
                    }
                    .fc-col-header-cell-cushion {
                        text-transform: uppercase;
                        font-size: 0.75rem;
                        font-weight: 500;
                        color: #71717a; /* Zinc-500 */
                        text-decoration: none !important;
                    }

                    /* 4. Células do Dia */
                    .fc-daygrid-day-frame {
                        min-height: 40px !important;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        position: relative;
                    }

                    /* 5. Número do Dia (Fica na frente da bolinha) */
                    .fc-daygrid-day-top {
                        justify-content: center;
                        position: relative;
                        z-index: 10; 
                    }
                    .fc-daygrid-day-number {
                        width: 32px;
                        height: 32px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 0.9rem;
                        font-weight: 500;
                        color: #e4e4e7; /* Cor do número normal */
                        text-decoration: none !important;
                        z-index: 10;
                    }

                    /* 6. Bolinhas de Fundo (Eventos) */
                    .fc-bg-event {
                        opacity: 1 !important;
                        border-radius: 50%;
                        width: 32px !important; 
                        height: 32px !important;
                        left: 50% !important;
                        top: 4px !important; /* Ajuste fino vertical */
                        transform: translateX(-50%) !important;
                        z-index: 1 !important; /* Fica ATRÁS do número */
                        cursor: pointer;
                    }
                    
                    /* Tira o texto/título do evento de fundo se aparecer */
                    .fc-bg-event .fc-event-title { display: none; }

                    /* Cores das Bolinhas */
                    .bg-evento-azul { background-color: #3b82f6 !important; }
                    .bg-evento-vermelho { background-color: #ef4444 !important; }

                    /* 7. Destaque "HOJE" (Amarelo) */
                    .fc-day-today .fc-daygrid-day-number {
                        background-color: #f59e0b !important;
                        color: black !important;
                        border-radius: 50%;
                        font-weight: bold;
                    }

                 
                    .fc .fc-daygrid-day.fc-day-today {
                        background-color: transparent !important;
                    }
                </style>
                ')
            );
    }
}