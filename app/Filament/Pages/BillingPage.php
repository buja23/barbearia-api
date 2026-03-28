<?php

namespace App\Filament\Pages;

use App\Models\SaasPlan;
use App\Services\PaymentService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class BillingPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Plano & Cobrança';
    protected static ?string $title           = 'Plano & Cobrança';
    protected static ?string $slug            = 'billing';
    protected static ?int    $navigationSort  = 99;
    protected static string  $view            = 'filament.pages.billing';

    // --- Livewire state para o checkout ---
    public ?int    $selectedPlanId  = null;
    public ?string $pixQrCode       = null; // base64 image
    public ?string $pixCopyPaste    = null; // copy-paste text
    public ?string $paymentId       = null;
    public bool    $copied          = false;
    public bool    $showPixDetails  = false;

    public function mount(): void
    {
        // Se já há um PIX pendente salvo, restaura o estado para o usuário não perder o QR
        $barbershop = $this->getBarbershop();
        if ($barbershop->saas_payment_id && !$barbershop->isSubscriptionActive()) {
            $this->paymentId     = $barbershop->saas_payment_id;
            $this->pixCopyPaste  = $barbershop->saas_pix_copy_paste;
            $this->pixQrCode     = $barbershop->saas_pix_qr_code;
            $this->selectedPlanId = $barbershop->saas_plan_id;
            $this->showPixDetails = true;
        }
    }

    public function getBarbershop(): \App\Models\Barbershop
    {
        return Filament::getTenant();
    }

    public function getPlansProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return SaasPlan::where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Usuário clicou em um plano → gera PIX e exibe QR Code na página.
     */
    public function selectPlan(int $planId): void
    {
        $barbershop = $this->getBarbershop();
        $plan       = SaasPlan::findOrFail($planId);

        $paymentService = new PaymentService();
        $result = $paymentService->createSaasPix($barbershop, $plan);

        if ($result['success']) {
            $this->selectedPlanId = $planId;
            $this->pixQrCode      = $result['qr_code_base64'];
            $this->pixCopyPaste   = $result['qr_code'];
            $this->paymentId      = $result['payment_id'];
            $this->copied         = false;
            $this->showPixDetails = true;

            Notification::make()
                ->title('PIX gerado!')
                ->body('Escaneie o QR Code ou use o Pix Copia e Cola para ativar seu plano.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Erro ao gerar PIX')
                ->body($result['error'])
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function checkoutWithCard(int $planId)
    {
        $barbershop = $this->getBarbershop();
        $plan = SaasPlan::findOrFail($planId);

        $paymentService = new PaymentService();
        $result = $paymentService->createSaasCardCheckout($barbershop, $plan);

        if (!$result['success']) {
            Notification::make()
                ->title('Erro ao iniciar checkout')
                ->body($result['error'])
                ->danger()
                ->persistent()
                ->send();

            return null;
        }

        return redirect()->away($result['checkout_url']);
    }

    /**
     * Polling automático: verifica se o pagamento foi confirmado.
     * Chamado via wire:poll a cada 5 segundos.
     */
    public function checkPayment(): void
    {
        if (!$this->paymentId) {
            return;
        }

        $barbershop = $this->getBarbershop();
        $barbershop->refresh();

        if ($barbershop->isSubscriptionActive()) {
            // Limpa estado do Livewire
            $this->paymentId    = null;
            $this->pixQrCode    = null;
            $this->pixCopyPaste = null;

            Notification::make()
                ->title('🎉 Pagamento confirmado!')
                ->body('Bem-vindo! Sua barbearia está ativa. Vamos ao painel!')
                ->success()
                ->persistent()
                ->send();

            $this->redirect(
                route('filament.admin.pages.dashboard', ['tenant' => $barbershop->slug])
            );
        }
    }

    /**
     * Cancela o PIX atual e permite escolher outro plano.
     */
    public function cancelPix(): void
    {
        $this->selectedPlanId = null;
        $this->pixQrCode      = null;
        $this->pixCopyPaste   = null;
        $this->paymentId      = null;
        $this->copied         = false;
        $this->showPixDetails = false;
    }

    public function revealPixDetails(): void
    {
        $this->showPixDetails = true;
    }

    public function hidePixDetails(): void
    {
        $this->showPixDetails = false;
    }

    /**
     * Marca como copiado (feedback visual do botão).
     */
    public function markCopied(): void
    {
        $this->copied = true;
    }
}
