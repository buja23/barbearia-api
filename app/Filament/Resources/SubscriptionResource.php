<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Carbon\Carbon;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $navigationLabel = 'Assinaturas';
    protected static ?string $modelLabel = 'Assinatura';
    protected static ?string $pluralModelLabel = 'Assinaturas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informações da Assinatura')
                    ->description('Vincule um cliente a um plano e defina a validade.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Cliente')
                                    ->options(User::where('role', 'client')->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),

                                Forms\Components\Select::make('plan_id')
                                    ->label('Plano Selecionado')
                                    ->relationship('plan', 'name')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                        $set('remaining_cuts', Plan::find($state)?->cuts_per_month ?? 0)),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('starts_at')
                                    ->label('Início da Assinatura')
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->required(),

                                Forms\Components\DatePicker::make('expires_at')
                                    ->label('Vencimento')
                                    ->default(now()->addMonth())
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->required(),

                                Forms\Components\TextInput::make('remaining_cuts')
                                    ->label('Saldo de Cortes')
                                    ->numeric()
                                    ->helperText('Quantidade de cortes disponíveis para o mês.')
                                    ->required(),
                            ]),

                        Forms\Components\Select::make('status')
                            ->label('Status da Assinatura')
                            ->options([
                                'active' => 'Ativo',
                                'expired' => 'Expirado',
                                'canceled' => 'Cancelado',
                            ])
                            ->default('active')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('plan.name')
                    ->label('Plano')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('remaining_cuts')
                    ->label('Saldo')
                    ->alignCenter()
                    ->description('cortes restantes'),

                TextColumn::make('expires_at')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at->isPast() ? 'danger' : 'success'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Ativo',
                        'expired' => 'Expirado',
                        'canceled' => 'Cancelado',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'active',
                        'danger' => 'expired',
                        'gray' => 'canceled',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Ativo',
                        'expired' => 'Expirado',
                        'canceled' => 'Cancelado',
                    ]),
                
                SelectFilter::make('plan_id')
                    ->label('Filtrar por Plano')
                    ->relationship('plan', 'name'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}