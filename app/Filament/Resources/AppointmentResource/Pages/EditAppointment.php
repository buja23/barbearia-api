<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Service;
use Carbon\Carbon;
use Filament\Facades\Filament;
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

    protected function mutateFormDataBeforeSave(array $data): array{
        $barber = Barber::query()->find($data['barber_id'] ?? null);
        $service = Service::query()->find($data['service_id'] ?? null);
        $tenantId = Filament::getTenant()?->id;

        if (
            ! $barber
            || ! $service
            || ($tenantId && $barber->barbershop_id !== $tenantId)
            || $barber->barbershop_id !== $service->barbershop_id
        ) {
            Notification::make()
                ->title('Serviço inválido para esta barbearia')
                ->body('Selecione um serviço da mesma barbearia do barbeiro escolhido.')
                ->danger()
                ->send();

            $this->halt();
        }

        if($service && isset($service->duration_minutes)){
            $data['end_at'] = Carbon::parse($data['scheduled_at']) -> addMinutes($service->duration_minutes);
        }

        $data['barbershop_id'] = $barber->barbershop_id;

        // 2 Verifica conflito de horario do barbeiro
        $hasConflict = Appointment::where('barber_id', $data['barber_id'])
            ->where('id', '!=', $this->record->id)
            ->where('status', '!=', 'canceled')
            ->where(function($query) use ($data){
            $query -> whereBetween('scheduled_at', [$data['scheduled_at'], $data['end_at']]) -> orWhereBetween('end_at', [$data['scheduled_at'], $data['end_at']]);
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
