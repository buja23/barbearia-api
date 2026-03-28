blade = r"""<x-filament-widgets::widget>
    <x-filament::section class="!p-0 !rounded-3xl !shadow-none !border-0 !ring-0 overflow-hidden bg-transparent">

        {{-- Script ANTES do wire:ignore para garantir que a função exista quando Alpine processar x-data --}}
        <script>
            if (!window.calendarWidget) {
                window.calendarWidget = function (wire, allEvents) {
                    return {
                        calendar: null,

                        init: function () {
                            var self = this;

                            function build() {
                                var el      = document.getElementById('barbearia-calendar');
                                var titleEl = document.getElementById('barbearia-calendar-title');
                                if (!el) return;

                                self.calendar = new FullCalendar.Calendar(el, {
                                    initialView:       'dayGridMonth',
                                    locale:            'pt-br',
                                    events:            allEvents,
                                    headerToolbar:     false,
                                    dayHeaderFormat:   { weekday: 'narrow' },
                                    fixedWeekCount:    false,
                                    showNonCurrentDates: false,
                                    contentHeight:     'auto',

                                    dayCellContent: function (arg) {
                                        return { html: '<div class="day-num">' + arg.dayNumberText + '</div>' };
                                    },

                                    datesSet: function (info) {
                                        if (titleEl) titleEl.textContent = info.view.title;
                                    },

                                    eventDidMount: function (info) {
                                        if (info.event.display !== 'background') return;
                                        var count = info.event.extendedProps && info.event.extendedProps.count;
                                        if (!count) return;
                                        var dayCell = info.el.closest('.fc-daygrid-day');
                                        if (!dayCell || dayCell.querySelector('.appt-badge')) return;
                                        var frame = dayCell.querySelector('.fc-daygrid-day-frame');
                                        if (!frame) return;
                                        var badge = document.createElement('div');
                                        badge.className = 'appt-badge';
                                        badge.textContent = count;
                                        frame.appendChild(badge);
                                    },

                                    dateClick: function (info) {
                                        document.querySelectorAll('.dia-selecionado').forEach(function (el) {
                                            el.classList.remove('dia-selecionado');
                                        });
                                        var frame = info.dayEl.querySelector('.fc-daygrid-day-frame');
                                        if (frame) frame.classList.add('dia-selecionado');
                                        wire.selectDate(info.dateStr);
                                    }
                                });

                                self.calendar.render();
                            }

                            if (typeof FullCalendar !== 'undefined') {
                                build();
                            } else {
                                /* FullCalendar ainda carregando (CDN async) — aguarda window.load */
                                window.addEventListener('load', build, { once: true });
                            }
                        }
                    };
                };
            }
        </script>

        <div
            wire:ignore
            x-data="calendarWidget(@this, {{ json_encode($this->getCalendarEvents()) }})"
            class="relative bg-white dark:bg-gray-950 p-8 md:p-10"
        >
            {{-- Header: título + navegação --}}
            <div class="flex items-center justify-between mb-8">
                <h2 id="barbearia-calendar-title"
                    class="text-4xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tighter capitalize"></h2>

                <div class="flex items-center gap-1">
                    <button @click="calendar.prev()"
                        class="p-3 rounded-2xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                        title="Mês anterior">
                        <x-filament::icon icon="heroicon-m-chevron-left" class="h-5 w-5" />
                    </button>
                    <button @click="calendar.today()"
                        class="px-4 py-2 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white uppercase tracking-widest transition-colors">
                        Hoje
                    </button>
                    <button @click="calendar.next()"
                        class="p-3 rounded-2xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                        title="Próximo mês">
                        <x-filament::icon icon="heroicon-m-chevron-right" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            {{-- Calendário --}}
            <div id="barbearia-calendar" class="barbcal"></div>

            {{-- Legenda --}}
            <div class="flex items-center gap-4 mt-5 px-1">
                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Lotação</span>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block w-2 h-2 rounded-full" style="background:#3b82f6"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Tranquilo</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block w-2 h-2 rounded-full" style="background:#f97316"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Médio</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block w-2 h-2 rounded-full" style="background:#ef4444"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Lotado</span>
                </div>
            </div>

            {{-- Overlay de loading ao clicar no dia --}}
            <div wire:loading.flex wire:target="selectDate"
                class="absolute inset-0 z-50 flex items-center justify-center bg-white/70 dark:bg-gray-950/70 backdrop-blur-sm rounded-3xl">
                <x-filament::loading-indicator class="h-10 w-10 text-indigo-600" />
            </div>
        </div>

        <style>
            /* Reset de bordas/fundos padrão do FC */
            .barbcal .fc-scrollgrid,
            .barbcal .fc-theme-standard td,
            .barbcal .fc-theme-standard th,
            .barbcal .fc-scrollgrid-section > * {
                border: none !important;
                background: transparent !important;
            }

            /* Cabeçalho dos dias da semana */
            .barbcal .fc-col-header-cell-cushion {
                color: #94a3b8;
                font-size: 0.72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                padding-bottom: 16px !important;
                text-decoration: none !important;
            }
            .dark .barbcal .fc-col-header-cell-cushion { color: #4b5563; }

            /* Célula de cada dia */
            .barbcal .fc-daygrid-day-frame {
                position: relative;
                min-height: 52px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 auto 6px auto;
                width: 52px;
                cursor: pointer;
                border-radius: 14px;
                transition: background 0.15s, transform 0.15s;
            }
            .barbcal .fc-daygrid-day-frame:hover {
                background: #f1f5f9 !important;
            }
            .dark .barbcal .fc-daygrid-day-frame:hover {
                background: #1e293b !important;
            }

            /* Número do dia */
            .barbcal .fc-daygrid-day-number {
                font-size: 0.9rem;
                font-weight: 500;
                color: #374151;
                text-decoration: none !important;
                z-index: 5;
                padding: 0 !important;
            }
            .dark .barbcal .fc-daygrid-day-number { color: #9ca3af; }

            /* Dia de hoje */
            .barbcal .fc-day-today { background: transparent !important; }
            .barbcal .fc-day-today .fc-daygrid-day-frame {
                background: #4f46e5 !important;
                box-shadow: 0 4px 18px rgba(79,70,229,0.4);
            }
            .barbcal .fc-day-today .fc-daygrid-day-number {
                color: #fff !important;
                font-weight: 800;
            }
            .barbcal .fc-day-today .appt-badge { color: rgba(255,255,255,0.9); }

            /* Eventos de fundo (heatmap) */
            .barbcal .fc-bg-event {
                opacity: 0.18 !important;
                border-radius: 12px !important;
            }

            /* Badge de contagem */
            .appt-badge {
                position: absolute;
                bottom: 4px;
                right: 5px;
                font-size: 0.55rem;
                font-weight: 800;
                color: #6366f1;
                line-height: 1;
                pointer-events: none;
                z-index: 10;
            }
            .dark .appt-badge { color: #818cf8; }

            /* Dia selecionado */
            .barbcal .dia-selecionado {
                box-shadow: 0 0 0 2px #1e1b4b, 0 0 0 4px #6366f1 !important;
                z-index: 10;
            }
            .dark .barbcal .dia-selecionado {
                box-shadow: 0 0 0 2px #030712, 0 0 0 4px #6366f1 !important;
            }

            /* Largura total */
            .barbcal .fc-daygrid-body,
            .barbcal .fc-scrollgrid-sync-table { width: 100% !important; }
        </style>

    </x-filament::section>
</x-filament-widgets::widget>
"""

path = '/home/buja/projetos/barbearia-api/resources/views/filament/widgets/calendar-widget.blade.php'
with open(path, 'w', encoding='utf-8') as f:
    f.write(blade)

import os
size = os.path.getsize(path)
lines = blade.count('\n')
print(f'OK: {lines} lines, {size} bytes')
