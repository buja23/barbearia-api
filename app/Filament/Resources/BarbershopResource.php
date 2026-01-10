<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarbershopResource\Pages;
use App\Models\Barbershop;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action; // Importação essencial para o headerActions
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BarbershopResource extends Resource
{
    protected static ?string $model = Barbershop::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationLabel = 'Minha Barbearia';

    protected static ?string $modelLabel = 'Barbearia';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informações Básicas')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state)))
                            ->label('Nome da Barbearia'),

                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('Link/Código (slug)'),

                        TextInput::make('phone')
                            ->tel()
                            ->mask('(99) 99999-9999')
                            ->label('WhatsApp'),

                        TextInput::make('address')
                            ->label('Endereço'),

                        FileUpload::make('logo_path')
                            ->image()
                            ->directory('barbershops-logos')
                            ->label('Logo da Barbearia'),

                        Hidden::make('user_id')
                            ->default(fn() => auth()->id())
                            ->required(),
                    ])->columns(2),

                Section::make('Configuração de Horários')
                    ->description('Defina os horários de funcionamento. Use o botão abaixo para preencher a semana rapidamente.')
                    ->headerActions([
                        // AÇÃO PRÁTICA: Preenchimento automático de Seg a Sex
                        Action::make('fill_weekdays')
                            ->label('Preencher Segunda a Sexta')
                            ->icon('heroicon-m-bolt')
                            ->color('warning')
                            ->form([
                                TimePicker::make('opening_time')
                                    ->label('Abertura Padrão')
                                    ->default('09:00')
                                    ->seconds(false)
                                    ->required(),
                                TimePicker::make('closing_time')
                                    ->label('Fecho Padrão')
                                    ->default('18:00')
                                    ->seconds(false)
                                    ->required(),
                            ])
                            ->action(function (array $data, Set $set) {
                                // Cria o array de 1 (Segunda) a 5 (Sexta)
                                $weekdays = collect(range(1, 5))->map(fn($day) => [
                                    'day_of_week'  => $day,
                                    'opening_time' => $data['opening_time'],
                                    'closing_time' => $data['closing_time'],
                                    'is_closed'    => false,
                                ])->toArray();

                                // Define os valores no Repeater
                                $set('openingHours', $weekdays);
                            }),
                    ])
                    ->schema([
                        Repeater::make('openingHours')
                            ->relationship('openingHours')
                            ->schema([
                                Select::make('day_of_week')
                                    ->label('Dia')
                                    ->options([
                                        0 => 'Domingo',
                                        1 => 'Segunda-feira',
                                        2 => 'Terça-feira',
                                        3 => 'Quarta-feira',
                                        4 => 'Quinta-feira',
                                        5 => 'Sexta-feira',
                                        6 => 'Sábado',
                                    ])
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                TimePicker::make('opening_time')
                                    ->label('Abertura')
                                    ->seconds(false)
                                    ->required(fn(Get $get) => ! $get('is_closed'))
                                    ->hidden(fn(Get $get) => $get('is_closed')),

                                TimePicker::make('closing_time')
                                    ->label('Fecho')
                                    ->seconds(false)
                                    ->required(fn(Get $get) => ! $get('is_closed'))
                                    ->hidden(fn(Get $get) => $get('is_closed')),

                                Toggle::make('is_closed')
                                    ->label('Fechado')
                                    ->default(false)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state) {
                                            $set('opening_time', null);
                                            $set('closing_time', null);
                                        }
                                    }),
                            ])
                            ->columns(4)
                            ->reorderable(false)
                            ->addActionLabel('Adicionar dia extra')
                            ->defaultItems(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo'),

                TextColumn::make('name')
                    ->label('Nome da Barbearia')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Link (Slug)')
                    ->copyable(),

                TextColumn::make('phone')
                    ->label('Telefone'),

                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Criado em')
                    ->sortable(),
            ])
            ->filters([])
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBarbershops::route('/'),
            'create' => Pages\CreateBarbershop::route('/create'),
            'edit'   => Pages\EditBarbershop::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Garante que o dono veja apenas a sua própria barbearia
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
}