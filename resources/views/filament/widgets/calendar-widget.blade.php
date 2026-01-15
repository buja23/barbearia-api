{{-- resources/views/filament/widgets/calendar-widget.blade.php --}}
<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-calendar" icon-color="primary">
        <x-slot name="heading">Agenda de Atendimentos</x-slot>
        
        <div x-data="calendarWidget(@this)" class="w-full">
            <style>
                /* CSS Senior para integrar o FullCalendar ao tema do Filament */
                .fc { --fc-border-color: #f3f4f6; font-family: inherit; }
                .fc .fc-toolbar-title { font-size: 1.1rem; font-weight: 600; color: #374151; }
                .fc .fc-button-primary { background: #4b5563; border: none; font-size: 0.8rem; text-transform: capitalize; }
                .fc .fc-button-primary:hover { background: #1f2937; }
                .fc .fc-daygrid-day.fc-day-today { background: rgba(var(--primary-500), 0.05); }
                .fc .fc-daygrid-day { cursor: pointer; transition: background 0.2s; }
                .fc .fc-daygrid-day:hover { background: #f9fafb; }
            </style>

            <div id="calendar" wire:ignore class="rounded-xl border border-gray-100 overflow-hidden shadow-sm"></div> 

            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                    <x-filament::icon icon="heroicon-m-funnel" class="h-5 w-5 text-gray-400" />
                    <span>Filtrando data:</span>
                    <x-filament::badge color="info">
                        {{-- O AlpineJS agora 'observa' o PHP via $wire em tempo real --}}
                        <span x-text="$wire.selectedDate ? new Date($wire.selectedDate + 'T12:00:00').toLocaleDateString('pt-BR') : 'Todos os registros'"></span>
                    </x-filament::badge>
                </div>
                
                <p class="text-xs text-gray-400 italic">Clique em um dia para atualizar a lista abaixo</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>