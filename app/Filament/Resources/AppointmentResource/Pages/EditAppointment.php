<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array{
        // 1 Busca o serviço para pegar a duração
        $service = Service::find($data['service_id']);

        if($service && isset($service->duration_minutes)){
            $data['end_at'] = Carbon::parse($data['schedule_at']) -> addMinutes($service->duration_minutes);
        }

        // 2 Verifica conflito de horario do barbeiro
        $hasConflict = Appointment::where('barber_id', $data['barber_id']) -> where('status', '!=', 'cancelled') -> where(function($query) use ($data){
            $query -> whereBetween('scheduled_at', [$data['schedule_at'], $data['end_at']]) -> orWhereBetween('end_at', [$data['scheduled_at'], $data['end_at']]);
        })
        -> exists();

        if ($hasConflict) {
            Notification::make()
                ->title('Conflito de horário')
                ->body('Este barbeiro já possui um agendamento nesse período.')
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }
}
