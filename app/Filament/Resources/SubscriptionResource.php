<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Financeiro';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Cliente')
                    ->options(User::where('role', 'client')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('plan_id')
                    ->relationship('plan', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                        $set('remaining_cuts', \App\Models\Plan::find($state)?->cuts_per_month ?? 0)),
                Forms\Components\DatePicker::make('starts_at')->default(now())->required(),
                Forms\Components\DatePicker::make('expires_at')->default(now()->addMonth())->required(),
                Forms\Components\TextInput::make('remaining_cuts')
                    ->numeric()
                    ->label('Cortes Restantes')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Ativa',
                        'expired' => 'Expirada',
                        'canceled' => 'Cancelada',
                    ])->default('active')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Cliente'),
                Tables\Columns\TextColumn::make('plan.name')->label('Plano'),
                Tables\Columns\TextColumn::make('remaining_cuts')->label('Saldo'),
                Tables\Columns\TextColumn::make('expires_at')->date()->label('Expira em'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => ['expired', 'canceled'],
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