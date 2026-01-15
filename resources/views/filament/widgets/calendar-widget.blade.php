<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-calendar" icon-color="primary">
        <x-slot name="heading">Agenda Interativa</x-slot>
        
        <div x-data="calendarWidget(@this)" class="w-full">
            <style>
                .fc { --fc-border-color: #e5e7eb; }
                .fc .fc-toolbar-title { font-size: 1.1rem; font-weight: 600; }
                .fc .fc-button-primary { background: #4b5563; border: none; }
                .fc .fc-daygrid-day.fc-day-today { background: rgba(var(--primary-500), 0.1); }
            </style>

            <div id="calendar" wire:ignore class="rounded-lg border border-gray-100 overflow-hidden shadow-inner"></div> 

            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                    <x-filament::icon icon="heroicon-m-funnel" class="h-4 w-4 text-gray-400" />
                    <span>Filtrando data:</span>
                    <x-filament::badge color="info">
                        {{-- CORREÇÃO: Agora o texto muda instantaneamente sem recarregar --}}
                        <span x-text="$wire.selectedDate ? new Date($wire.selectedDate + 'T00:00:00').toLocaleDateString('pt-BR') : 'Hoje'"></span>
                    </x-filament::badge>
                </div>
                
                <p class="text-xs text-gray-400 italic">Clique em um dia para filtrar</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>