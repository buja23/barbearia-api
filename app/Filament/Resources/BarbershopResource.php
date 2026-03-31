<?php
namespace App\Filament\Resources;

use App\Filament\Resources\BarbershopResource\Pages;
use App\Models\Barbershop;
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
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
                            ->disk('public')
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

                Section::make('Recebimento via PIX')
                    ->description('Configure sua chave PIX para receber pagamentos dos clientes diretamente na sua conta.')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Select::make('pix_key_type')
                            ->label('Tipo de Chave')
                            ->options([
                                'cpf'    => 'CPF',
                                'cnpj'   => 'CNPJ',
                                'email'  => 'E-mail',
                                'phone'  => 'Telefone',
                                'random' => 'Chave Aleatória',
                            ])
                            ->live()
                            ->placeholder('Selecione o tipo'),

                        TextInput::make('pix_key')
                            ->label('Chave PIX')
                            ->placeholder('CPF, CNPJ, e-mail, telefone ou chave aleatória')
                            ->helperText('Esta chave será usada para gerar o QR Code PIX nos agendamentos.'),
                    ])->columns(2),

                Section::make('MercadoPago — Recebimento de Assinaturas')
                    ->description('Conecte sua conta MercadoPago para receber pagamentos de assinaturas (PIX e cartão) diretamente na sua conta.')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('mp_status')
                            ->label('Status da Conexão')
                            ->content(function ($record): \Illuminate\Support\HtmlString {
                                if ($record && $record->mp_access_token) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<span class="inline-flex items-center gap-1 text-success-600 font-semibold">'
                                        . '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>'
                                        . ' Conta MercadoPago conectada</span>'
                                    );
                                }
                                return new \Illuminate\Support\HtmlString(
                                    '<span class="inline-flex items-center gap-1 text-warning-600 font-semibold">'
                                    . '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>'
                                    . ' Não conectado — clientes não podem pagar pela assinatura</span>'
                                );
                            }),

                        \Filament\Forms\Components\Actions::make([
                            \Filament\Forms\Components\Actions\Action::make('connect_mp')
                                ->label('Conectar MercadoPago')
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->color('success')
                                ->url(route('mp.connect'))
                                ->visible(fn ($record) => $record && !$record->mp_access_token),

                            \Filament\Forms\Components\Actions\Action::make('disconnect_mp')
                                ->label('Desconectar MercadoPago')
                                ->icon('heroicon-o-x-circle')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Desconectar MercadoPago?')
                                ->modalDescription('Após desconectar, clientes não poderão pagar assinaturas até você reconectar a conta.')
                                ->url(route('mp.disconnect'))
                                ->visible(fn ($record) => $record && $record->mp_access_token),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
       return $table
            ->columns([
                ImageColumn::make('logo_path')->disk('public')->label('Logo')->circular(),
                TextColumn::make('name')->label('Barbearia')->searchable()->sortable(),
                TextColumn::make('slug')
                    ->label('Link')
                    ->formatStateUsing(fn (string $state): string => url("/b/{$state}"))
                    ->color('primary')
                    ->copyable()
                    ->copyMessage('Link copiado!'),
                TextColumn::make('phone')->label('WhatsApp'),
            ])
            ->actions([
                // Action de TABELA (Usamos Tables\Actions\Action)
                Tables\Actions\Action::make('qr_code')
                    ->label('QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->modalHeading('QR Code para Impressão')
                    ->modalContent(function (Barbershop $record) {
                        $url = route('barbershop.public', ['slug' => $record->slug]);
                        
                        // Garante que o QR Code seja gerado
                        $qrCode = QrCode::size(250)->margin(2)->generate($url);

                        return view('filament.pages.qr-code-modal', [
                            'qrCode' => $qrCode,
                            'url'    => $url,
                            'name'   => $record->name
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn($action) => $action->label('Fechar')),

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

    /**
     * Barbershop é o próprio tenant — não possui relação "barbershop" consigo mesmo.
     * Desabilita o tenant scoping automático do Filament neste resource.
     */
    public static function isScopedToTenant(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        // Admin vê todas; barber vê apenas a sua própria
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return Barbershop::query();
        }

        return Barbershop::query()->where('user_id', $user?->id);
    }
}
