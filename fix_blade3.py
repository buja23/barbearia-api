import os

blade = r"""<x-filament-widgets::widget>
    <x-filament::section class="!p-0 !rounded-3xl !shadow-none !border-0 !ring-0 overflow-hidden bg-transparent">

        <div class="relative bg-white dark:bg-gray-950 p-6 md:p-8 rounded-3xl">

            {{-- Header: título + navegação --}}
            <div class="flex items-center justify-between mb-6">
                <h2 id="barbearia-cal-title-{{ $this->getId() }}"
                    class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white tracking-tighter capitalize">
                </h2>
                <div class="flex items-center gap-1">
                    <button id="barbearia-cal-prev-{{ $this->getId() }}"
                        class="p-2.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                        type="button" title="Mês anterior">
                        <x-filament::icon icon="heroicon-m-chevron-left" class="h-5 w-5" />
                    </button>
                    <button id="barbearia-cal-today-{{ $this->getId() }}"
                        class="px-3 py-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white uppercase tracking-widest transition-colors"
                        type="button">
                        Hoje
                    </button>
                    <button id="barbearia-cal-next-{{ $this->getId() }}"
                        class="p-2.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                        type="button" title="Próximo mês">
                        <x-filament::icon icon="heroicon-m-chevron-right" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            {{-- Grade do calendário — wire:ignore preserva o FullCalendar ao re-render --}}
            <div wire:ignore>
                <div id="barbearia-cal-{{ $this->getId() }}" class="barbcal"></div>
            </div>

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

            {{-- Overlay loading --}}
            <div wire:loading.flex wire:target="selectDate"
                class="absolute inset-0 z-50 items-center justify-center bg-white/70 dark:bg-gray-950/70 backdrop-blur-sm rounded-3xl">
                <x-filament::loading-indicator class="h-10 w-10 text-indigo-600" />
            </div>
        </div>

        {{-- Inicialização do FullCalendar — sem Alpine, sem timing issues --}}
        <script data-navigate-once>
        (function () {
            var CID    = '{{ $this->getId() }}';
            var EVENTS = {!! json_encode($this->getCalendarEvents()) !!};

            function init() {
                /* Aguarda FullCalendar e os elementos do DOM */
                if (typeof FullCalendar === 'undefined') {
                    requestAnimationFrame(init);
                    return;
                }

                var el      = document.getElementById('barbearia-cal-'       + CID);
                var titleEl = document.getElementById('barbearia-cal-title-'  + CID);
                var prevBtn = document.getElementById('barbearia-cal-prev-'   + CID);
                var todayBtn= document.getElementById('barbearia-cal-today-'  + CID);
                var nextBtn = document.getElementById('barbearia-cal-next-'   + CID);

                if (!el) { requestAnimationFrame(init); return; }

                var cal = new FullCalendar.Calendar(el, {
                    initialView:         'dayGridMonth',
                    locale:              'pt-br',
                    events:              EVENTS,
                    headerToolbar:       false,
                    dayHeaderFormat:     { weekday: 'narrow' },
                    fixedWeekCount:      false,
                    showNonCurrentDates: false,
                    contentHeight:       'auto',

                    dayCellContent: function (arg) {
                        return { html: '<span class="fc-day-num">' + arg.dayNumberText + '</span>' };
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
                        var badge = document.createElement('span');
                        badge.className = 'appt-badge';
                        badge.textContent = count;
                        frame.appendChild(badge);
                    },

                    dateClick: function (info) {
                        document.querySelectorAll('.dia-sel').forEach(function (e) {
                            e.classList.remove('dia-sel');
                        });
                        var frame = info.dayEl.querySelector('.fc-daygrid-day-frame');
                        if (frame) frame.classList.add('dia-sel');

                        /* Chama o método Livewire sem Alpine */
                        var lw = Livewire.find(CID);
                        if (lw) lw.selectDate(info.dateStr);
                    }
                });

                cal.render();

                /* Botões de navegação */
                if (prevBtn)  prevBtn.onclick  = function () { cal.prev(); };
                if (todayBtn) todayBtn.onclick  = function () { cal.today(); };
                if (nextBtn)  nextBtn.onclick   = function () { cal.next(); };
            }

            /* Inicia assim que possível */
            init();
        })();
        </script>

        <style>
            /* Reset geral */
            .barbcal .fc-scrollgrid,
            .barbcal .fc-theme-standard td,
            .barbcal .fc-theme-standard th,
            .barbcal .fc-scrollgrid-section > * {
                border: none !important;
                background: transparent !important;
            }

            /* Cabeçalho dias da semana */
            .barbcal .fc-col-header-cell-cushion {
                color: #94a3b8;
                font-size: 0.7rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                padding-bottom: 14px !important;
                text-decoration: none !important;
            }
            .dark .barbcal .fc-col-header-cell-cushion { color: #4b5563; }

            /* Células dos dias */
            .barbcal .fc-daygrid-day-frame {
                position: relative;
                min-height: 50px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 auto 8px auto;
                width: 50px;
                cursor: pointer;
                border-radius: 14px;
                transition: background 0.15s, box-shadow 0.15s;
            }
            .barbcal .fc-daygrid-day-frame:hover {
                background: #f1f5f9 !important;
            }
            .dark .barbcal .fc-daygrid-day-frame:hover {
                background: #1e293b !important;
            }

            /* Número do dia */
            .barbcal .fc-daygrid-day-number,
            .barbcal .fc-day-num {
                font-size: 0.88rem;
                font-weight: 500;
                color: #374151;
                text-decoration: none !important;
                z-index: 5;
                padding: 0 !important;
                line-height: 1;
            }
            .dark .barbcal .fc-daygrid-day-number,
            .dark .barbcal .fc-day-num { color: #9ca3af; }

            /* Hoje */
            .barbcal .fc-day-today { background: transparent !important; }
            .barbcal .fc-day-today .fc-daygrid-day-frame {
                background: #4f46e5 !important;
                box-shadow: 0 4px 16px rgba(79,70,229,0.35);
            }
            .barbcal .fc-day-today .fc-daygrid-day-number,
            .barbcal .fc-day-today .fc-day-num {
                color: #fff !important;
                font-weight: 800;
            }
            .barbcal .fc-day-today .appt-badge { color: rgba(255,255,255,0.9); }

            /* Eventos de fundo (heatmap) */
            .barbcal .fc-bg-event {
                opacity: 0.2 !important;
                border-radius: 14px !important;
            }

            /* Badge contagem */
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
            .barbcal .dia-sel {
                box-shadow: 0 0 0 2px #0f0f1a, 0 0 0 4px #6366f1 !important;
            }
            .dark .barbcal .dia-sel {
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

lines = blade.count('\n')
size  = os.path.getsize(path)
print(f'OK: {lines} lines, {size} bytes')
