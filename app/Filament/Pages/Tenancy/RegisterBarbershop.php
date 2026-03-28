<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Barbershop;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Str;

class RegisterBarbershop extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Criar sua Barbearia';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nome da Barbearia')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->label('Link único (slug)')
                    ->required()
                    ->unique(Barbershop::class, 'slug')
                    ->alphaDash()
                    ->helperText('Exemplo: barbearia-do-joao — será usado no link de agendamento público.'),

                TextInput::make('phone')
                    ->label('WhatsApp')
                    ->mask('(99) 99999-9999')
                    ->tel(),

                TextInput::make('address')
                    ->label('Endereço'),

                FileUpload::make('logo_path')
                    ->label('Logo da Barbearia')
                    ->image()
                    ->directory('barbershops-logos'),
            ]);
    }

    /**
     * Cria a barbearia, associa ao usuário e define o período trial.
     * Também define o role do usuário como 'barber' automaticamente.
     */
    protected function handleRegistration(array $data): Barbershop
    {
        $user = auth()->user();
        $userTrialEndsAt = $user->trial_ends_at;

        if (! $userTrialEndsAt) {
            $userTrialEndsAt = now()->addDays(14);

            $user->update([
                'trial_ends_at' => $userTrialEndsAt,
            ]);
        }

        $hasActiveSharedTrial = $userTrialEndsAt->isFuture();

        $barbershop = Barbershop::create([
            ...$data,
            'user_id'                  => auth()->id(),
            'subscription_status'      => $hasActiveSharedTrial ? 'trial' : 'expired',
            'trial_ends_at'            => $userTrialEndsAt,
            'subscription_expires_at'  => null,
        ]);

        // Define automaticamente o role para 'barber' ao criar uma barbearia
        $user->update([
            'role'          => 'barber',
            'barbershop_id' => $barbershop->id,
        ]);

        return $barbershop;
    }
}
