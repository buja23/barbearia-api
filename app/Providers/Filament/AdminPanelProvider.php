<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Tenancy\RegisterBarbershop;
use App\Http\Middleware\EnsureBusinessSubscriptionActive;
use App\Models\Barbershop;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Assets\Js;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Plugins\MyCalendar\MyCalendarPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')

            // --- Autenticação ---
            ->login()
            ->registration() // Permite que novos barbeiros se cadastrem

            // --- Multi-Tenancy (SaaS) ---
            // Cada barbearia é um tenant isolado. O slug da URL será o slug da barbearia.
            ->tenant(Barbershop::class, slugAttribute: 'slug')
            ->tenantRegistration(RegisterBarbershop::class) // Página de criar barbearia após cadastro

            // --- Aparência ---
            ->colors(['primary' => Color::Amber])

            // --- Discovery de Resources/Pages/Widgets ---
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                \App\Filament\Widgets\CalendarWidget::class,
            ])

            // --- Middlewares base ---
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])

            // --- Middlewares de autenticação ---
            ->authMiddleware([
                Authenticate::class,
            ])

            // --- Middleware de assinatura (bloqueia acesso se expirado) ---
            // Aplicado apenas às rotas do tenant (quando já há uma barbearia selecionada)
            ->tenantMiddleware([
                EnsureBusinessSubscriptionActive::class,
            ], isPersistent: true)

            // --- Assets ---
            ->assets([
                \Filament\Support\Assets\Css::make('fullcalendar-css', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css'),
                \Filament\Support\Assets\Js::make('fullcalendar-js', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'),
                Js::make('calendar-widget', asset('js/calendar-widget.js')),
            ]);
    }
}
