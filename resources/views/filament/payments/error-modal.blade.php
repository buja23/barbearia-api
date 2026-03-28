<div class="p-4 text-center">
    <x-filament::icon icon="heroicon-o-x-circle" class="h-12 w-12 text-red-500 mx-auto mb-2" />
    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Falha ao Gerar Pix</h3>
    <p class="text-sm text-red-600 mt-2">{{ $error ?? 'Verifique as credenciais e tente novamente.' }}</p>

    @if(!empty($actionUrl))
        <div class="mt-4">
            <a
                href="{{ $actionUrl }}"
                class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition-colors"
            >
                {{ $actionLabel ?? 'Cadastrar PIX' }}
            </a>
        </div>
    @endif
</div>