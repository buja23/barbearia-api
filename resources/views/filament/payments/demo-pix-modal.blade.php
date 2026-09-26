<div class="space-y-6">

    {{-- Cabeçalho --}}
    <div class="flex flex-col items-center justify-center py-4 text-center">

        <div
            class="mb-4 flex h-16 w-16 items-center justify-center rounded-full
                   bg-amber-100 dark:bg-amber-900/30"
        >
            <x-filament::icon
                icon="heroicon-o-qr-code"
                class="h-8 w-8 text-amber-600 dark:text-amber-400"
            />
        </div>

        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
            Pagamento PIX — Demonstração
        </h3>

        <p
            class="mt-2 max-w-md text-sm leading-6
                   text-gray-600 dark:text-gray-400"
        >
            Esta conta faz parte do ambiente demonstrativo do sistema.
            Nenhuma cobrança ou transação financeira real será realizada.
        </p>

    </div>

    {{-- Valor --}}
    <div
        class="rounded-xl border border-gray-200 bg-gray-50 p-5
               dark:border-gray-700 dark:bg-gray-800/50"
    >

        <div class="flex items-center justify-between gap-4">

            <div>

                <p
                    class="text-xs font-semibold uppercase tracking-wider
                           text-gray-500 dark:text-gray-400"
                >
                    Valor demonstrativo
                </p>

                <p
                    class="mt-1 text-2xl font-bold
                           text-gray-900 dark:text-white"
                >
                    R$
                    {{
                        number_format(
                            $record->total_price
                                ?? $record->total_amount
                                ?? 0,
                            2,
                            ',',
                            '.'
                        )
                    }}
                </p>

            </div>

            <x-filament::badge color="warning">
                Modo Demo
            </x-filament::badge>

        </div>

    </div>

    {{-- Área visual simulando QR --}}
    <div class="flex justify-center">

        <div
            class="flex h-48 w-48 flex-col items-center justify-center
                   rounded-2xl border-2 border-dashed border-gray-300
                   bg-white p-6 text-center
                   dark:border-gray-600 dark:bg-gray-900"
        >

            <x-filament::icon
                icon="heroicon-o-qr-code"
                class="h-16 w-16 text-gray-400"
            />

            <p
                class="mt-3 text-sm font-semibold
                       text-gray-600 dark:text-gray-300"
            >
                QR Code PIX
            </p>

            <p
                class="mt-1 text-xs
                       text-gray-400 dark:text-gray-500"
            >
                Desativado no ambiente demo
            </p>

        </div>

    </div>

    {{-- Explicação --}}
    <div
        class="rounded-xl border border-blue-200 bg-blue-50 p-4
               dark:border-blue-800 dark:bg-blue-900/20"
    >

        <div class="flex gap-3">

            <x-filament::icon
                icon="heroicon-o-information-circle"
                class="mt-0.5 h-5 w-5 flex-shrink-0
                       text-blue-500"
            />

            <div>

                <p
                    class="text-sm font-semibold
                           text-blue-800 dark:text-blue-300"
                >
                    Integração de pagamento desativada
                </p>

                <p
                    class="mt-1 text-sm leading-5
                           text-blue-700 dark:text-blue-400"
                >
                    Em ambiente de produção, esta funcionalidade
                    gera um QR Code PIX, código copia e cola e permite
                    registrar a confirmação do pagamento.
                </p>

            </div>

        </div>

    </div>

    {{-- Recursos disponíveis em produção --}}
    <div class="grid grid-cols-3 gap-3">

        <div
            class="rounded-xl border border-gray-200 p-4 text-center
                   dark:border-gray-700"
        >
            <x-filament::icon
                icon="heroicon-o-qr-code"
                class="mx-auto h-6 w-6 text-gray-500"
            />

            <p class="mt-2 text-xs font-medium">
                QR Code
            </p>
        </div>

        <div
            class="rounded-xl border border-gray-200 p-4 text-center
                   dark:border-gray-700"
        >
            <x-filament::icon
                icon="heroicon-o-clipboard-document"
                class="mx-auto h-6 w-6 text-gray-500"
            />

            <p class="mt-2 text-xs font-medium">
                Copia e Cola
            </p>
        </div>

        <div
            class="rounded-xl border border-gray-200 p-4 text-center
                   dark:border-gray-700"
        >
            <x-filament::icon
                icon="heroicon-o-check-circle"
                class="mx-auto h-6 w-6 text-gray-500"
            />

            <p class="mt-2 text-xs font-medium">
                Confirmação
            </p>
        </div>

    </div>

</div>