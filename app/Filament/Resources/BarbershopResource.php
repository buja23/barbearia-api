<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarbershopResource\Pages;
use App\Filament\Resources\BarbershopResource\RelationManagers;
use App\Models\Barbershop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Filament\Tables\Columns\TextColumn;


class BarbershopResource extends Resource {
    protected static ?string $model = Barbershop::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form( Form $form ): Form {
        return $form
        ->schema( [
            Select::make( 'user_id' )
            ->relationship( 'user', 'name' ) // Busca usuários pelo nome
            ->required()
            ->label( 'Dono da Barbearia' ),

            TextInput::make( 'name' )
            ->required()
            ->live( onBlur: true ) // Ao sair do campo...
            ->afterStateUpdated( fn( Set $set, ?string $state ) => $set( 'slug', Str::slug( $state ) ) ) // ...gera o slug automático
            ->label( 'Nome da Barbearia' ),

            TextInput::make( 'slug' )
            ->required()
            ->unique( ignoreRecord: true )
            ->label( 'Link/Código (slug)' ),

            TextInput::make( 'phone' )
            ->tel()
            ->label( 'WhatsApp' ),

            TextInput::make( 'address' )
            ->label( 'Endereço' ),

            Hidden::make('user_id')
            ->default(fn () => auth()->id())
            ->required(),

        ] );
    }

    public static function table( Table $table ): Table {
        return $table
        ->columns( [
            TextColumn::make( 'name' )
            ->label( 'Nome da Barbearia' )
            ->searchable()
            ->sortable(),

            TextColumn::make( 'slug' )
            ->label( 'Link (Slug)' )
            ->copyable(),

            TextColumn::make( 'user.name' )
            ->label( 'Dono' )
            ->sortable(),

            TextColumn::make( 'created_at' )
            ->dateTime( 'd/m/Y H:i' )
            ->label( 'Criado em' )
            ->sortable(),
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
            'index' => Pages\ListBarbershops::route( '/' ),
            'create' => Pages\CreateBarbershop::route( '/create' ),
            'edit' => Pages\EditBarbershop::route( '/{record}/edit' ),
        ];
    }

    public static function getEloquentQuery(): Builder{
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }

}
