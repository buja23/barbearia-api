<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;

class ServiceResource extends Resource {
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form( Form $form ): Form {
        return $form
        ->schema( [
            Select::make( 'barbershop_id' )
            ->relationship( 'barbershop', 'name' )
            ->required()
            ->label( 'Pertence à Barbearia' ),

            TextInput::make( 'name' )
            ->required()
            ->label( 'Nome do Serviço (ex: Corte)' ),

            TextInput::make( 'price' )
            ->numeric()
            ->prefix( 'R$' )
            ->required(),

            TextInput::make( 'duration_minutes' )
            ->numeric()
            ->default( 30 )
            ->label( 'Duração (minutos)' ),

            Toggle::make( 'is_active' )
            ->label( 'Ativo?' )
            ->default(true),
        ] );
    }

    public static function table( Table $table ): Table {
        return $table
        ->columns( [
            TextColumn::make( 'name' )
            ->label( 'Serviço' )
            ->searchable(),

            TextColumn::make( 'barbershop.name' )
            ->label( 'Barbearia' ),

            TextColumn::make( 'price' )
            ->money( 'BRL' ) // Formata automático como R$
            ->label( 'Preço' ),

            TextColumn::make( 'duration_minutes' )
            ->suffix( ' min' )
            ->label( 'Duração' ),

            ToggleColumn::make( 'is_active' )
            ->label( 'Ativo?' ),
        ] )
        ->filters( [
            //
        ] )
        ->actions( [
            Tables\Actions\EditAction::make(),
        ] )
        ->bulkActions( [
            Tables\Actions\BulkActionGroup::make( [
                Tables\Actions\DeleteBulkAction::make(),
            ] ),
        ] );
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ListServices::route( '/' ),
            'create' => Pages\CreateService::route( '/create' ),
            'edit' => Pages\EditService::route( '/{record}/edit' ),
        ];
    }
}
