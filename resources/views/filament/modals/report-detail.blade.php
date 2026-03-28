<div class="space-y-4 text-sm">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <span class="font-semibold text-gray-500 dark:text-gray-400">Tipo</span>
            <p class="mt-1">{{ $report->getTypeLabel() }}</p>
        </div>
        <div>
            <span class="font-semibold text-gray-500 dark:text-gray-400">Status</span>
            <p class="mt-1">{{ $report->getStatusLabel() }}</p>
        </div>
        <div>
            <span class="font-semibold text-gray-500 dark:text-gray-400">Enviado por</span>
            <p class="mt-1">{{ $report->user?->name ?? '—' }}</p>
        </div>
        <div>
            <span class="font-semibold text-gray-500 dark:text-gray-400">Barbearia</span>
            <p class="mt-1">{{ $report->barbershop?->name ?? '—' }}</p>
        </div>
        <div>
            <span class="font-semibold text-gray-500 dark:text-gray-400">Recebido em</span>
            <p class="mt-1">{{ $report->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <div>
        <span class="font-semibold text-gray-500 dark:text-gray-400">Descrição</span>
        <p class="mt-1 whitespace-pre-line rounded-lg bg-gray-100 p-3 dark:bg-gray-800">{{ $report->description }}</p>
    </div>

    @if ($report->image_path)
        <div>
            <span class="font-semibold text-gray-500 dark:text-gray-400">Imagem anexada</span>
            <div class="mt-2">
                <a href="{{ Storage::disk('public')->url($report->image_path) }}" target="_blank">
                    <img
                        src="{{ Storage::disk('public')->url($report->image_path) }}"
                        alt="Imagem do reporte"
                        class="max-h-64 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:opacity-90 transition"
                    />
                </a>
                <p class="mt-1 text-xs text-gray-400">Clique para abrir em tamanho completo</p>
            </div>
        </div>
    @endif

    @if ($report->admin_notes)
        <div>
            <span class="font-semibold text-gray-500 dark:text-gray-400">Notas internas</span>
            <p class="mt-1 whitespace-pre-line rounded-lg bg-amber-50 p-3 text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">{{ $report->admin_notes }}</p>
        </div>
    @endif
</div>
