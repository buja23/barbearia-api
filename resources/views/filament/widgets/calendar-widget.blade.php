<x-filament-widgets::widget>
    {{-- Carrega FullCalendar --}}
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

    <x-filament::section class="!p-0 !rounded-[2rem] shadow-2xl overflow-hidden border-0 ring-1 ring-gray-100 dark:ring-gray-800">
        
        <div 
            wire:ignore 
            x-data="calendarWidget(@this, {{ json_encode($this->getCalendarEvents()) }})" 
            @limpar-calendario.window="limparVisual()"
            class="relative bg-white dark:bg-gray-900 p-6 md:p-8"
        >
            {{-- Header Clean --}}
            <div class="flex items-center justify-between mb-6 px-2">
                <h2 id="calendar-title" class="text-2xl font-black text-gray-900 dark:text-white tracking-tight capitalize font-sans">
                    {{-- JS preenche aqui --}}
                </h2>
                <div class="flex gap-2 bg-gray-100 dark:bg-gray-800 p-1 rounded-full">
                    <button @click="calendar.prev()" class="p-2 rounded-full hover:bg-white dark:hover:bg-gray-700 shadow-sm transition-all text-gray-600 dark:text-gray-300">
                        <x-filament::icon icon="heroicon-m-chevron-left" class="h-5 w-5" />
                    </button>
                    <button @click="calendar.today()" class="px-3 py-1 text-xs font-bold uppercase rounded-full hover:bg-white dark:hover:bg-gray-700 shadow-sm transition-all text-gray-600 dark:text-gray-300">
                        Hoje
                    </button>
                    <button @click="calendar.next()" class="p-2 rounded-full hover:bg-white dark:hover:bg-gray-700 shadow-sm transition-all text-gray-600 dark:text-gray-300">
                        <x-filament::icon icon="heroicon-m-chevron-right" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            {{-- Calendário --}}
            <div id="calendar" class="calendar-senior-theme min-h-[400px]"></div>

            {{-- Legenda (Status Balls) --}}
            <div class="mt-8 flex items-center justify-center gap-6 border-t border-dashed border-gray-100 dark:border-gray-800 pt-6">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
                    <span class="w-3 h-3 rounded-full bg-blue-100 border border-blue-500"></span> Livre
                </div>
                <div class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
                    <span class="w-3 h-3 rounded-full bg-orange-100 border border-orange-500"></span> Médio
                </div>
                <div class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
                    <span class="w-3 h-3 rounded-full bg-red-100 border border-red-500"></span> Lotado
                </div>
            </div>

            {{-- Loading State --}}
            <div 
                wire:loading.flex 
                wire:target="selectDate" 
                class="absolute inset-0 z-50 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] transition-all rounded-[2rem]"
            >
                <x-filament::loading-indicator class="h-10 w-10 text-blue-600" />
            </div>
        </div>

        {{-- Lógica JavaScript --}}
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
                            headerToolbar: false, // Esconde toolbar padrão
                            dayHeaderFormat: { weekday: 'short' },
                            fixedWeekCount: false,
                            showNonCurrentDates: false,
                            contentHeight: 'auto',
                            
                            // Atualiza Título
                            datesSet: (info) => {
                                document.getElementById('calendar-title').innerText = info.view.title;
                            },

                            // Clique na Data
                            dateClick: (info) => {
                                this.limparVisual();
                                
                                // Adiciona classe de seleção
                                let dayFrame = info.dayEl.querySelector('.fc-daygrid-day-frame');
                                if(dayFrame) dayFrame.classList.add('dia-selecionado');
                                
                                wire.selectDate(info.dateStr);
                            }
                        });
                        this.calendar.render();
                    },

                    // Função para limpar seleção visual
                    limparVisual() {
                        document.querySelectorAll('.dia-selecionado').forEach(el => el.classList.remove('dia-selecionado'));
                    }
                }));
            });
        </script>

        <style>
            /* === CSS SENIOR V6 (Layout Bolas Flutuantes) === */
            .fc-theme-standard td, .fc-theme-standard th { border: none !important; }
            .fc-theme-standard .fc-scrollgrid { border: none !important; }

            .calendar-senior-theme { font-family: 'Inter', sans-serif; }

            /* Dias da Semana */
            .fc-col-header-cell-cushion {
                color: #9ca3af; font-size: 0.75rem; font-weight: 700; 
                text-transform: uppercase; padding-bottom: 24px !important;
                text-decoration: none !important;
            }

            /* Container do Dia */
            .fc-daygrid-day-frame {
                height: 50px !important; width: 50px !important; margin: 0 auto 4px auto;
                display: flex; justify-content: center; align-items: center;
                cursor: pointer; border-radius: 50%;
                transition: transform 0.1s ease;
                position: relative; /* Importante para o posicionamento absoluto dentro dele */
            }
            
            /* Hover Effect */
            .fc-daygrid-day-frame:hover {
                transform: scale(1.15);
                background-color: #f3f4f6;
            }
            .dark .fc-daygrid-day-frame:hover { background-color: #374151; }

            /* === NÚMERO (A Mágica do Z-Index) === */
            .fc-daygrid-day-number {
                font-size: 0.95rem; font-weight: 600; color: #4b5563;
                z-index: 20; position: relative; pointer-events: none;
                text-decoration: none !important;
            }
            .dark .fc-daygrid-day-number { color: #e5e7eb; }

            /* === HOJE (Adeus Amarelo Feio, Olá Azul Apple) === */
            .fc-day-today .fc-daygrid-day-frame {
                background-color: #3b82f6 !important; /* Azul Real */
                box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4);
            }
            .fc-day-today .fc-daygrid-day-number {
                color: white !important; font-weight: 800;
            }
            /* Remove background padrão */
            .fc-day-today { background: transparent !important; }
            
            /* === BOLAS DE STATUS (Background Events) === */
            .fc-bg-event {
                opacity: 1 !important;
                border-radius: 50%;
                width: 42px !important; height: 42px !important;
                left: 50% !important; top: 50% !important;
                transform: translate(-50%, -50%) !important;
                z-index: 10 !important; /* Fica atrás do número */
                pointer-events: none;
            }

            /* === SELECIONADO (Anel de Foco) === */
            .dia-selecionado {
                box-shadow: 0 0 0 3px white, 0 0 0 5px #3b82f6 !important; /* Anel Duplo */
                z-index: 30; /* Fica na frente de tudo */
            }
            .dark .dia-selecionado {
                 box-shadow: 0 0 0 3px #111827, 0 0 0 5px #3b82f6 !important;
            }
            
            /* Se hoje for selecionado, mantém o azul mas adiciona o anel */
            .fc-day-today .dia-selecionado {
                background-color: #2563eb !important;
            }
        </style>
    </x-filament::section>
</x-filament-widgets::widget>