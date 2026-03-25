<x-filament-panels::page>
    {{-- Polling: verifica a cada 5s se pagamento foi confirmado --}}
    @if($paymentId)
        <div wire:poll.5000ms="checkPayment"></div>
    @endif

    {{-- ====== BANNER DE STATUS DA ASSINATURA ====== --}}
    @php
        $barbershop = $this->getBarbershop();
        $isActive   = $barbershop->isSubscriptionActive();
        $onTrial    = $barbershop->isOnTrial();
        $trialDays  = $barbershop->trialDaysRemaining();
    @endphp

    @if($isActive)
        <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-900/20 dark:border-green-800 p-4 flex items-center gap-3 mb-2">
            <x-heroicon-o-check-badge class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0" />
            <div>
                <p class="text-sm font-semibold text-green-800 dark:text-green-300">
                    Plano {{ $barbershop->subscription_plan }} ativo
                </p>
                <p class="text-xs text-green-600 dark:text-green-400">
                    Sua assinatura vence em {{ $barbershop->subscription_expires_at?->format('d/m/Y') }}.
                </p>
            </div>
        </div>
    @elseif($onTrial)
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-4 flex items-center gap-3 mb-2">
            <x-heroicon-o-clock class="w-6 h-6 text-amber-600 dark:text-amber-400 flex-shrink-0" />
            <div>
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                    Período de teste — {{ $trialDays }} {{ Str::plural('dia', $trialDays) }} restante{{ $trialDays !== 1 ? 's' : '' }}
                </p>
                <p class="text-xs text-amber-600 dark:text-amber-400">
                    Assine agora para não perder o acesso após o período gratuito.
                </p>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-700 p-4 flex items-center gap-3 mb-2">
            <x-heroicon-o-exclamation-circle class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0" />
            <div>
                <p class="text-sm font-semibold text-red-800 dark:text-red-300">
                    Acesso suspenso — Seu período de teste encerrou
                </p>
                <p class="text-xs text-red-600 dark:text-red-400">
                    Escolha um plano abaixo para reativar sua barbearia.
                </p>
            </div>
        </div>
    @endif

    {{-- ====== CHECKOUT PIX (aparece quando um plano foi selecionado) ====== --}}
    @if($paymentId && $pixCopyPaste)
        @php
            $selectedPlan = $this->plans->find($selectedPlanId);
        @endphp
        <div class="rounded-2xl border border-amber-300 dark:border-amber-600 bg-white dark:bg-gray-800 shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-white">Finalizar assinatura — {{ $selectedPlan?->name }}</h2>
                    <p class="text-amber-100 text-sm">
                        Valor: <span class="font-bold">R$ {{ number_format((float) $selectedPlan?->price, 2, ',', '.') }}</span>
                    </p>
                </div>
                <button wire:click="cancelPix" class="text-amber-100 hover:text-white transition-colors text-sm underline">
                    Trocar plano
                </button>
            </div>

            <div class="p-6 md:p-8">
                <div class="grid md:grid-cols-2 gap-8 items-center">

                    {{-- QR Code --}}
                    <div class="flex flex-col items-center gap-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Escaneie com seu app de banco:
                        </p>
                        @if($pixQrCode)
                            <div class="border-4 border-amber-400 rounded-2xl p-3 bg-white inline-block shadow">
                                <img
                                    src="data:image/png;base64,{{ $pixQrCode }}"
                                    alt="QR Code PIX"
                                    class="w-48 h-48"
                                >
                            </div>
                        @endif
                        <div class="flex items-center gap-2 text-xs text-gray-400">
                            <x-heroicon-o-arrow-path class="w-3 h-3 animate-spin opacity-60" />
                            <span wire:poll.5000ms>Aguardando confirmação...</span>
                        </div>
                    </div>

                    {{-- Pix Copia e Cola + instruções --}}
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">
                                Pix Copia e Cola
                            </label>
                            <div class="relative">
                                <textarea
                                    readonly
                                    rows="4"
                                    onclick="this.select()"
                                    class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-300 p-3 resize-none font-mono"
                                >{{ $pixCopyPaste }}</textarea>
                            </div>
                        </div>

                        <button
                            wire:click="markCopied"
                            onclick="navigator.clipboard.writeText('{{ $pixCopyPaste }}').then(() => {})"
                            class="w-full flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition-all
                                {{ $copied
                                    ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-300'
                                    : 'bg-amber-500 hover:bg-amber-600 text-white shadow-md hover:shadow-lg' }}"
                        >
                            @if($copied)
                                <x-heroicon-o-check class="w-4 h-4" />
                                Copiado!
                            @else
                                <x-heroicon-o-clipboard-document class="w-4 h-4" />
                                Copiar código PIX
                            @endif
                        </button>

                        <div class="rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 p-4 space-y-2">
                            <p class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">Como pagar</p>
                            <ol class="text-xs text-blue-600 dark:text-blue-300 space-y-1 list-decimal list-inside">
                                <li>Abra seu app do banco</li>
                                <li>Acesse a área <strong>PIX</strong></li>
                                <li>Cole o código ou escaneie o QR Code</li>
                                <li>Confirme o pagamento de <strong>R$ {{ number_format((float) $selectedPlan?->price, 2, ',', '.') }}</strong></li>
                            </ol>
                            <p class="text-xs text-blue-500 dark:text-blue-400 pt-1">
                                ✅ A ativação é automática após o pagamento.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endif

    {{-- ====== PLANOS ====== --}}
    @if(!$paymentId)
        <div>
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Escolha seu plano</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Sem taxas escondidas. Cancele quando quiser.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach($this->plans as $plan)
                    @php
                        $isCurrentPlan = $isActive && $barbershop->subscription_plan === $plan->name;
                    @endphp
                    <div
                        class="relative rounded-2xl border-2 {{ $plan->is_popular ? 'border-amber-400 dark:border-amber-500 shadow-xl shadow-amber-100 dark:shadow-amber-900/20' : 'border-gray-200 dark:border-gray-700' }}
                               bg-white dark:bg-gray-800 p-6 flex flex-col"
                    >
                        {{-- Badge Mais Popular --}}
                        @if($plan->is_popular)
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                                <span class="bg-amber-500 text-white text-xs font-bold px-4 py-1 rounded-full shadow">
                                    ⭐ Mais popular
                                </span>
                            </div>
                        @endif

                        {{-- Cabeçalho do plano --}}
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $plan->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $plan->description }}</p>
                        </div>

                        {{-- Preço --}}
                        <div class="mb-6">
                            <div class="flex items-end gap-1">
                                <span class="text-3xl font-extrabold text-gray-900 dark:text-white">
                                    R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                                </span>
                                <span class="text-sm text-gray-400 dark:text-gray-500 mb-1">/mês</span>
                            </div>
                            @if($plan->price <= 1)
                                <span class="text-xs text-amber-600 dark:text-amber-400 font-semibold">
                                    ✦ Preço de lançamento especial
                                </span>
                            @endif
                        </div>

                        {{-- Features --}}
                        <ul class="flex-1 space-y-2 mb-6">
                            @foreach((array) $plan->features as $feature)
                                <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        {{-- Botão de ação --}}
                        @if($isCurrentPlan)
                            <div class="w-full text-center rounded-xl border border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20 py-2.5 text-sm font-semibold text-green-700 dark:text-green-400">
                                ✅ Plano atual
                            </div>
                        @else
                            <button
                                wire:click="selectPlan({{ $plan->id }})"
                                wire:loading.attr="disabled"
                                wire:target="selectPlan({{ $plan->id }})"
                                class="w-full rounded-xl px-4 py-3 text-sm font-semibold transition-all
                                    {{ $plan->is_popular
                                        ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-md hover:shadow-lg disabled:opacity-60'
                                        : 'bg-gray-900 hover:bg-gray-700 dark:bg-white dark:hover:bg-gray-200 text-white dark:text-gray-900 disabled:opacity-60' }}"
                            >
                                <span wire:loading.remove wire:target="selectPlan({{ $plan->id }})">
                                    Assinar agora — R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                                </span>
                                <span wire:loading wire:target="selectPlan({{ $plan->id }})" class="flex items-center justify-center gap-2">
                                    <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin" />
                                    Gerando PIX...
                                </span>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Garantia / segurança --}}
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-6 text-sm text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-lock-closed class="w-4 h-4 text-gray-400" />
                    Pagamento seguro via PIX
                </div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-shield-check class="w-4 h-4 text-gray-400" />
                    Ativação automática
                </div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrow-uturn-left class="w-4 h-4 text-gray-400" />
                    Cancele quando quiser
                </div>
            </div>
        </div>
    @endif

</x-filament-panels::page>
