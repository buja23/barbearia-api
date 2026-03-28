<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $navigationLabel = 'Planos';
    protected static ?string $modelLabel = 'Plano';
    protected static ?string $pluralModelLabel = 'Planos';
    protected static ?string $tenantOwnershipRelationshipName = 'barbershop';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Plano')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome do Plano')
                            ->placeholder('Ex: Plano Mensal 4 Cortes')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('price')
                            ->label('Preço Mensal')
                            ->numeric()
                            ->prefix('R$')
                            ->required()
                            ->minValue(0),

                        Forms\Components\TextInput::make('cuts_per_month')
                            ->label('Cortes por Mês')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->helperText('Quantos cortes o assinante tem direito por mês.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Plano Ativo')
                            ->default(true)
                            ->helperText('Apenas planos ativos ficam disponíveis para novas assinaturas.'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->placeholder('Descreva os benefícios do plano...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Plano')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Plan $record) => $record->description),

                Tables\Columns\TextColumn::make('price')
                    ->label('Preço/Mês')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cuts_per_month')
                    ->label('Cortes/Mês')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('Assinantes')
                    ->counts('subscriptions')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Ativo'),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Atenção: planos com assinaturas ativas não podem ser excluídos.'),
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
            'index'  => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit'   => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}