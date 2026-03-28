<x-filament-panels::page>
    <div class="max-w-2xl mx-auto">
        <div class="mb-6 rounded-xl border border-amber-200/80 bg-amber-50/85 p-4 text-amber-700 dark:border-amber-700/80 dark:bg-amber-900/20 dark:text-amber-300">
            <div class="flex items-start gap-3">
                <x-heroicon-o-information-circle class="mt-0.5 h-5 w-5 shrink-0"/>
                <div>
                    <p class="font-semibold">Encontrou um problema ou tem uma ideia?</p>
                    <p class="mt-1 text-sm opacity-90">Preencha o formulário abaixo. Nossa equipe receberá uma notificação imediatamente e retornará em breve.</p>
                </div>
            </div>
        </div>

        <form wire:submit="submit" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit" size="lg" icon="heroicon-o-paper-airplane">
                    Enviar Reporte
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
