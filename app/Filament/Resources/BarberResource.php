<?php
namespace App\Filament\Resources;

use App\Filament\Resources\BarberResource\Pages;
use App\Models\Barber;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BarberResource extends Resource
{
    protected static ?string $model = Barber::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Barbeiros';

    protected static ?string $modelLabel = 'Barbeiro';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dados do Profissional')
                    ->schema([
                        Select::make('barbershop_id')
                            ->relationship('barbershop', 'name')
                            ->required()
                            ->label('Barbearia'),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Nome Completo'),

                        TextInput::make('phone')
                            ->tel()
                            ->mask('(99) 99999-9999')
                            ->label('Celular'),

                        TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->label('E-mail'),

                        FileUpload::make('avatar')
                            ->image()
                            ->directory('barbers-avatars')
                            ->label('Foto do Perfil'),

                        Toggle::make('is_active')
                            ->label('Ativo para agendamentos?')
                            ->default(true),

                        Section::make('Horário de Pausa/Almoço')
                            ->description('Defina o intervalo em que este profissional não estará disponível.')
                            ->schema([
                                TimePicker::make('lunch_start')
                                    ->label('Início da Pausa')
                                    ->seconds(false)
                                    ->displayFormat('H:i'),

                                TimePicker::make('lunch_end')
                                    ->label('Fim da Pausa')
                                    ->seconds(false)
                                    ->displayFormat('H:i')
                                    ->after('lunch_start'), // Validação: Fim deve ser após o início
                            ])->columns(2),

                        TextInput::make('commission_percentage')
                            ->label('Porcentagem da Comissão')
                            ->numeric()
                            ->suffix('%')
                            ->default(50.00)
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                    
            ]),
    ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->circular()
                    ->label('Foto'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nome'),

                TextColumn::make('phone')
                    ->label('Telefone'),

                TextColumn::make('barbershop.name')
                    ->label('Barbearia')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Ativo'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListBarbers::route('/'),
            'create' => Pages\CreateBarber::route('/create'),
            'edit'   => Pages\EditBarber::route('/{record}/edit'),
        ];
    }
}
