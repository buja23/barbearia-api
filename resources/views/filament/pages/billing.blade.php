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
            $statusTitle = 'Plano ' . $displayPlanName . ' · acesso até ' . $barbershop->subscription_expires_at?->format('d/m/Y');
            $statusBody  = 'Sua assinatura foi cancelada. Você ainda tem acesso completo até a data acima. Assine novamente antes do vencimento para não interromper o serviço.';
            $statusIcon  = 'heroicon-o-clock';
            $statusTone  = 'border-orange-200/80 bg-orange-50/85 text-orange-700 dark:border-orange-800/80 dark:bg-orange-900/20 dark:text-orange-300';
        } elseif ($isActive) {
            $displayPlanName = $barbershop->subscription_plan === 'Basico' ? 'Básico' : $barbershop->subscription_plan;
            $statusLabel = 'Assinatura ativa';
            $statusTitle = 'Plano ' . $displayPlanName . ' em operação';
            $statusBody = 'Sua assinatura vence em ' . $barbershop->subscription_expires_at?->format('d/m/Y') . '.';
            $statusIcon = 'heroicon-o-check-badge';
            $statusTone = 'border-emerald-200/80 bg-emerald-50/85 text-emerald-700 dark:border-emerald-800/80 dark:bg-emerald-900/20 dark:text-emerald-300';
        } elseif ($onTrial) {
            $statusLabel = 'Período de teste';
            $statusTitle = $trialDays . ' ' . Str::plural('dia', $trialDays) . ' restante' . ($trialDays !== 1 ? 's' : '') . ' para escolher seu plano';
            $statusBody = 'Assine agora para manter o acesso sem interrupção quando o teste terminar.';
            $statusIcon = 'heroicon-o-clock';
            $statusTone = 'border-amber-200/80 bg-amber-50/85 text-amber-700 dark:border-amber-700/80 dark:bg-amber-900/20 dark:text-amber-300';
        } else {
            $statusLabel = 'Acesso suspenso';
            $statusTitle = 'Seu período de teste encerrou';
            $statusBody = 'Escolha um plano abaixo para reativar sua barbearia e voltar a operar normalmente.';
            $statusIcon = 'heroicon-o-exclamation-circle';
            $statusTone = 'border-rose-200/80 bg-rose-50/85 text-rose-700 dark:border-rose-800/80 dark:bg-rose-900/20 dark:text-rose-300';
        }
    @endphp

    <div class="mx-auto max-w-7xl space-y-10">
        <section class="overflow-hidden rounded-[30px] border border-gray-200/80 bg-white/95 px-6 py-8 shadow-[0_20px_60px_-32px_rgba(15,23,42,0.28)] dark:border-white/10 dark:bg-gray-950/75 sm:px-8 sm:py-10">
            <div class="space-y-8">
                <div class="inline-flex items-start gap-5 rounded-[24px] border px-5 py-5 {{ $statusTone }}">
                    <span class="mt-0.5 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-white/70 dark:bg-white/10">
                        <x-dynamic-component :component="$statusIcon" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] opacity-80">{{ $statusLabel }}</p>
                        <h2 class="mt-2 text-base font-black tracking-[-0.02em] text-gray-950 dark:text-white">{{ $statusTitle }}</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $statusBody }}</p>
                    </div>
                </div>

                <div>
                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.22em] text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                        Plano e cobrança
                    </span>
                    <h1 class="mt-6 max-w-3xl text-3xl font-black tracking-[-0.03em] text-gray-950 dark:text-white sm:text-5xl">
                        {{ $isActive ? 'Gerencie ou renove seu plano.' : 'Escolha seu plano e finalize a cobrança.' }}
                    </h1>
                    <p class="mt-5 max-w-2xl text-sm leading-7 text-gray-600 dark:text-gray-300 sm:text-base">
                        Mensal para flexibilidade. Semestral e anual para economia.
                    </p>
                </div>
            </div>
        </section>

        {{-- ── Gerenciamento de assinatura ─────────────────────────────────── --}}
        @if($isActive)
            @if($showCancelConfirm)
                <section class="overflow-hidden rounded-[30px] border border-rose-200/80 bg-rose-50/80 px-6 py-8 dark:border-rose-500/20 dark:bg-rose-500/5 sm:px-8">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                        <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-rose-100 dark:bg-rose-500/15">
                            <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-rose-600 dark:text-rose-400" />
                        </span>
                        <div class="flex-1">
                            <h3 class="text-base font-black text-gray-900 dark:text-white">Cancelar assinatura?</h3>
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                Ao cancelar, você <strong>mantém o acesso completo até {{ $barbershop->subscription_expires_at?->format('d/m/Y') }}</strong>.
                                Após essa data, o acesso será suspenso e será necessário assinar novamente.
                            </p>
                            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                                <button
                                    wire:click="cancelSubscription"
                                    wire:loading.attr="disabled"
                                    wire:target="cancelSubscription"
                                    class="inline-flex items-center justify-center rounded-2xl bg-rose-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:opacity-60"
                                >
                                    <span wire:loading.remove wire:target="cancelSubscription">Confirmar cancelamento</span>
                                    <span wire:loading wire:target="cancelSubscription" class="flex items-center gap-2">
                                        <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" /> Cancelando...
                                    </span>
                                </button>
                                <button
                                    wire:click="dismissCancel"
                                    class="inline-flex items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition hover:border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-gray-200"
                                >
                                    Manter assinatura
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            @else
                <section class="overflow-hidden rounded-[30px] border border-gray-200/80 bg-white/95 px-6 py-6 shadow-sm dark:border-white/10 dark:bg-gray-950/75 sm:px-8">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-gray-500">Minha assinatura</p>
                            <h3 class="mt-2 text-lg font-black tracking-[-0.02em] text-gray-900 dark:text-white">
                                Plano {{ $barbershop->subscription_plan === 'Basico' ? 'Básico' : $barbershop->subscription_plan }}
                            </h3>
                            <div class="mt-3 flex flex-wrap gap-6">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-gray-400">Acesso garantido até</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $barbershop->subscription_expires_at?->format('d/m/Y') }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-gray-400">Status</p>
                                    <p class="mt-1 text-sm font-semibold {{ $barbershop->subscription_status === 'cancelled' ? 'text-orange-600 dark:text-orange-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ $barbershop->subscription_status === 'cancelled' ? 'Cancelada · sem renovação automática' : 'Ativa' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        @if($barbershop->subscription_status !== 'cancelled')
                            <button
                                wire:click="confirmCancel"
                                class="inline-flex shrink-0 items-center justify-center rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm font-semibold text-rose-600 transition hover:border-rose-300 hover:bg-rose-50 dark:border-rose-500/30 dark:bg-transparent dark:text-rose-400 dark:hover:bg-rose-500/10"
                            >
                                Cancelar assinatura
                            </button>
                        @else
                            <p class="shrink-0 text-sm font-medium text-orange-600 dark:text-orange-400">
                                Assine novamente abaixo para renovar o acesso.
                            </p>
                        @endif
                    </div>
                </section>
            @endif
        @endif
        {{-- ──────────────────────────────────────────────────────────────── --}}

        @if($paymentId && $pixCopyPaste)
            @php
                $selectedPlan = $this->plans->find($selectedPlanId);
                $selectedPlanDisplayName = $selectedPlan?->name === 'Basico' ? 'Básico' : $selectedPlan?->name;
            @endphp
            <div class="grid gap-6 xl:grid-cols-[0.82fr_1.18fr] xl:items-start">
                <aside class="space-y-6 xl:sticky xl:top-6">
                    <div class="overflow-hidden rounded-[30px] border border-gray-200/80 bg-gray-950 text-white shadow-[0_18px_60px_-28px_rgba(15,23,42,0.55)] dark:border-white/10">
                        <div class="bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.26),transparent_34%),linear-gradient(180deg,rgba(255,255,255,0.04),rgba(255,255,255,0))] px-6 py-7 sm:px-7">
                            <span class="inline-flex items-center rounded-full border border-white/15 bg-white/5 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.22em] text-amber-200/90">
                                Cobrança em andamento
                            </span>
                            <h2 class="mt-4 text-3xl font-black tracking-[-0.03em] text-white">{{ $selectedPlanDisplayName }}</h2>
                            <p class="mt-3 text-sm leading-7 text-white/70">
                                Finalize agora para liberar sua assinatura.
                            </p>

                            <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/45">Ciclo</p>
                                    <p class="mt-2 text-base font-semibold text-white">{{ $selectedPlan?->billing_cycle_label }}</p>
                                    <p class="mt-1 text-sm text-white/65">{{ $selectedPlan?->billing_cycle_description }}</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/45">Valor</p>
                                    <p class="mt-2 text-base font-semibold text-white">R$ {{ number_format((float) $selectedPlan?->price, 2, ',', '.') }}</p>
                                    <p class="mt-1 text-sm text-white/65">Ativação automática após confirmação.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[28px] border border-gray-200/80 bg-white/95 p-6 shadow-sm dark:border-white/10 dark:bg-gray-950/70">
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-gray-500">Fluxo</p>
                        <div class="mt-5 space-y-4">
                            <div class="flex gap-3">
                                <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-sm font-bold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">1</span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Escolha cartão ou PIX</p>
                                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">Cartão em destaque, PIX disponível.</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-sm font-bold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">2</span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Confirme o pagamento</p>
                                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">A ativação é automática.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <section class="overflow-hidden rounded-[30px] border border-gray-200/80 bg-white/95 shadow-[0_20px_60px_-34px_rgba(15,23,42,0.3)] dark:border-white/10 dark:bg-gray-950/75">
                    <div class="border-b border-gray-200/80 px-6 py-6 md:px-8 dark:border-white/10">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-300">Checkout da assinatura</p>
                                <h2 class="mt-3 text-2xl font-black tracking-[-0.03em] text-gray-950 dark:text-white sm:text-3xl">Finalize sua cobrança</h2>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                                    Valor do ciclo {{ $selectedPlan?->billing_cycle_label }}: <span class="font-bold text-gray-950 dark:text-white">R$ {{ number_format((float) $selectedPlan?->price, 2, ',', '.') }}</span>
                                </p>
                            </div>
                            <button wire:click="cancelPix" class="inline-flex items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-amber-300 hover:text-amber-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:border-amber-500/40 dark:hover:text-amber-300">
                                Trocar plano
                            </button>
                        </div>
                    </div>

                    <div class="p-6 md:p-8">
                        <div class="grid gap-5 lg:grid-cols-2">
                            <div class="rounded-[26px] border border-gray-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,1),rgba(248,250,252,0.9))] p-6 dark:border-white/10 dark:bg-white/5">
                                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-500">Recomendado</p>
                                <h3 class="mt-3 text-xl font-black tracking-[-0.02em] text-gray-900 dark:text-white">Pagar com cartão</h3>
                                <p class="mt-3 text-sm leading-6 text-gray-700 dark:text-gray-300">
                                    Crédito ou débito. Ativação imediata após confirmação.
                                </p>

                                {{-- Bandeiras aceitas --}}
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    @foreach(['VISA', 'MASTER', 'ELO', 'AMEX', 'HIPERCARD'] as $brand)
                                        <span class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-[10px] font-bold tracking-wide text-gray-600 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                                            {{ $brand }}
                                        </span>
                                    @endforeach
                                </div>

                                <div class="mt-4 rounded-2xl border border-blue-100 bg-blue-50/80 px-4 py-2.5 text-xs text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
                                    Não é necessário ter conta ou saldo no Mercado Pago.
                                </div>

                                <div class="mt-4">
                                    <button
                                        wire:click="initiateCardPayment({{ $selectedPlanId }})"
                                        wire:loading.attr="disabled"
                                        wire:target="initiateCardPayment({{ $selectedPlanId }})"
                                        class="inline-flex w-full items-center justify-center rounded-2xl bg-gray-950 px-5 py-4 text-sm font-bold text-white transition hover:bg-gray-800 disabled:opacity-60 dark:bg-white dark:text-gray-950 dark:hover:bg-gray-100"
                                    >
                                        <span wire:loading.remove wire:target="initiateCardPayment({{ $selectedPlanId }})">Pagar com cartão</span>
                                        <span wire:loading wire:target="initiateCardPayment({{ $selectedPlanId }})" class="flex items-center gap-2">
                                            <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" /> Abrindo...
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-[26px] border border-amber-200/80 bg-[linear-gradient(180deg,rgba(255,251,235,0.65),rgba(255,255,255,0.96))] p-6 dark:border-amber-500/20 dark:bg-[linear-gradient(180deg,rgba(245,158,11,0.08),rgba(255,255,255,0.02))]">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-300">Alternativa</p>
                                        <h3 class="mt-3 text-xl font-black tracking-[-0.02em] text-gray-900 dark:text-white">Pagar com PIX</h3>
                                        <p class="mt-3 text-sm leading-6 text-gray-700 dark:text-gray-300">
                                            QR Code e copia e cola na mesma tela.
                                        </p>
                                    </div>
                                    @if($showPixDetails)
                                        <button wire:click="hidePixDetails" class="text-sm font-semibold text-gray-700 underline decoration-gray-300 underline-offset-4 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                                            Ocultar PIX
                                        </button>
                                    @else
                                        <button wire:click="revealPixDetails" class="inline-flex items-center justify-center rounded-2xl border border-amber-300 bg-white px-4 py-3 text-sm font-bold text-amber-700 transition hover:border-amber-400 hover:bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/15">
                                            Mostrar QR Code
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($showPixDetails)
                            <div class="mt-6 grid gap-6 border-t border-gray-200 pt-6 dark:border-white/10 lg:grid-cols-[minmax(280px,0.72fr)_minmax(0,1fr)] lg:items-start">
                                <div class="flex flex-col items-center gap-4 rounded-[28px] bg-[linear-gradient(180deg,#fff7ed,#ffffff)] p-6 shadow-inner shadow-amber-100/60 dark:bg-transparent dark:shadow-none">
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-400">
                                        Escaneie com seu app de banco
                                    </p>
                                    @if($pixQrCode)
                                        <div class="rounded-[28px] border-4 border-amber-300 bg-white p-3 shadow-xl shadow-amber-100/60 dark:border-amber-400 dark:shadow-amber-900/20">
                                            <img
                                                src="data:image/png;base64,{{ $pixQrCode }}"
                                                alt="QR Code PIX"
                                                class="h-52 w-52"
                                            >
                                        </div>
                                    @endif
                                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-white/80 px-3 py-1.5 text-xs text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                                        <x-heroicon-o-arrow-path class="h-3 w-3 animate-spin opacity-60" />
                                        <span wire:poll.5000ms>Aguardando confirmação...</span>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="rounded-[28px] border border-gray-200 bg-white/90 p-5 shadow-lg shadow-gray-200/60 dark:border-white/10 dark:bg-white/5">
                                        <label class="mb-3 block text-[11px] font-bold uppercase tracking-[0.2em] text-gray-700 dark:text-gray-400">
                                            Pix Copia e Cola
                                        </label>
                                        <textarea
                                            readonly
                                            rows="5"
                                            onclick="this.select()"
                                            class="w-full rounded-2xl border border-gray-300 bg-gray-50 px-4 py-3 text-xs text-gray-800 shadow-inner shadow-gray-200/40 resize-none font-mono dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                        >{{ $pixCopyPaste }}</textarea>

                                        <button
                                            wire:click="markCopied"
                                            onclick="navigator.clipboard.writeText('{{ $pixCopyPaste }}').then(() => {})"
                                            class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold transition-all
                                                {{ $copied
                                                    ? 'border border-green-300 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                    : 'bg-amber-500 text-white shadow-md hover:bg-amber-600 hover:shadow-lg' }}"
                                        >
                                            @if($copied)
                                                <x-heroicon-o-check class="h-4 w-4" />
                                                Copiado!
                                            @else
                                                <x-heroicon-o-clipboard-document class="h-4 w-4" />
                                                Copiar código PIX
                                            @endif
                                        </button>
                                    </div>

                                    <div class="rounded-[28px] border border-blue-200 bg-blue-50 p-5 dark:border-blue-800 dark:bg-blue-900/20">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-blue-700 dark:text-blue-400">Como pagar</p>
                                        <ol class="mt-4 space-y-3 text-sm text-blue-700 dark:text-blue-300">
                                            <li class="flex gap-3">
                                                <span class="inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">1</span>
                                                Abra seu app do banco.
                                            </li>
                                            <li class="flex gap-3">
                                                <span class="inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">2</span>
                                                Escaneie o QR Code ou cole o código.
                                            </li>
                                            <li class="flex gap-3">
                                                <span class="inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">3</span>
                                                Confirme <strong>R$ {{ number_format((float) $selectedPlan?->price, 2, ',', '.') }}</strong> do plano {{ $selectedPlan?->billing_cycle_label }}.
                                            </li>
                                        </ol>
                                        <p class="mt-4 text-sm font-semibold text-blue-700 dark:text-blue-300">
                                            Ativação automática após confirmação.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        @elseif($showCardForm && $selectedPlanId)
            {{-- ── Checkout Bricks inline (sem redirecionamento, sem login MP) ──── --}}
            @php $brickPlan = $this->plans->find($selectedPlanId); @endphp
            <div class="grid gap-6 xl:grid-cols-[0.82fr_1.18fr] xl:items-start">

                <aside class="space-y-4 xl:sticky xl:top-6">
                    <div class="overflow-hidden rounded-[30px] border border-gray-200/80 bg-gray-950 text-white shadow-[0_18px_60px_-28px_rgba(15,23,42,0.55)] dark:border-white/10">
                        <div class="bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.26),transparent_34%),linear-gradient(180deg,rgba(255,255,255,0.04),rgba(255,255,255,0))] px-6 py-7 sm:px-7">
                            <span class="inline-flex items-center rounded-full border border-white/15 bg-white/5 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.22em] text-amber-200/90">
                                Plano selecionado
                            </span>
                            <h2 class="mt-4 text-3xl font-black tracking-[-0.03em] text-white">
                                {{ $brickPlan?->name === 'Basico' ? 'Básico' : $brickPlan?->name }}
                            </h2>
                            <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/45">Ciclo</p>
                                    <p class="mt-2 text-base font-semibold text-white">{{ $brickPlan?->billing_cycle_label }}</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/45">Valor cobrado</p>
                                    <p class="mt-2 text-base font-semibold text-white">R$ {{ number_format((float) $brickPlan?->price, 2, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button
                        wire:click="cancelCardForm"
                        class="inline-flex w-full items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-amber-300 hover:text-amber-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:border-amber-500/40 dark:hover:text-amber-300"
                    >
                        ← Trocar plano
                    </button>
                </aside>

                <section class="overflow-hidden rounded-[30px] border border-gray-200/80 bg-white/95 shadow-[0_20px_60px_-34px_rgba(15,23,42,0.3)] dark:border-white/10 dark:bg-gray-950/75">
                    <div class="border-b border-gray-200/80 px-6 py-6 dark:border-white/10 md:px-8">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-300">Pagamento seguro</p>
                        <h2 class="mt-3 text-2xl font-black tracking-[-0.03em] text-gray-950 dark:text-white">Dados do cartão</h2>
                        <div class="mt-3 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50/80 px-3 py-1.5 text-xs text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
                            <x-heroicon-o-lock-closed class="h-3 w-3" />
                            Não é necessário ter conta ou saldo no Mercado Pago
                        </div>
                    </div>
                    <div class="p-6 md:p-8">
                        {{-- Brick container — wire:ignore impede o Livewire de apagar o form do SDK --}}
                        @php $mpPublicKey = config('services.mercadopago.public_key'); @endphp

                        @if(empty($mpPublicKey))
                            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 dark:border-rose-500/30 dark:bg-rose-500/10">
                                <p class="font-bold text-rose-700 dark:text-rose-400">Configuração incompleta</p>
                                <p class="mt-1 text-sm text-rose-600 dark:text-rose-300">
                                    A variável <code class="rounded bg-rose-100 px-1 dark:bg-rose-500/20">MERCADOPAGO_PUBLIC_KEY</code>
                                    não está definida no servidor. Adicione-a em
                                    <strong>Forge → Environment</strong> e faça um novo deploy.
                                </p>
                            </div>
                        @else
                        <div
                            x-data="{
                                controller: null,
                                brickError: null,
                                async init() {
                                    try {
                                        await this.loadSDK();
                                        if (this.controller) { this.controller.unmount(); this.controller = null; }
                                        const mp = new MercadoPago({{ Js::from($mpPublicKey) }}, { locale: 'pt-BR' });
                                        const builder = mp.bricks();
                                        this.controller = await builder.create('cardPayment', 'saas-card-brick', {
                                            initialization: {
                                                amount: {{ Js::from((float) ($brickPlan?->price ?? 0)) }},
                                            },
                                            customization: {
                                                visual: { hideFormTitle: true },
                                                paymentMethods: { maxInstallments: 12 },
                                            },
                                            callbacks: {
                                                onSubmit: async (formData) => {
                                                    return new Promise((resolve, reject) => {
                                                        $wire.processCardPayment({{ $selectedPlanId }}, formData)
                                                            .then(() => resolve())
                                                            .catch((e) => reject(e));
                                                    });
                                                },
                                                onError: (error) => {
                                                    console.error('MP Brick error:', error);
                                                    this.brickError = 'Erro ao carregar o formulário de cartão: ' + (error.message ?? JSON.stringify(error));
                                                },
                                                onReady: () => { this.brickError = null; },
                                            },
                                        });
                                    } catch (e) {
                                        console.error('Brick init failed:', e);
                                        this.brickError = 'Não foi possível carregar o formulário. Verifique sua conexão e recarregue a página.';
                                    }
                                },
                                loadSDK() {
                                    return new Promise((resolve, reject) => {
                                        if (window.MercadoPago) { resolve(); return; }
                                        const s = document.createElement('script');
                                        s.src = 'https://sdk.mercadopago.com/js/v2';
                                        s.onload = resolve;
                                        s.onerror = () => reject(new Error('Falha ao carregar o SDK do Mercado Pago'));
                                        document.head.appendChild(s);
                                    });
                                },
                            }"
                            x-init="init()"
                            wire:ignore
                        >
                            <template x-if="brickError">
                                <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300" x-text="brickError"></div>
                            </template>
                            <div id="saas-card-brick"></div>
                        </div>
                        @endif
                    </div>
                </section>
            </div>

        @else
            <div class="grid gap-6 xl:grid-cols-3">
                @foreach($this->plans as $plan)
                    @php
                        $isCurrentPlan = $isActive && $barbershop->subscription_plan === $plan->name;
                        $referenceTotal = $monthlyReferencePlan ? (float) $monthlyReferencePlan->price * max(1, $plan->billing_cycle_months) : null;
                        $savingsAmount = $referenceTotal && $plan->billing_cycle_months > 1 ? max(0, $referenceTotal - (float) $plan->price) : 0;
                        $savingsPercent = $referenceTotal && $savingsAmount > 0 ? round(($savingsAmount / $referenceTotal) * 100) : 0;
                        $displayName = $plan->name === 'Basico' ? 'Básico' : $plan->name;
                    @endphp
                    <div class="group relative flex flex-col overflow-hidden rounded-[30px] border {{ $plan->is_popular ? 'border-amber-300/90 bg-[linear-gradient(180deg,rgba(255,251,235,0.96),rgba(255,255,255,0.98))] shadow-[0_22px_60px_-32px_rgba(217,119,6,0.32)] dark:border-amber-500/30 dark:bg-[linear-gradient(180deg,rgba(245,158,11,0.08),rgba(255,255,255,0.02))]' : 'border-gray-200/80 bg-white/95 shadow-[0_18px_50px_-34px_rgba(15,23,42,0.22)] dark:border-white/10 dark:bg-gray-950/75' }} p-6 sm:p-7">
                        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(245,158,11,0.12),_transparent_35%)] opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

                        <div class="relative mb-8">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-500">Plano {{ $plan->billing_cycle_label }}</p>
                                    <h3 class="mt-3 text-2xl font-black tracking-[-0.03em] text-gray-900 dark:text-white">{{ $displayName }}</h3>
                                    <p class="mt-4 max-w-xs text-sm leading-7 text-gray-600 dark:text-gray-300 line-clamp-3">{{ $plan->description }}</p>
                                </div>
                                @if($plan->is_popular)
                                    <span class="inline-flex shrink-0 items-center rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                                        Mais popular
                                    </span>
                                @endif
                            </div>
                            <p class="mt-6 text-xs font-semibold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">
                                {{ $plan->billing_cycle_description }}
                            </p>
                        </div>

                        <div class="relative mb-8 rounded-[24px] border border-gray-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,1),rgba(248,250,252,0.92))] p-6 dark:border-white/10 dark:bg-white/5">
                            <div class="flex items-end gap-2">
                                <span class="text-4xl font-black leading-none tracking-tight text-gray-900 dark:text-white">
                                    R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                                </span>
                                <span class="mb-1 text-sm font-medium text-gray-600 dark:text-gray-500">/{{ $plan->billing_cycle_label }}</span>
                            </div>
                            @if($plan->billing_cycle_months > 1)
                                <div class="mt-5 rounded-2xl border border-green-200 bg-green-50/85 px-4 py-3 dark:border-green-500/20 dark:bg-green-500/10">
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                        Equivale a <span class="font-bold text-gray-900 dark:text-white">R$ {{ number_format((float) $plan->monthly_equivalent, 2, ',', '.') }}/mês</span>
                                    </p>
                                    <p class="mt-1 text-sm font-bold text-green-700 dark:text-green-400">
                                        Economize {{ $savingsPercent }}% e preserve R$ {{ number_format((float) $savingsAmount, 2, ',', '.') }} no ciclo
                                    </p>
                                </div>
                            @else
                                <span class="mt-5 inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                                    Flexibilidade total para pagar mês a mês
                                </span>
                            @endif
                        </div>

                        <ul class="relative mb-10 flex-1 space-y-4">
                            @foreach((array) $plan->features as $feature)
                                <li class="flex items-start gap-3 text-sm leading-6 text-gray-700 dark:text-gray-300">
                                    <span class="mt-0.5 inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-400">
                                        <x-heroicon-o-check class="h-3.5 w-3.5" />
                                    </span>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        @if($isCurrentPlan)
                            <div class="w-full rounded-2xl border border-green-300 bg-green-50 px-4 py-4 text-center text-sm font-semibold text-green-700 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400">
                                Plano atual
                            </div>
                        @else
                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                                <button
                                    wire:click="initiateCardPayment({{ $plan->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="initiateCardPayment({{ $plan->id }})"
                                    class="w-full rounded-2xl px-5 py-4 text-sm font-semibold leading-6 transition-all
                                        {{ $plan->is_popular
                                            ? 'bg-amber-500 hover:bg-amber-600 text-white disabled:opacity-60'
                                            : 'bg-gray-950 hover:bg-gray-800 dark:bg-white dark:hover:bg-gray-100 text-white dark:text-gray-950 disabled:opacity-60' }}"
                                >
                                    <span wire:loading.remove wire:target="initiateCardPayment({{ $plan->id }})">
                                        Cartão de crédito / débito
                                    </span>
                                    <span wire:loading wire:target="initiateCardPayment({{ $plan->id }})" class="flex items-center justify-center gap-2">
                                        <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" />
                                        Carregando...
                                    </span>
                                </button>

                                <button
                                    wire:click="selectPlan({{ $plan->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="selectPlan({{ $plan->id }})"
                                    class="w-full rounded-2xl border border-gray-200 bg-white px-5 py-4 text-sm font-semibold text-gray-700 transition-all hover:border-amber-300 hover:text-amber-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:border-amber-500/40 dark:hover:text-amber-300"
                                >
                                    <span wire:loading.remove wire:target="selectPlan({{ $plan->id }})">
                                        Gerar PIX
                                    </span>
                                    <span wire:loading wire:target="selectPlan({{ $plan->id }})" class="flex items-center justify-center gap-2">
                                        <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" />
                                        Gerando PIX...
                                    </span>
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col items-stretch justify-center gap-3 text-sm text-gray-500 dark:text-gray-400 sm:flex-row sm:items-center">
                <div class="flex items-center justify-center gap-2 rounded-full border border-gray-200/80 bg-white/90 px-4 py-2 dark:border-white/10 dark:bg-white/5">
                    <x-heroicon-o-lock-closed class="h-4 w-4 text-gray-400" />
                    Pagamento seguro via PIX
                </div>
                <div class="flex items-center justify-center gap-2 rounded-full border border-gray-200/80 bg-white/90 px-4 py-2 dark:border-white/10 dark:bg-white/5">
                    <x-heroicon-o-shield-check class="h-4 w-4 text-gray-400" />
                    Ativação automática
                </div>
                <div class="flex items-center justify-center gap-2 rounded-full border border-gray-200/80 bg-white/90 px-4 py-2 dark:border-white/10 dark:bg-white/5">
                    <x-heroicon-o-arrow-uturn-left class="h-4 w-4 text-gray-400" />
                    Cancele quando quiser
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
