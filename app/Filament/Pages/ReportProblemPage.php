<?php

namespace App\Filament\Pages;

use App\Models\Report;
use App\Models\User;
use App\Notifications\NewReportReceived;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportProblemPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-bug-ant';
    protected static ?string $navigationLabel = 'Reportar Problema';
    protected static ?string $title           = 'Reportar Problema ou Sugestão';
    protected static ?string $slug            = 'report-problem';
    protected static ?int    $navigationSort  = 100;

    protected static string $view = 'filament.pages.report-problem';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'bug'        => '🐛 Bug / Erro no sistema',
                        'suggestion' => '💡 Sugestão de melhoria',
                        'other'      => '💬 Outro',
                    ])
                    ->required()
                    ->default('bug'),

                TextInput::make('title')
                    ->label('Título')
                    ->placeholder('Resuma o problema em uma frase')
                    ->required()
                    ->maxLength(150),

                Textarea::make('description')
                    ->label('Descrição')
                    ->placeholder('Descreva o problema com o máximo de detalhes possível. O que aconteceu? O que você esperava que acontecesse?')
                    ->required()
                    ->rows(6)
                    ->maxLength(2000),

                FileUpload::make('image_path')
                    ->label('Imagem / Captura de tela (opcional)')
                    ->image()
                    ->imageEditor()
                    ->directory('reports')
                    ->disk('public')
                    ->maxSize(5120) // 5MB
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                    ->helperText('Anexe uma captura de tela se isso ajudar a entender o problema. Máx 5MB.'),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        /** @var \App\Models\User $user */
        $user       = Auth::user();
        $barbershop = Filament::getTenant();

        $report = Report::create([
            'user_id'       => $user->id,
            'barbershop_id' => $barbershop?->id,
            'type'          => $data['type'],
            'title'         => $data['title'],
            'description'   => $data['description'],
            'image_path'    => $data['image_path'] ?? null,
            'status'        => 'open',
        ]);

        // Notifica todos os admins do sistema
        $this->notifyAdmins($report);

        $this->form->fill();

        Notification::make()
            ->title('Reporte enviado com sucesso!')
            ->body('Obrigado! Vamos analisar e entrar em contato em breve.')
            ->success()
            ->send();
    }

    private function notifyAdmins(Report $report): void
    {
        try {
            $admins = User::where('role', 'admin')->get();

            foreach ($admins as $admin) {
                $admin->notify(new NewReportReceived($report));
            }
        } catch (\Throwable $e) {
            Log::error('Erro ao notificar admins sobre reporte: ' . $e->getMessage(), [
                'report_id' => $report->id,
            ]);
        }
    }
}
