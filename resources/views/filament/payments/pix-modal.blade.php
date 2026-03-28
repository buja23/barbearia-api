<div class="space-y-4">
    @php
        $isAppointment = isset($record->payment_status);
        $isApproved = $isAppointment ? $record->payment_status === 'approved' : $record->status === 'approved';
        $isCanceled = $isAppointment
            ? ($record->payment_status === 'rejected' || $record->status === 'canceled')
            : ($record->status === 'cancelled');
        $amount = $record->total_price ?? $record->total_amount ?? 0;
        $qrCode = $record->pix_qr_code_url ?? $record->qr_code_base64 ?? null;
        $titleContext = $isAppointment ? 'agendamento' : 'venda';
    @endphp

    @if($isApproved)
        <div class="flex flex-col items-center justify-center p-6 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-100 dark:border-green-800 text-center">
            <x-filament::icon icon="heroicon-o-check-circle" class="w-16 h-16 text-green-500 mb-4" />
            <h3 class="text-xl font-bold text-green-700 dark:text-green-400">Pagamento Recebido!</h3>
            <p class="text-sm text-green-600 dark:text-green-300 mt-2">Esta {{ $titleContext }} já foi paga com sucesso.</p>
        </div>

    @elseif($isCanceled)
        <div class="flex flex-col items-center justify-center p-6 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100 dark:border-red-800 text-center">
            <x-filament::icon icon="heroicon-o-x-circle" class="w-16 h-16 text-red-500 mb-4" />
            <h3 class="text-xl font-bold text-red-700 dark:text-red-400">Pagamento Cancelado</h3>
            <p class="text-sm text-red-600 dark:text-red-300 mt-2">O pagamento ou {{ $titleContext }} foi cancelado.</p>
        </div>

    @else
        <div class="flex items-center justify-center gap-2 mb-2">
            <span class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                R$ {{ number_format($amount, 2, ',', '.') }}
            </span>
        </div>

        <div class="text-center">
            <p class="text-sm font-medium text-gray-500 mb-4">
                Escaneie o QR Code abaixo para pagar via PIX:
            </p>

            {{-- QR Code Imagem --}}
            @if(!empty($qrCode))
                <div class="flex justify-center mb-6">
                    <img
                        src="{{ $qrCode }}"
                        alt="QR Code PIX"
                        class="w-52 h-52 border-2 border-gray-100 rounded-lg p-2 bg-white"
                    >
                </div>
            @endif

            {{-- Copia e Cola --}}
            <div class="relative">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wider">
                    PIX copia e cola
                </label>

                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        value="{{ $record->pix_copy_paste }}"
                        readonly
                        class="w-full text-xs p-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-600 font-mono"
                        id="pix-copia-cola"
                    >

                    <button
                        type="button"
                        onclick="
                            var el = document.getElementById('pix-copia-cola');
                            navigator.clipboard ? navigator.clipboard.writeText(el.value) : (el.select(), document.execCommand('copy'));
                            this.innerHTML = '<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'></path></svg>';
                            setTimeout(() => this.innerHTML = '<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z\'></path></svg>', 2000);
                        "
                        class="p-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors flex-shrink-0"
                        title="Copiar código PIX"
                    >
                        <x-filament::icon icon="heroicon-o-clipboard" class="w-5 h-5" />
                    </button>
                </div>
            </div>

            @if($record->barbershop?->pix_key)
                <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <p class="text-xs text-amber-700 dark:text-amber-300 flex items-center justify-center gap-2">
                        <x-filament::icon icon="heroicon-o-bell-alert" class="w-4 h-4 flex-shrink-0" />
                        Após o cliente pagar, clique em <strong class="mx-1">✓ Confirmar PIX</strong> na linha da {{ $titleContext }}.
                    </p>
                </div>
            @else
                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                    <p class="text-xs text-blue-700 dark:text-blue-300 flex items-center justify-center gap-2">
                        <x-filament::loading-indicator class="w-4 h-4" />
                        Aguardando confirmação do pagamento via PIX...
                    </p>
                </div>
            @endif
        </div>
    @endif
</div>