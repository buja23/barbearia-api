import os

blade = r"""<x-filament-widgets::widget>
    <x-filament::section class="overflow-hidden">

        <div
            wire:ignore
            x-data="calendarWidget(@this, {{ json_encode($this->getCalendarEvents()) }})"
            class="relative select-none"
        >
            {{-- ── Header ─────────────────────────────────────────── --}}
            <div class="flex items-center justify-between mb-6">

                {{-- Título do mês --}}
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500 mb-0.5">
                        Calendário
                    </p>
                    <h2
                        id="calendar-title"
                        class="text-2xl font-bold text-gray-900 dark:text-white capitalize leading-none"
                    ></h2>
                </div>

                {{-- Navegação --}}
                <div class="flex items-center gap-0.5 bg-gray-100 dark:bg-white/5 rounded-xl p-1">
                    <button
                        @click="calendar.prev()"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-white/10 hover:text-gray-900 dark:hover:text-white transition-all"
                        title="Mês anterior"
                    >
                        <x-filament::icon icon="heroicon-s-chevron-left" class="w-4 h-4" />
                    </button>
                    <button
                        @click="calendar.today()"
                        class="px-3 h-8 text-[11px] font-bold rounded-lg text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-white/10 hover:text-gray-900 dark:hover:text-white uppercase tracking-widest transition-all"
                    >
                        Hoje
                    </button>
                    <button
                        @click="calendar.next()"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-white/10 hover:text-gray-900 dark:hover:text-white transition-all"
                        title="Próximo mês"
                    >
                        <x-filament::icon icon="heroicon-s-chevron-right" class="w-4 h-4" />
                    </button>
                </div>
            </div>

            {{-- ── Grade ──────────────────────────────────────────── --}}
            <div id="calendar" class="cal-modern"></div>

            {{-- ── Legenda ─────────────────────────────────────────── --}}
            <div class="flex items-center gap-5 mt-5 pt-4 border-t border-gray-100 dark:border-white/5">
                <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-gray-600">
                    Lotação
                </span>
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Tranquilo</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-orange-400"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Médio</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Lotado</span>
                </div>
            </div>

            {{-- ── Loading overlay ─────────────────────────────────── --}}
            <div
                wire:loading.flex
                wire:target="selectDate"
                class="absolute inset-0 z-50 flex items-center justify-center bg-white/60 dark:bg-gray-900/60 backdrop-blur-sm rounded-xl"
            >
                <x-filament::loading-indicator class="h-8 w-8 text-violet-500" />
            </div>
        </div>

        {{-- ── Alpine component ───────────────────────────────────── --}}
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('calendarWidget', (wire, allEvents) => ({
                    calendar: null,

                    init() {
                        const calendarEl = document.getElementById('calendar');
                        const titleEl    = document.getElementById('calendar-title');

                        this.calendar = new FullCalendar.Calendar(calendarEl, {
                            initialView: 'dayGridMonth',
                            locale: 'pt-br',
                            events: allEvents,
                            headerToolbar: false,
                            dayHeaderFormat: { weekday: 'narrow' },
                            fixedWeekCount: false,
                            showNonCurrentDates: false,
                            contentHeight: 'auto',

                            dayCellContent: (arg) => ({
                                html: `<div class="cal-day-inner">${arg.dayNumberText}</div>`
                            }),

                            datesSet: (info) => {
                                titleEl.innerText = info.view.title;
                            },

                            eventDidMount: (info) => {
                                if (info.event.display !== 'background') return;
                                const count = info.event.extendedProps?.count;
                                if (!count) return;
                                const dayCell = info.el.closest('.fc-daygrid-day');
                                if (!dayCell || dayCell.querySelector('.appt-dot')) return;
                                const frame = dayCell.querySelector('.fc-daygrid-day-frame');
                                if (!frame) return;
                                const dot = document.createElement('span');
                                dot.className = 'appt-dot';
                                dot.style.background = info.event.backgroundColor;
                                frame.appendChild(dot);
                            },

                            dateClick: (info) => {
                                document.querySelectorAll('.dia-selecionado')
                                    .forEach(el => el.classList.remove('dia-selecionado'));
                                const frame = info.dayEl.querySelector('.fc-daygrid-day-frame');
                                if (frame) frame.classList.add('dia-selecionado');
                                wire.selectDate(info.dateStr);
                            }
                        });

                        this.calendar.render();
                    }
                }));
            });
        </script>

        {{-- ── Estilos ─────────────────────────────────────────────── --}}
        <style>
            /* Reset estrutura FullCalendar */
            .cal-modern .fc-scrollgrid,
            .cal-modern .fc-theme-standard td,
            .cal-modern .fc-theme-standard th,
            .cal-modern .fc-scrollgrid-section > * {
                border: none !important;
                background: transparent !important;
            }
            .cal-modern table { border-collapse: separate; border-spacing: 0 2px; }

            /* Cabeçalho dias da semana */
            .cal-modern .fc-col-header-cell { padding: 0 0 10px; }
            .cal-modern .fc-col-header-cell-cushion {
                display: block;
                text-align: center;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #94a3b8;
                text-decoration: none !important;
                padding: 0 !important;
            }
            .dark .cal-modern .fc-col-header-cell-cushion { color: #475569; }

            /* Linha separadora abaixo do header */
            .cal-modern .fc-col-header {
                border-bottom: 1px solid #f1f5f9;
                padding-bottom: 0;
            }
            .dark .cal-modern .fc-col-header { border-bottom-color: rgba(255,255,255,0.05); }

            /* Célula diária */
            .cal-modern .fc-daygrid-day { padding: 3px 2px !important; }
            .cal-modern .fc-daygrid-day-frame {
                position: relative;
                width: 40px;
                height: 40px;
                margin: 0 auto;
                display: flex !important;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                border-radius: 50%;
                transition: background 0.15s, transform 0.12s;
            }
            .cal-modern .fc-daygrid-day-frame:hover {
                background: #f1f5f9;
                transform: scale(1.08);
            }
            .dark .cal-modern .fc-daygrid-day-frame:hover { background: rgba(255,255,255,0.06); }

            /* Número do dia */
            .cal-modern .fc-daygrid-day-number { display: none; } /* esconde o original */
            .cal-day-inner {
                font-size: 13px;
                font-weight: 500;
                color: #374151;
                line-height: 1;
                pointer-events: none;
                z-index: 5;
            }
            .dark .cal-day-inner { color: #cbd5e1; }

            /* Hoje */
            .cal-modern .fc-day-today { background: transparent !important; }
            .cal-modern .fc-day-today .fc-daygrid-day-frame {
                background: #7c3aed !important;
                box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4);
            }
            .cal-modern .fc-day-today .fc-daygrid-day-frame:hover {
                background: #6d28d9 !important;
                transform: scale(1.08);
            }
            .cal-modern .fc-day-today .cal-day-inner {
                color: #fff !important;
                font-weight: 700;
            }
            .cal-modern .fc-day-today .appt-dot { background: rgba(255,255,255,0.7) !important; }

            /* Fundo do evento heatmap — invisível (substituído pelo dot) */
            .cal-modern .fc-bg-event { opacity: 0 !important; }

            /* Dot de agendamento */
            .appt-dot {
                position: absolute;
                bottom: 5px;
                left: 50%;
                transform: translateX(-50%);
                width: 4px;
                height: 4px;
                border-radius: 50%;
                pointer-events: none;
                z-index: 10;
            }

            /* Dia selecionado */
            .cal-modern .dia-selecionado {
                outline: 2px solid #7c3aed;
                outline-offset: 2px;
            }

            /* Largura total */
            .cal-modern .fc-daygrid-body,
            .cal-modern .fc-scrollgrid-sync-table { width: 100% !important; }

            /* Remove top events area (text events) */
            .cal-modern .fc-daygrid-day-events { display: none !important; }

            /* Scroll desnecessário */
            .cal-modern .fc-scroller { overflow: hidden !important; }
        </style>

    </x-filament::section>
</x-filament-widgets::widget>
"""

path = '/home/buja/projetos/barbearia-api/resources/views/filament/widgets/calendar-widget.blade.php'
with open(path, 'w', encoding='utf-8') as f:
    f.write(blade)

import os
lines = blade.count('\n')
size  = os.path.getsize(path)
print(f'OK: {lines} lines, {size} bytes')
