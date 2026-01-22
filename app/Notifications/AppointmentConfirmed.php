<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // Importante para não travar o sistema
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public $appointment;

    /**
     * Recebe o agendamento para pegar os dados (Data, Hora, Barbeiro)
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    /**
     * Define os canais (Email, Banco de Dados, futuramente WhatsApp)
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Monta o E-mail Bonito
     */
    public function toMail(object $notifiable): MailMessage
    {
        $date = $this->appointment->scheduled_at->format('d/m/Y');
        $time = $this->appointment->scheduled_at->format('H:i');
        $barber = $this->appointment->barber->name ?? 'Barbeiro';

        return (new MailMessage)
            ->subject('✅ Agendamento Confirmado! - Barbearia System')
            ->greeting("Olá, {$this->appointment->client_name}!")
            ->line('Seu pagamento foi recebido e seu horário está garantido.')
            ->line("📅 Data: **{$date}**")
            ->line("⏰ Horário: **{$time}**")
            ->line("✂️ Profissional: **{$barber}**")
            ->action('Ver Agendamento', url('/admin/appointments')) // Link para o painel
            ->line('Chegue com 10 minutos de antecedência. Até lá!');
    }

    /**
     * Salva no Banco de Dados (Para aparecer no "sininho" do painel depois)
     */
    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'message' => "Agendamento confirmado para {$this->appointment->scheduled_at->format('d/m H:i')}",
        ];
    }
}