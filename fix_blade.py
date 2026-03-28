content = r"""<x-filament-widgets::widget>
    <x-filament::section class="!p-0 !rounded-3xl !shadow-none !border-0 !ring-0 overflow-hidden bg-transparent">
        <div
            wire:ignore
            x-data="calendarWidget(@this, {{ json_encode($this->getCalendarEvents()) }})"
            class="relative bg-white dark:bg-gray-950 p-8 md:p-10"
        >
            <div class="flex items-center justify-between mb-[30px]">
                <h2 id="calendar-title" class="text-5xl md:text-6xl font-black text-gray-900 dark:text-white tracking-tighter capitalize font-sans"></h2>
                <div class="flex items-center gap-2">
                    <button @click="calendar.prev()" class="p-3 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-900 transition-all text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                        <x-filament::icon icon="heroicon-m-chevron-left" class="h-6 w-6" />
                    </button>
                    <button @click="calendar.today()" class="px-5 py-2 text-sm font-bold text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white uppercase tracking-wider transition-all">
                        Hoje
                    </button>
                    <button @click="calendar.next()" class="p-3 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-900 transition-all text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                        <x-filament::icon icon="heroicon-m-chevron-right" class="h-6 w-6" />
                    </button>
                </div>
            </div>

            <div id="calendar" class="calendar-pro-theme min-h-[550px]"></div>

            <div class="flex items-center gap-5 mt-6 px-1">
                <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Lota&ccedil;&atilde;o</span>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-full opacity-70" style="background:#3b82f6"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Tranquilo</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-full opacity-70" style="background:#f97316"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">M&eacute;dio</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-full opacity-70" style="background:#ef4444"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Lotado</span>
                </div>
            </div>

            <div wire:loading.flex wire:target="selectDate"
                class="absolute inset-0 z-50 flex items-center justify-center bg-white/70 dark:bg-gray-950/70 backdrop-blur-sm rounded-3xl">
                <x-filament::loading-indicator class="h-10 w-10 text-indigo-600" />
            </div>
        </div>

        <script>
            document.addEventListener('alpine:init', function() {
                Alpine.data('calendarWidget', function(wire, allEvents) {
                    return {
                        calendar: null,
                        init: function() {
                            var self = this;
                            var calendarEl = document.getElementById('calendar');
                            var titleEl = document.getElementById('calendar-title');
                            self.calendar = new FullCalendar.Calendar(calendarEl, {
                                initialView: 'dayGridMonth',
                                locale: 'pt-br',
                                events: allEvents,
                                headerToolbar: false,
                                dayHeaderFormat: { weekday: 'narrow' },
                                fixedWeekCount: false,
                                showNonCurrentDates: false,
                                contentHeight: 'auto',
                                dayCellContent: function(arg) {
                                    return { html: '<div class="day-content">' + arg.dayNumberText + '</div>' };
                                },
                                datesSet: function(info) {
                                    titleEl.innerText = info.view.title;
                                },
                                eventDidMount: function(info) {
                                    if (info.event.display !== 'background') return;
                                    var count = info.event.extendedProps && info.event.extendedProps.count;
                                    if (!count) return;
                                    var dayCell = info.el.closest('.fc-daygrid-day');
                                    if (!dayCell || dayCell.querySelector('.appointment-badge')) return;
                                    var frame = dayCell.querySelector('.fc-daygrid-day-frame');
                                    if (!frame) return;
                                    var badge = document.createElement('div');
                                    badge.className = 'appointment-badge';
                                    badge.textContent = count;
                                    frame.appendChild(badge);
                                },
                                dateClick: function(info) {
                                    document.querySelectorAll('.dia-selecionado').forEach(function(el) {
                                        el.classList.remove('dia-selecionado');
                                    });
                                    var frame = info.dayEl.querySelector('.fc-daygrid-day-frame');
                                    if (frame) frame.classList.add('dia-selecionado');
                                    wire.selectDate(info.dateStr);
                                }
                            });
                            self.calendar.render();
                        }
                    };
                });
            });
        </script>

        <style>
            .calendar-pro-theme { font-family: Inter, sans-serif; }
            .fc-theme-standard td, .fc-theme-standard th, .fc-theme-standard .fc-scrollgrid,
            .fc-scrollgrid-section-header > *, .fc-scrollgrid-section-body > * { border: none !important; background: transparent !important; }
            .fc-col-header-cell-cushion { color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; padding-bottom: 30px !important; text-decoration: none !important; opacity: 0.5; }
            .dark .fc-col-header-cell-cushion { color: #9ca3af; }
            .fc-daygrid-day-frame { position: relative; height: 56px !important; width: 56px !important; margin: 0 auto 12px auto; display: flex; justify-content: center; align-items: center; cursor: pointer; border-radius: 18px; transition: all 0.2s; }
            .fc-daygrid-day-frame:hover { background-color: #f3f4f6; transform: scale(1.15); }
            .dark .fc-daygrid-day-frame:hover { background-color: #1f2937; }
            .fc-daygrid-day-number { font-size: 1.1rem; font-weight: 500; color: #374151; z-index: 20; text-decoration: none !important; }
            .dark .fc-daygrid-day-number { color: #e2e8f0; }
            .fc-day-today .fc-daygrid-day-frame { background: #4f46e5 !important; box-shadow: 0 8px 20px rgba(79,70,229,0.3); }
            .fc-day-today .fc-daygrid-day-number { color: white !important; font-weight: 700; }
            .fc-day-today { background: transparent !important; }
            .fc-bg-event { opacity: 0.2 !important; border-radius: 18px; transform: scale(0.9); }
            .appointment-badge { position: absolute; bottom: 5px; right: 6px; font-size: 0.6rem; font-weight: 800; color: #6366f1; line-height: 1; pointer-events: none; opacity: 0.85; }
            .dark .appointment-badge { color: #818cf8; }
            .fc-day-today .appointment-badge { color: rgba(255,255,255,0.85); }
            .dia-selecionado { box-shadow: 0 0 0 3px #030712, 0 0 0 5px #4f46e5 !important; z-index: 30; transform: scale(1.05); }
        </style>
    </x-filament::section>
</x-filament-widgets::widget>
"""

path = '/home/buja/projetos/barbearia-api/resources/views/filament/widgets/calendar-widget.blade.php'
with open(path, 'w') as f:
    f.write(content)

import os
lines = content.count('\n')
print(f'Written: {lines} lines, {os.path.getsize(path)} bytes')
