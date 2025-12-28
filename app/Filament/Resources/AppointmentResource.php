<?php
namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Service;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;


class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('barber_id')
                    ->relationship('barber', 'name')
                    ->required()
                    ->label('Barbeiro'),

                Select::make('service_id')
                    ->relationship('service', 'name')
                    ->required()
                    ->live() // IMPORTANTE: Torna o campo reativo
                    ->afterStateUpdated(function ($state, Set $set) {
                        // Quando escolhe o serviço, busca o preço dele e preenche o campo 'total_price'
                        $service = Service::find($state);
                        if ($service) {
                            $set('total_price', $service->price);
                        }
                    })
                    ->label('Serviço'),

                DateTimePicker::make('scheduled_at')
                    ->required()
                    ->seconds(false)
                    ->label('Data e Hora'),

                TextInput::make('total_price')
                    ->numeric()
                    ->prefix('R$')
                    ->readOnly() // O dono não edita o preço na mão (ou remove isso se quiser permitir desconto)
                    ->required()
                    ->label('Valor'),

                Select::make('status')
                    ->options([
                        'pending'   => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                    ])
                    ->default('confirmed')
                    ->required(),

                // Seção Cliente
                TextInput::make('client_name')
                    ->label('Nome do Cliente (Avulso)')
                    ->placeholder('Ex: João da Silva'),

                TextInput::make('client_phone')
                    ->label('Telefone')
                    ->mask('(99) 99999-9999'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 1. Cliente (Pega direto da coluna client_name)
                // Se você quiser pegar do usuário logado, troque por 'user.name'
                TextColumn::make('cliente')
                    ->label('Cliente')
                    ->getStateUsing(fn ($record) =>
                        $record->client_name ?? $record->user?->name
                    )
                    ->searchable(),


                // 2. Barbeiro (Pega a relação 'barber' e o campo 'name' dele)
                // Certifique-se que na tabela 'barbers' a coluna do nome é 'name'
                TextColumn::make('barber.name')
                    ->label('Barbeiro')
                    ->sortable(),

                // 3. Serviço (Pega a relação 'service' e o campo 'name' dele)
                TextColumn::make('service.name')
                    ->label('Serviço'),

                // 4. Data e Hora (O seu campo é 'scheduled_at')
                TextColumn::make('scheduled_at')
                    ->label('Data e Hora')
                    ->dateTime('d/m/Y H:i') // Formatação brasileira
                    ->sortable(),

                // 5. Preço Total
                TextColumn::make('total_price')
                    ->label('Preço')
                    ->money('BRL'), // Formata como R$

                // 6. Status
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'confirmed' => 'success',
                        'completed' => 'primary',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),

            ])
            ->defaultSort('scheduled_at', 'desc') // Ordena do mais recente para o antigo
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit'   => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
