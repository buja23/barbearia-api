<?php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class CalendarWidget extends Widget
{
    public static function canView(): bool
    {
        // Oculta do Dashboard, mas mantém o widget funcional no código
        return false;
    }
    protected static string $view = 'filament.widgets.calendar-widget';

    public $selectedDate;

    public function selectDate($date)
    {
        $this->selectedDate = $date;

        // Aqui você pode salvar no banco, por exemplo:
        // Appointment::create(['date' => $date]);

        $this->dispatch('filtrar-data', data: $date);
    }

}
