<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Financeiro';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nome do Plano'),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->prefix('R$')
                    ->required()
                    ->label('Preço Mensal'),
                Forms\Components\TextInput::make('cuts_per_month')
                    ->numeric()
                    ->required()
                    ->label('Cortes por Mês'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Ativo')
                    ->default(true),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Plano'),
                Tables\Columns\TextColumn::make('price')->money('BRL')->label('Preço'),
                Tables\Columns\TextColumn::make('cuts_per_month')->label('Cortes'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Status'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}