<x-filament-panels::page>
    {{-- Polling: verifica a cada 5s se pagamento foi confirmado --}}
    @if($paymentId)
        <div wire:poll.5000ms="checkPayment"></div>
    @endif

    @php
        $barbershop = $this->getBarbershop();
        $isActive = $barbershop->isSubscriptionActive();
        $onTrial = $barbershop->isOnTrial();
        $trialDays = $barbershop->trialDaysRemaining();
        $monthlyReferencePlan = $this->plans->firstWhere('billing_cycle_months', 1);

        if ($isActive && $barbershop->subscription_status === 'cancelled') {
            $displayPlanName = $barbershop->subscription_plan === 'Basico' ? 'Básico' : $barbershop->subscription_plan;
            $statusLabel = 'Cancelamento agendado';
            $statusTitle = "Plano {$displayPlanName} • Expira em {$barbershop->subscription_expires_at?->format('d/m/Y')}";
            $statusBody  = 'Acesso mantido até o vencimento. Reative antes da data para não perder o histórico.';
            $statusIcon  = 'heroicon-o-clock';
            $statusTone  = 'border-orange-500/20 bg-orange-50/50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400';
        } elseif ($isActive) {
            $displayPlanName = $barbershop->subscription_plan === 'Basico' ? 'Básico' : $barbershop->subscription_plan;
            $statusLabel = 'Assinatura ativa';
            $statusTitle = "Plano {$displayPlanName} em operação";
            $statusBody = "Renovação automática em {$barbershop->subscription_expires_at?->format('d/m/Y')}.";
            $statusIcon = 'heroicon-o-check-badge';
            $statusTone = 'border-emerald-500/20 bg-emerald-50/50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
        } elseif ($onTrial) {
            $statusLabel = 'Período de teste';
            $statusTitle = "{$trialDays} " . Str::plural('dia', $trialDays) . " de cortesia restante" . ($trialDays !== 1 ? 's' : '');
            $statusBody = 'Aproveite todos os recursos. Assine para garantir a continuidade após o teste.';
            $statusIcon = 'heroicon-o-sparkles';
            $statusTone = 'border-sky-500/20 bg-sky-50/50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300';
        } else {
            $statusLabel = 'Acesso suspenso';
            $statusTitle = 'O teste encerrou';
            $statusBody = 'Escolha um dos planos abaixo para reativar sua agenda e voltar a faturar.';
            $statusIcon = 'heroicon-o-exclamation-circle';
            $statusTone = 'border-rose-500/20 bg-rose-50/50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400';
        }
    @endphp

    <div class="mx-auto max-w-7xl space-y-8 animate-in fade-in duration-700">
        {{-- ── Header & Status ─────────────────────────────────── --}}
        <header class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white p-8 shadow-sm dark:border-white/10 dark:bg-gray-900 md:p-12">
            <div class="relative z-10 flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-6">
                    <div class="inline-flex items-center gap-3 rounded-full border px-4 py-1.5 text-xs font-bold uppercase tracking-widest {{ $statusTone }}">
                        <x-dynamic-component :component="$statusIcon" class="h-4 w-4" />
                        {{ $statusLabel }}
                    </div>
                    
                    <div class="max-w-2xl">
                        <h1 class="text-4xl font-black tracking-tight text-gray-950 dark:text-white sm:text-6xl">
                            {{ $isActive ? 'Sua assinatura.' : 'Evolua sua barbearia.' }}
                        </h1>
                        <p class="mt-4 text-lg leading-relaxed text-gray-500 dark:text-gray-400">
                            {{ $statusBody }} Gerencie seus pagamentos e escolha o plano ideal para o seu crescimento.
                        </p>
                    </div>
                </div>

                @if($isActive && $barbershop->subscription_status !== 'cancelled')
                    <div class="flex flex-col items-start gap-4 rounded-2xl border border-gray-100 bg-gray-50/50 p-6 dark:border-white/5 dark:bg-white/5">
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Próximo vencimento</p>
                        <p class="text-2xl font-black text-gray-950 dark:text-white">{{ $barbershop->subscription_expires_at?->format('d/m/Y') }}</p>
                        <button wire:click="confirmCancel" class="text-xs font-bold text-rose-600 underline-offset-4 hover:underline">Cancelar renovação automática</button>
                    </div>
                @endif
            </div>
            
            {{-- Detalhe visual de fundo --}}
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-amber-500/5 blur-3xl"></div>
        </header>

        {{-- ── Confirmação de Cancelamento ────────────────────── --}}
        @if($showCancelConfirm)
            <div class="rounded-3xl border border-rose-200 bg-rose-50/50 p-8 dark:border-rose-500/20 dark:bg-rose-500/5">
                <div class="flex flex-col gap-6 md:flex-row md:items-center">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-950 dark:text-white">Deseja realmente cancelar?</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Seu acesso continuará ativo até o fim do ciclo atual. Depois disso, seus dados serão preservados, mas a agenda ficará bloqueada.</p>
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="cancelSubscription" class="rounded-xl bg-rose-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-rose-700">Confirmar</button>
                        <button wire:click="dismissCancel" class="rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 shadow-sm transition hover:bg-gray-50">Manter plano</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Grid de Planos ─────────────────────────────────── --}}
        @if(!$paymentId && !$showCardForm)
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($this->plans as $plan)
                    @php
                        $isCurrent = $isActive && $barbershop->subscription_plan === $plan->name;
                        $displayName = $plan->name === 'Basico' ? 'Básico' : $plan->name;
                        $referenceTotal = $monthlyReferencePlan ? (float) $monthlyReferencePlan->price * max(1, $plan->billing_cycle_months) : null;
                        $savingsAmount = $referenceTotal && $plan->billing_cycle_months > 1 ? max(0, $referenceTotal - (float) $plan->price) : 0;
                        $savingsPercent = $referenceTotal && $savingsAmount > 0 ? round(($savingsAmount / $referenceTotal) * 100) : 0;
                    @endphp

                    <div @class([
                        'relative flex flex-col rounded-[2.5rem] border p-8 transition-all duration-300',
                        'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/10 dark:bg-amber-500/5' => $plan->is_popular,
                        'border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900 shadow-sm hover:shadow-md' => !$plan->is_popular,
                    ])>
                        @if($plan->is_popular)
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-amber-500 px-4 py-1 text-[10px] font-black uppercase tracking-widest text-white shadow-lg">Recomendado</span>
                        @endif

                        <div class="mb-8">
                            <h3 class="text-2xl font-black text-gray-950 dark:text-white">{{ $displayName }}</h3>
                            <p class="mt-2 text-sm font-medium text-gray-400 uppercase tracking-widest">{{ $plan->billing_cycle_label }}</p>
                        </div>

                        <div class="mb-8 flex items-baseline gap-1">
                            <span class="text-4xl font-black tracking-tight text-gray-950 dark:text-white">R$ {{ number_format((float) $plan->price, 2, ',', '.') }}</span>
                            <span class="text-sm font-medium text-gray-500">/ciclo</span>
                        </div>

                        <ul class="mb-10 flex-1 space-y-4">
                            @foreach((array) $plan->features as $feature)
                                <li class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300">
                                    <x-heroicon-m-check-circle class="h-5 w-5 shrink-0 text-emerald-500" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        @if($isCurrent)
                            <div class="flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-50 py-4 text-sm font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                <x-heroicon-s-check-badge class="h-5 w-5" />
                                Plano Atual
                            </div>
                        @else
                            <div class="space-y-3">
                                <button wire:click="initiateCardPayment({{ $plan->id }})" class="group w-full rounded-2xl bg-gray-950 py-4 text-sm font-bold text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-950 dark:hover:bg-gray-100 shadow-xl shadow-gray-950/10">
                                    Pagar com Cartão
                                </button>
                                <button wire:click="selectPlan({{ $plan->id }})" class="w-full rounded-2xl border border-gray-200 bg-white py-4 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-white/10 dark:bg-transparent dark:text-gray-300 dark:hover:bg-white/5">
                                    Gerar PIX
                                </button>
                            </div>
                        @endif

                        @if($savingsPercent > 0)
                            <p class="mt-6 text-center text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                Economia de {{ $savingsPercent }}% neste plano
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ── Checkout / PIX ────────────────────────────────── --}}
        @if($paymentId && $pixCopyPaste)
            <div class="rounded-[2.5rem] border border-gray-200 bg-white p-8 dark:border-white/10 dark:bg-gray-900 shadow-xl overflow-hidden">
                <div class="grid gap-12 lg:grid-cols-2">
                    <div class="space-y-8">
                        <div>
                            <h2 class="text-3xl font-black text-gray-950 dark:text-white">Pagamento via PIX</h2>
                            <p class="mt-2 text-gray-500">Escaneie o código ou copie o link abaixo para ativar sua conta agora.</p>
                        </div>

                        <div class="flex flex-col items-center justify-center rounded-3xl bg-gray-50 p-8 dark:bg-white/5 border-2 border-dashed border-gray-200 dark:border-white/10">
                            @if($pixQrCode)
                                <div class="rounded-2xl bg-white p-4 shadow-lg border border-gray-100">
                                    <img src="data:image/png;base64,{{ $pixQrCode }}" class="h-48 w-48" alt="QR Code PIX">
                                </div>
                            @endif
                            <div class="mt-6 flex items-center gap-3 text-sm font-bold text-amber-600 animate-pulse">
                                <x-heroicon-o-arrow-path class="h-5 w-5 animate-spin" />
                                Aguardando pagamento...
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-3xl bg-gray-950 p-8 text-white shadow-2xl">
                            <p class="text-xs font-bold uppercase tracking-widest text-white/50">Valor do Plano</p>
                            <h4 class="mt-2 text-4xl font-black">R$ {{ number_format((float) $this->plans->find($selectedPlanId)?->price, 2, ',', '.') }}</h4>
                            <hr class="my-6 border-white/10">
                            <div class="space-y-4">
                                <label class="text-xs font-bold uppercase tracking-widest text-white/50">Pix Copia e Cola</label>
                                <div class="relative group">
                                    <textarea readonly class="w-full rounded-xl border-none bg-white/5 p-4 pr-12 text-xs font-mono text-white/80 focus:ring-amber-500">{{ $pixCopyPaste }}</textarea>
                                    <button 
                                        onclick="navigator.clipboard.writeText('{{ $pixCopyPaste }}'); alert('Copiado!')"
                                        class="absolute right-3 top-3 rounded-lg bg-amber-500 p-2 text-white transition hover:bg-amber-600"
                                    >
                                        <x-heroicon-s-clipboard-document-check class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button wire:click="cancelPix" class="w-full text-center text-sm font-bold text-gray-400 hover:text-gray-600">Escolher outro plano</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Mercado Pago Brick ───────────────────────────── --}}
        @if($showCardForm && $selectedPlanId)
            <div class="rounded-[2.5rem] border border-gray-200 bg-white p-8 dark:border-white/10 dark:bg-gray-900 shadow-xl animate-in slide-in-from-bottom-4 duration-500">
                <div class="mb-8 flex items-center justify-between border-b pb-6 dark:border-white/10">
                    <div>
                        <h2 class="text-2xl font-black text-gray-950 dark:text-white">Pagamento Seguro</h2>
                        <p class="text-sm text-gray-500">Dados protegidos por criptografia SSL</p>
                    </div>
                    <button wire:click="cancelCardForm" class="rounded-xl bg-gray-100 p-3 hover:bg-gray-200 dark:bg-white/5 dark:hover:bg-white/10">
                        <x-heroicon-o-x-mark class="h-6 w-6 text-gray-500" />
                    </button>
                </div>

                {{-- O SDK do MP precisa de um container limpo --}}
                <div wire:ignore class="min-h-[400px]">
                    @php $mpPublicKey = config('services.mercadopago.public_key'); @endphp
                    @if(empty($mpPublicKey))
                        <div class="p-8 text-center text-rose-500 font-bold border-2 border-dashed rounded-3xl border-rose-200">
                            Erro: Public Key não configurada.
                        </div>
                    @else
                        <div id="saas-card-brick" x-data="{ 
                            initMP() {
                                const mp = new MercadoPago('{{ $mpPublicKey }}', { locale: 'pt-BR' });
                                const builder = mp.bricks();
                                builder.create('cardPayment', 'saas-card-brick', {
                                    initialization: { amount: {{ (float) $this->plans->find($selectedPlanId)?->price }} },
                                    customization: { visual: { hideFormTitle: true }, paymentMethods: { maxInstallments: 12 } },
                                    callbacks: {
                                        onSubmit: (formData) => {
                                            return new Promise((resolve, reject) => {
                                                $wire.processCardPayment({{ $selectedPlanId }}, formData)
                                                    .then(() => resolve())
                                                    .catch((e) => reject(e));
                                            });
                                        },
                                        onError: (error) => console.error(error),
                                    },
                                });
                            }
                        }" x-init="initMP()"></div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Footer Trust Badges --}}
        <footer class="flex flex-wrap justify-center gap-8 opacity-40 grayscale transition hover:grayscale-0">
            <div class="flex items-center gap-2 text-sm font-bold">
                <x-heroicon-o-shield-check class="h-5 w-5" /> SSL Seguro
            </div>
            <div class="flex items-center gap-2 text-sm font-bold">
                <x-heroicon-o-bolt class="h-5 w-5" /> Ativação Imediata
            </div>
            <div class="flex items-center gap-2 text-sm font-bold">
                <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" /> Suporte 24/7
            </div>
        </footer>
    </div>

    {{-- Script necessário para o MP Bricks --}}
    @push('scripts')
        <script src="https://sdk.mercadopago.com/js/v2"></script>
    @endpush
</x-filament-panels::page>