<div class="flex items-center">
    @php
        $color = match ($getState()) {
            'pending' => 'warning',   // Amarelo
            'confirmed' => 'success', // Verde
            'completed' => 'info',    // Azul
            'cancelled' => 'danger',  // Vermelho
            default => 'gray',
        };
        
        // Mapeando para classes do Tailwind (ajuste conforme seu tema)
        $colorClass = match ($color) {
            'warning' => 'border-yellow-500 text-yellow-700 bg-yellow-50',
            'success' => 'border-green-500 text-green-700 bg-green-50',
            'info'    => 'border-blue-500 text-blue-700 bg-blue-50',
            'danger'  => 'border-red-500 text-red-700 bg-red-50',
            'gray'    => 'border-gray-500 text-gray-700 bg-gray-50',
        };
    @endphp

    <div class="px-3 py-1 border-l-4 rounded-r-md text-xs font-bold uppercase tracking-wider {{ $colorClass }}">
        {{ $getRecord()->status_label ?? $getState() }}
    </div>
</div>