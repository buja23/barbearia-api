<?php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class CalendarWidget extends Widget
{
    public static function canView(): bool
    {
        // Oculta do Dashboard, mas mantém o widget funcional no código
        return ! request()->routeIs('filament.admin.pages.dashboard');
    }
    protected static string $view = 'filament.widgets.calendar-widget';

    public $selectedDate;

    public function selectDate($date)
    {
        $this->selectedDate = $date;
        // Mudamos 'data:' para 'date:' para sincronizar com o ListAppointments
        $this->dispatch('filtrar-data', date: $date);
    }
}
