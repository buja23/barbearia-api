<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Passamos os eventos para o AlpineJS --}}
        <div x-data="calendarWidget(@this, {{ json_encode($this->getCalendarEvents()) }})" class="w-full flex justify-center">
            <style>
                /* === 1. Layout Mobile-First Premium (Sua Referência) === */
                .fc {
                    max-width: 380px !important;
                    margin: 0 auto !important;
                    font-family: 'Inter', sans-serif; /* Fonte mais clean */
                    background: transparent;
                }

                /* Toolbar Minimalista */
                .fc-toolbar {
                    justify-content: space-between !important;
                    align-items: center !important;
                    margin-bottom: 24px !important;
                    padding: 0 10px;
                }
                .fc-toolbar-title {
                    font-size: 1.1rem !important;
                    font-weight: 700;
                    color: #374151;
                    text-transform: capitalize;
                }
                .dark .fc-toolbar-title { color: white; }

                /* Botões de Navegação Invisíveis */
                .fc-button {
                    background: transparent !important;
                    border: none !important;
                    color: #9ca3af !important; /* Cinza suave */
                    box-shadow: none !important;
                    padding: 4px !important;
                }
                .fc-button:hover { color: #1f2937 !important; transform: scale(1.1); }
                .fc-button:active { color: #000 !important; }
                .fc-button:focus { box-shadow: none !important; }

                /* Limpeza da Grade */
                .fc-theme-standard td, .fc-theme-standard th, .fc-scrollgrid { 
                    border: none !important; 
                }
                .fc-col-header-cell-cushion { 
                    color: #9ca3af; 
                    text-decoration: none !important; 
                    font-weight: 600; 
                    text-transform: uppercase; 
                    font-size: 0.7rem; 
                    letter-spacing: 0.05em;
                }

                /* === 2. Célula do Dia (O Container) === */
                .fc-daygrid-day-frame {
                    min-height: 44px !important; /* Altura fixa para ficar redondinho */
                    height: 44px !important;
                    position: relative;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    cursor: pointer !important;
                    margin-bottom: 4px; /* Espaço entre linhas */
                }

                /* === 3. A Correção "Nuclear" dos Números === */
                .fc-daygrid-day-top {
                    flex-direction: row; 
                    justify-content: center;
                    position: absolute; /* Solta o número */
                    z-index: 20; /* Força ficar na frente de tudo */
                    pointer-events: none; /* Clique atravessa o número */
                }

                .fc-daygrid-day-number {
                    font-size: 0.9rem;
                    font-weight: 600;
                    color: #52525b; /* Zinc 600 */
                    text-decoration: none !important;
                    width: 32px;
                    height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 20; /* Redundância de segurança */
                }
                .dark .fc-daygrid-day-number { color: #e4e4e7; }

                /* === 4. As Bolinhas (Eventos de Fundo) === */
                .fc-bg-event {
                    opacity: 1 !important;
                    border-radius: 50% !important;
                    width: 36px !important; /* Ligeiramente maior que o número */
                    height: 36px !important;
                    left: 50% !important; 
                    top: 50% !important;
                    transform: translate(-50%, -50%) !important; /* Centralização perfeita */
                    z-index: 1 !important; /* Atrás do número */
                    pointer-events: none !important;
                }

                /* Cores Suaves (Pastel) */
                .bg-evento-azul { background-color: #dbeafe !important; } /* Blue 100 */
                .bg-evento-laranja { background-color: #ffedd5 !important; } /* Orange 100 */
                .bg-evento-vermelho { background-color: #fee2e2 !important; } /* Red 100 */

                /* Ajuste de cor do texto quando tem bolinha (opcional, para contraste) */
                .fc-day-other .fc-daygrid-day-number { color: #d4d4d8; } /* Dias de outro mês mais claros */

                /* === 5. Estados Especiais === */
                
                /* Hoje (Amarelo) */
                .fc-day-today .fc-daygrid-day-number {
                    background-color: #fcd34d !important; /* Amber 300 */
                    color: #451a03 !important;
                    border-radius: 50%;
                    box-shadow: 0 2px 4px rgba(251, 191, 36, 0.3);
                }
                .fc .fc-daygrid-day.fc-day-today { background-color: transparent !important; }

                /* Selecionado (Anel de Foco) */
                .dia-selecionado .fc-daygrid-day-frame {
                    background-color: rgba(0,0,0,0.03);
                    border-radius: 50%;
                }
                .dia-selecionado .fc-daygrid-day-number {
                    color: #000;
                    font-weight: 800;
                }
            </style>

            <div id="calendar" wire:ignore></div> 
        </div>
        <div x-data="calendarWidget(...)" class="w-full relative"> {{-- Adicione relative aqui --}}
    
    {{-- 🚀 SENIOR UX: Overlay de Carregamento --}}
    <div wire:loading.flex wire:target="selectDate" class="absolute inset-0 z-50 flex items-center justify-center bg-white/50 backdrop-blur-[2px] rounded-xl transition-all duration-300">
        <div class="flex flex-col items-center gap-2">
            <x-filament::loading-indicator class="h-8 w-8 text-primary-600" />
            <span class="text-xs font-semibold text-primary-700 animate-pulse">Filtrando...</span>
        </div>
    </div>

    {{-- O resto do seu calendário continua aqui... --}}
    </x-filament::section>
</x-filament-widgets::widget>