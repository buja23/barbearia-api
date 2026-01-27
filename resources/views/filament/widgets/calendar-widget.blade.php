<x-filament-widgets::widget>
    {{-- Carrega FullCalendar --}}
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

    <x-filament::section class="!p-0 !rounded-[2.5rem] shadow-2xl overflow-hidden border-0 ring-1 ring-gray-100 dark:ring-gray-800">
        
        <div 
            x-data="calendarWidget(@this, {{ json_encode($this->getCalendarEvents()) }})" 
            class="relative bg-white dark:bg-gray-900 p-8"
        >
            {{-- Header Moderno --}}
            <div class="flex items-center justify-between mb-8 px-2">
                <div>
                    <h2 id="calendar-title" class="text-3xl font-black text-gray-900 dark:text-white tracking-tighter capitalize font-sans leading-none">
                        {{-- JS preenche aqui --}}
                    </h2>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Calendário de Agendamentos</p>
                </div>

                <div class="flex gap-2 bg-gray-50 dark:bg-gray-800 p-1.5 rounded-full border border-gray-100 dark:border-gray-700">
                    <button @click="calendar.prev()" class="p-3 rounded-full hover:bg-white dark:hover:bg-gray-700 shadow-sm transition-all text-gray-500 dark:text-gray-400 hover:text-primary-600 active:scale-90">
                        <x-filament::icon icon="heroicon-m-chevron-left" class="h-5 w-5" />
                    </button>
                    <button @click="calendar.next()" class="p-3 rounded-full hover:bg-white dark:hover:bg-gray-700 shadow-sm transition-all text-gray-500 dark:text-gray-400 hover:text-primary-600 active:scale-90">
                        <x-filament::icon icon="heroicon-m-chevron-right" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            {{-- Calendário --}}
            <div id="calendar" wire:ignore class="calendar-modern-theme"></div>

            {{-- Legenda Vibrante --}}
            <div class="mt-10 flex flex-wrap justify-center gap-4 border-t border-dashed border-gray-100 dark:border-gray-800 pt-8">
                <div class="px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-600 text-[10px] font-bold uppercase tracking-wide flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span> Tranquilo
                </div>
                <div class="px-3 py-1.5 rounded-full bg-orange-50 border border-orange-100 text-orange-600 text-[10px] font-bold uppercase tracking-wide flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span> Médio
                </div>
                <div class="px-3 py-1.5 rounded-full bg-rose-50 border border-rose-100 text-rose-600 text-[10px] font-bold uppercase tracking-wide flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span> Lotado
                </div>
            </div>

            {{-- Loading --}}
            <div wire:loading.flex wire:target="selectDate" class="absolute inset-0 z-50 flex items-center justify-center bg-white/60 dark:bg-gray-900/60 backdrop-blur-[2px] transition-all rounded-[2.5rem]">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-full shadow-2xl">
                    <x-filament::loading-indicator class="h-8 w-8 text-primary-600" />
                </div>
            </div>
        </div>

        {{-- Script (Mantido Igual) --}}
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
                            dayHeaderFormat: { weekday: 'short' },
                            fixedWeekCount: false,
                            showNonCurrentDates: false,
                            contentHeight: 'auto',
                            datesSet: (info) => {
                                document.getElementById('calendar-title').innerText = info.view.title;
                            },
                            dateClick: (info) => {
                                document.querySelectorAll('.dia-selecionado').forEach(el => el.classList.remove('dia-selecionado'));
                                let dayFrame = info.dayEl.querySelector('.fc-daygrid-day-frame');
                                if(dayFrame) dayFrame.classList.add('dia-selecionado');
                                wire.selectDate(info.dateStr);
                            }
                        });
                        this.calendar.render();
                    }
                }));
            });
        </script>

        <style>
            .calendar-modern-theme { font-family: 'Inter', sans-serif; }

            /* Cabeçalho dos Dias (DOM, SEG...) */
            .fc-theme-standard td, .fc-theme-standard th { border: none !important; }
            .fc-col-header-cell-cushion { 
                color: #cbd5e1; 
                font-size: 0.7rem; 
                font-weight: 800; 
                letter-spacing: 0.1em;
                text-transform: uppercase; 
                padding-bottom: 24px !important; 
                text-decoration: none !important; 
            }

            /* Container do Dia */
            .fc-daygrid-day-frame { 
                height: 52px !important; 
                width: 52px !important; 
                margin: 0 auto 8px auto; 
                display: flex; 
                justify-content: center; 
                align-items: center; 
                cursor: pointer; 
                border-radius: 18px; /* Mais quadrado, estilo app iOS */
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative; 
            }
            .fc-daygrid-day-frame:hover { 
                transform: translateY(-2px) scale(1.05); 
                background-color: #f8fafc; 
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .dark .fc-daygrid-day-frame:hover { background-color: #1e293b; }

            /* Número */
            .fc-daygrid-day-number { 
                font-size: 1rem; 
                font-weight: 700; 
                color: #475569; 
                z-index: 20; 
                position: relative; 
                pointer-events: none; 
                text-decoration: none !important; 
            }
            .dark .fc-daygrid-day-number { color: #f1f5f9; }

            /* Hoje */
            .fc-day-today .fc-daygrid-day-frame { 
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important; 
                box-shadow: 0 8px 16px rgba(37, 99, 235, 0.25);
            }
            .fc-day-today .fc-daygrid-day-number { color: white !important; font-weight: 800; }

            /* Bolas de Fundo (Cores Vibrantes com Transparência) */
            .fc-bg-event { 
                opacity: 1 !important; 
                border-radius: 16px; /* Acompanha o formato do dia */
                width: 44px !important; 
                height: 44px !important; 
                left: 50% !important; 
                top: 50% !important; 
                transform: translate(-50%, -50%) !important; 
                z-index: 10 !important; 
                pointer-events: none; 
            }

            /* Cores Vibrantes (Vêm do PHP) */
            .bg-evento-azul { background-color: #eff6ff !important; border: 1px solid #bfdbfe !important; }
            .bg-evento-laranja { background-color: #fff7ed !important; border: 1px solid #fed7aa !important; }
            .bg-evento-vermelho { background-color: #fef2f2 !important; border: 1px solid #fecaca !important; }

            /* Dark Mode Cores */
            .dark .bg-evento-azul { background-color: rgba(59, 130, 246, 0.15) !important; border-color: rgba(59, 130, 246, 0.3) !important; }
            .dark .bg-evento-laranja { background-color: rgba(249, 115, 22, 0.15) !important; border-color: rgba(249, 115, 22, 0.3) !important; }
            .dark .bg-evento-vermelho { background-color: rgba(239, 68, 68, 0.15) !important; border-color: rgba(239, 68, 68, 0.3) !important; }

            /* Seleção (Anel de Foco Moderno) */
            .dia-selecionado { 
                box-shadow: 0 0 0 2px white, 0 0 0 4px #3b82f6 !important; 
                z-index: 30; 
                transform: scale(1.05);
            }
            .dark .dia-selecionado { box-shadow: 0 0 0 2px #111827, 0 0 0 4px #3b82f6 !important; }
        </style>
    </x-filament::section>
</x-filament-widgets::widget>