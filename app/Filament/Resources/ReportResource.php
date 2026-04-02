<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon  = 'heroicon-o-bug-ant';
    protected static ?string $navigationLabel = 'Reportes';
    protected static ?string $navigationGroup = 'Suporte';
    protected static ?int    $navigationSort  = 10;

    // Apenas admins veem este resource
    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('status')
                ->label('Status')
                ->options([
                    'open'        => 'Aberto',
                    'in_progress' => 'Em análise',
                    'resolved'    => 'Resolvido',
                ])
                ->required(),

            Textarea::make('admin_notes')
                ->label('Notas internas (admin)')
                ->rows(4)
                ->maxLength(1000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'bug'        => 'danger',
                        'suggestion' => 'info',
                        'other'      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'bug'        => '🐛 Bug',
                        'suggestion' => '💡 Sugestão',
                        'other'      => '💬 Outro',
                        default      => $state,
                    }),

                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('user.name')
                    ->label('Enviado por')
                    ->searchable(),

                TextColumn::make('barbershop.name')
                    ->label('Barbearia')
                    ->searchable(),

                TextColumn::make('source')
                    ->label('Origem')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'mobile' => 'warning',
                        'admin'  => 'gray',
                        default  => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'mobile' => '📱 App Mobile',
                        'admin'  => '🖥️ Painel Admin',
                        default  => $state,
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'open'        => 'warning',
                        'in_progress' => 'info',
                        'resolved'    => 'success',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'open'        => 'Aberto',
                        'in_progress' => 'Em análise',
                        'resolved'    => 'Resolvido',
                        default       => $state,
                    }),

                ImageColumn::make('image_path')
                    ->label('Imagem')
                    ->disk('public')
                    ->height(48)
                    ->defaultImageUrl(null),

                TextColumn::make('created_at')
                    ->label('Recebido em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'bug'        => '🐛 Bug',
                        'suggestion' => '💡 Sugestão',
                        'other'      => '💬 Outro',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open'        => 'Aberto',
                        'in_progress' => 'Em análise',
                        'resolved'    => 'Resolvido',
                    ]),

                SelectFilter::make('source')
                    ->label('Origem')
                    ->options([
                        'mobile' => '📱 App Mobile',
                        'admin'  => '🖥️ Painel Admin',
                    ]),
            ])
            ->actions([
                Action::make('view')
                    ->label('Ver detalhes')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Report $record) => $record->title)
                    ->modalContent(fn (Report $record) => view('filament.modals.report-detail', ['report' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),

                Action::make('change_status')
                    ->label('Mudar status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->form([
                        Select::make('status')
                            ->label('Novo status')
                            ->options([
                                'open'        => 'Aberto',
                                'in_progress' => 'Em análise',
                                'resolved'    => 'Resolvido',
                            ])
                            ->required(),

                        Textarea::make('admin_notes')
                            ->label('Notas internas')
                            ->rows(3),
                    ])
                    ->fillForm(fn (Report $record) => [
                        'status'      => $record->status,
                        'admin_notes' => $record->admin_notes,
                    ])
                    ->action(function (Report $record, array $data) {
                        $record->update([
                            'status'      => $data['status'],
                            'admin_notes' => $data['admin_notes'] ?? $record->admin_notes,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Status atualizado!')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'barbershop']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
        ];
    }
}
