<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Carbon\Carbon;

class CalendarWidget extends Widget
{
    protected static string $view = 'filament.widgets.calendar-widget';

    public $selectedDate;

    public function selectDate($date)
    {
        $this->selectedDate = $date;

        // Aqui você pode salvar no banco, por exemplo:
        // Appointment::create(['date' => $date]);

        $this->dispatch('saved', ['date' => $date]);
    }
}
