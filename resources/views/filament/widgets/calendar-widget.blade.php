<x-filament-widgets::widget>
    {{-- Carrega FullCalendar --}}
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

    {{-- CONTAINER PRINCIPAL (Sem bordas, sem fundo branco padrão do filament) --}}
    <x-filament::section class="!p-0 !rounded-3xl !shadow-none !border-0 !ring-0 overflow-hidden bg-transparent">
        
        <div 
            wire:ignore 
            x-data="calendarWidget(@this, {{ json_encode($this->getCalendarEvents()) }})" 
            @limpar-calendario.window="limparVisual()"
            class="relative bg-white dark:bg-gray-950 p-8 md:p-10" 
        >
            {{-- HEADER: Título Hero --}}
            <div class="flex items-center justify-between mb-[30px]">
                
                {{-- TÍTULO AUMENTADO AQUI 👇 --}}
                <h2 id="calendar-title" class="text-5xl md:text-6xl font-black text-gray-900 dark:text-white tracking-tighter capitalize font-sans">
                    {{-- JS preenche aqui (Ex: Fevereiro de 2026) --}}
                </h2>

                {{-- Navegação --}}
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

            {{-- CALENDÁRIO --}}
            <div id="calendar" class="calendar-pro-theme min-h-[550px]"></div>

            {{-- Loading Overlay --}}
            <div 
                wire:loading.flex 
                wire:target="selectDate" 
                class="absolute inset-0 z-50 flex items-center justify-center bg-white/60 dark:bg-gray-950/60 backdrop-blur-sm transition-all rounded-3xl"
            >
                <div class="flex flex-col items-center gap-3">
                    <x-filament::loading-indicator class="h-10 w-10 text-indigo-600" />
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('calendarWidget', (wire, initialEvents) => ({
                    calendar: null,
                    init() {
                        let calendarEl = document.getElementById('calendar');
                        this.calendar = new FullCalendar.Calendar(calendarEl, {
                            initialView: 'dayGridMonth',
                            locale: 'pt-br',
                            events: initialEvents,
                            headerToolbar: false,
                            dayHeaderFormat: { weekday: 'narrow' }, // S, T, Q...
                            fixedWeekCount: false,
                            showNonCurrentDates: false,
                            contentHeight: 'auto',
                            
                            // Customização do conteúdo da célula
                            dayCellContent: (arg) => {
                                return { html: `<div class="day-content">${arg.dayNumberText}</div>` };
                            },
                            
                            datesSet: (info) => {
                                document.getElementById('calendar-title').innerText = info.view.title;
                            },

                            dateClick: (info) => {
                                this.limparVisual();
                                let dayFrame = info.dayEl.querySelector('.fc-daygrid-day-frame');
                                if(dayFrame) dayFrame.classList.add('dia-selecionado');
                                wire.selectDate(info.dateStr);
                            }
                        });
                        this.calendar.render();
                    },

                    limparVisual() {
                        document.querySelectorAll('.dia-selecionado').forEach(el => el.classList.remove('dia-selecionado'));
                    }
                }));
            });
        </script>

        <style>
            .calendar-pro-theme { font-family: 'Inter', sans-serif; }

            /* Reset Total de Bordas */
            .fc-theme-standard td, 
            .fc-theme-standard th, 
            .fc-theme-standard .fc-scrollgrid,
            .fc-scrollgrid-section-header > *,
            .fc-scrollgrid-section-body > * { 
                border: none !important; 
                background: transparent !important; 
            }

            /* Dias da Semana */
            .fc-col-header-cell-cushion {
                color: #64748b; 
                font-size: 0.8rem; 
                font-weight: 700; 
                text-transform: uppercase;
                letter-spacing: 0.1em; 
                padding-bottom: 30px !important; 
                text-decoration: none !important;
                opacity: 0.5;
            }
            .dark .fc-col-header-cell-cushion { color: #9ca3af; }

            /* Célula do Dia */
            .fc-daygrid-day-frame {
                height: 56px !important; width: 56px !important; 
                margin: 0 auto 12px auto; 
                display: flex; justify-content: center; align-items: center;
                cursor: pointer; 
                border-radius: 18px; 
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .fc-daygrid-day-frame:hover {
                background-color: #f3f4f6;
                transform: scale(1.15);
            }
            .dark .fc-daygrid-day-frame:hover { background-color: #1f2937; }

            /* Número */
            .fc-daygrid-day-number {
                font-size: 1.1rem; 
                font-weight: 500; 
                color: #374151;
                z-index: 20; text-decoration: none !important;
            }
            .dark .fc-daygrid-day-number { color: #e2e8f0; }

            /* Hoje */
            .fc-day-today .fc-daygrid-day-frame {
                background: #4f46e5 !important;
                box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
            }
            .fc-day-today .fc-daygrid-day-number { color: white !important; font-weight: 700; }
            .fc-day-today { background: transparent !important; }

            /* Indicador de Agendamento */
            .fc-bg-event {
                opacity: 0.2 !important;
                border-radius: 18px;
                transform: scale(0.9); 
            }

            /* Seleção */
            .dia-selecionado {
                box-shadow: 0 0 0 3px #030712, 0 0 0 5px #4f46e5 !important;
                z-index: 30;
                transform: scale(1.05);
            }
        </style>
    </x-filament::section>
</x-filament-widgets::widget>