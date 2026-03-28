<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReportReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Report $report) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $typeLabel   = $this->report->getTypeLabel();
        $barbershop  = $this->report->barbershop?->name ?? 'Sistema';
        $user        = $this->report->user?->name ?? 'Usuário';
        $adminUrl    = url('/admin/reports/' . $this->report->id);

        return (new MailMessage)
            ->subject("{$typeLabel} — Novo reporte recebido")
            ->greeting("Novo reporte recebido!")
            ->line("**Tipo:** {$typeLabel}")
            ->line("**Barbearia:** {$barbershop}")
            ->line("**Enviado por:** {$user}")
            ->line("**Título:** {$this->report->title}")
            ->line("**Descrição:**")
            ->line($this->report->description)
            ->action('Ver no painel', $adminUrl)
            ->line('Acesse o painel para responder ou alterar o status.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'report_id'   => $this->report->id,
            'type'        => $this->report->type,
            'title'       => $this->report->title,
            'barbershop'  => $this->report->barbershop?->name,
            'user'        => $this->report->user?->name,
            'message'     => "{$this->report->getTypeLabel()} de {$this->report->user?->name}: {$this->report->title}",
        ];
    }
}
