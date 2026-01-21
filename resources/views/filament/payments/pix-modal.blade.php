<div class="flex flex-col items-center justify-center p-4 space-y-6 text-center">
    {{-- Lógica de Sucesso: Se já estiver pago, mostra confetes --}}
    @if($record->status === 'confirmed' || $record->payment_status === 'approved')
        <div class="flex flex-col items-center animate-in fade-in zoom-in duration-500">
            <div class="rounded-full bg-green-100 p-4 mb-4">
                <x-filament::icon icon="heroicon-o-check-circle" class="h-16 w-16 text-green-600" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Pagamento Confirmado!</h3>
            <p class="text-gray-500">O agendamento foi garantido.</p>
        </div>
    @else
        {{-- Lógica de Pagamento Pendente --}}
        
        {{-- Polling: A cada 5s, atualiza este componente para checar o status --}}
        <div wire:poll.5s class="w-full flex flex-col items-center">
            
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">
                Valor a pagar: <span class="text-gray-900 dark:text-white font-bold text-lg">R$ {{ number_format($record->total_price, 2, ',', '.') }}</span>
            </p>

            {{-- Imagem do QR Code (Base64) --}}
            @if($record->pix_qr_code_url)
                <div class="border-4 border-gray-900 rounded-xl overflow-hidden shadow-lg mb-6">
                    <img src="data:image/png;base64,{{ $record->pix_qr_code_url }}" class="w-64 h-64 object-cover" alt="QR Code Pix" />
                </div>
            @else
                <div class="flex flex-col items-center justify-center w-64 h-64 bg-gray-100 rounded-xl mb-6">
                    <x-filament::loading-indicator class="h-8 w-8 text-gray-400" />
                    <span class="text-xs text-gray-500 mt-2">Gerando Pix...</span>
                </div>
            @endif

            {{-- Código Copia e Cola --}}
            <div class="w-full max-w-sm space-y-2">
                <label class="text-xs font-semibold text-gray-400 uppercase">Pix Copia e Cola</label>
                <div class="flex items-center gap-2">
                    <x-filament::input.wrapper class="w-full">
                        <x-filament::input
                            type="text"
                            readonly
                            value="{{ $record->pix_copy_paste }}"
                            class="text-xs text-gray-500 font-mono"
                        />
                    </x-filament::input.wrapper>
                    
                    {{-- Botão de Copiar Nativo do Filament --}}
                    <x-filament::button
                        icon="heroicon-m-clipboard"
                        color="gray"
                        x-on:click="window.navigator.clipboard.writeText('{{ $record->pix_copy_paste }}'); $tooltip('Copiado!', { timeout: 1500 });"
                    >
                        Copiar
                    </x-filament::button>
                </div>
            </div>

            <p class="text-xs text-gray-400 mt-6 animate-pulse">
                Aguardando pagamento... A tela atualizará automaticamente.
            </p>
        </div>
    @endif
</div>

<div class="mt-4">
    <x-filament::button 
        size="xs" 
        color="info" 
        wire:click="$refresh" {{-- Isso força um refresh do componente --}}
    >
        Verificar Pagamento Agora
    </x-filament::button>
</div>