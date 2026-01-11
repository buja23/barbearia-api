<?php

namespace App\Filament\Plugins\MyCalendar;

use Filament\Contracts\Plugin;
use Filament\Panel;
use App\Filament\Plugins\MyCalendar\Widgets\CalendarWidget;

class MyCalendarPlugin implements Plugin
{
    public function getId(): string
    {
        return 'my-calendar';
    }

    public function register(Panel $panel): void
    {
        $panel->widgets([
            CalendarWidget::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // vazio por enquanto
    }
}
