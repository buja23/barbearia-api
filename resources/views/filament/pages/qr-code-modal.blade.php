<div class="flex flex-col items-center justify-center space-y-6 p-4">
    <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100">
        {{-- O QR Code SVG gerado --}}
        {!! $qrCode !!}
    </div>

    <div class="text-center space-y-2">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
            {{ $name ?? 'Minha Barbearia' }}
        </h3>
        <p class="text-sm text-gray-500">
            Escaneie para agendar seu horário
        </p>
        
        <a href="{{ $url }}" target="_blank" class="inline-flex items-center gap-2 text-primary-600 font-bold hover:underline mt-2">
            <x-filament::icon icon="heroicon-m-link" class="w-4 h-4" />
            Acessar Link
        </a>
    </div>

    <div class="w-full pt-4 border-t dark:border-gray-700">
        <button onclick="window.print()" class="w-full py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-bold transition">
            🖨️ Imprimir
        </button>
    </div>
</div>