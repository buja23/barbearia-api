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
use Illuminate\Support\Js;

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
                    //'selectable' => true,
                    
                    // A MÁGICA ACONTECE AQUI:
                   'dateClick' => new Js("
                        function(info) {
                            // 1. Teste de Vida: Vai aparecer uma janela no seu navegador. 
                            // Se NÃO aparecer, o FullCalendar está travado.
                            alert('Cliquei no dia: ' + info.dateStr); 

                            // Limpeza visual
                            document.querySelectorAll('.dia-selecionado').forEach(el => el.classList.remove('dia-selecionado'));
                            document.querySelectorAll('.fc-highlight').forEach(el => el.remove());
                            info.dayEl.classList.add('dia-selecionado');

                            // 2. Tentativa Forte de Dispatch (Usando window.Livewire)
                            if (window.Livewire) {
                                console.log('Enviando evento para Livewire...');
                                window.Livewire.dispatch('data-alterada', { date: info.dateStr });
                            } else {
                                alert('ERRO CRÍTICO: O Livewire não foi encontrado na página!');
                            }
                        }
                    "),
                ]),
        ])
     ->renderHook(
                'panels::head.end',
                fn (): string => Blade::render('
                <style>
                    /* === 1. Layout Básico e Limpeza === */
                    .fc {
                        max-width: 380px !important;
                        margin: 0 auto !important;
                        font-family: inherit;
                        background: transparent;
                    }
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
                    .fc-theme-standard td, .fc-theme-standard th, .fc-scrollgrid { border: none !important; }
                    .fc-col-header-cell-cushion { color: #71717a; text-decoration: none !important; font-weight: 500; text-transform: uppercase; font-size: 0.75rem; }

                    /* === 2. A CORREÇÃO DO CLIQUE (NUCLEAR) === */
                    
                    /* Garante que a Célula do Dia (Frame) seja clicável */
                    .fc-daygrid-day-frame {
                        min-height: 40px !important;
                        position: relative;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        cursor: pointer !important; /* Mostra a mãozinha */
                        pointer-events: auto !important; /* Ativa o clique aqui */
                        z-index: 1;
                    }

                    /* Força TODOS os elementos dentro do dia a serem "fantasmas" para o mouse */
                    /* O clique vai atravessar números, bolinhas, textos e acertar o Frame */
                    .fc-daygrid-day-frame * {
                        pointer-events: none !important; 
                    }

                    /* === 3. Visual das Bolinhas e Números === */
                    .fc-daygrid-day-number {
                        width: 32px; height: 32px;
                        display: flex; align-items: center; justify-content: center;
                        font-size: 0.9rem; font-weight: 500; color: #e4e4e7;
                        text-decoration: none !important;
                        z-index: 10; /* Fica na frente visualmente */
                    }

                    .fc-bg-event {
                        opacity: 1 !important;
                        border-radius: 50%;
                        width: 32px !important; height: 32px !important;
                        left: 50% !important; top: 50% !important; /* Centraliza absoluto */
                        transform: translate(-50%, -50%) !important;
                        z-index: 5 !important; /* Atrás do número */
                    }
                    .fc-bg-event .fc-event-title { display: none; }
                    
                    .bg-evento-azul { background-color: #3b82f6 !important; }
                    .bg-evento-vermelho { background-color: #ef4444 !important; }

                    /* === 4. Feedback Visual de Seleção (Borda Amarela) === */
                    .dia-selecionado {
                        box-shadow: inset 0 0 0 3px #f59e0b !important;
                        border-radius: 8px !important;
                        background-color: rgba(245, 158, 11, 0.1);
                    }

                    /* Hoje (Amarelo) */
                    .fc-day-today .fc-daygrid-day-number {
                        background-color: #f59e0b !important;
                        color: black !important;
                        border-radius: 50%;
                        font-weight: bold;
                    }
                    .fc .fc-daygrid-day.fc-day-today { background-color: transparent !important; }

                    .fc-highlight {
                    background: rgba(245, 158, 11, 0.2) !important;
                    box-shadow: inset 0 0 0 2px #f59e0b !important;
                    border-radius: 8px !important;
                     }
                </style>
                ')
            );
    }
}