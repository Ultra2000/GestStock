<?php

namespace App\Filament\Pages;

use App\Models\VatDeclaration;
use App\Services\Ca3DeclarationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Ca3Declaration extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Déclaration CA3';
    protected static ?string $title           = 'Déclaration de TVA (CA3)';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?int    $navigationSort  = 4;

    protected static string $view = 'filament.pages.ca3-declaration';

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('accounting') ?? false;
    }

    public static function canAccess(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('accounting') ?? false;
    }

    public ?array $data = [];

    /** Résultat du calcul en cours */
    public ?array $result = null;

    /** Déclaration sauvegardée en cours */
    public ?VatDeclaration $declaration = null;

    public function mount(): void
    {
        $this->form->fill([
            'period'     => 'month',
            'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'end_date'   => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Période de déclaration')
                    ->schema([
                        Select::make('period')
                            ->label('Période')
                            ->options([
                                'month'   => 'Mois en cours',
                                'last_month' => 'Mois précédent',
                                'quarter' => 'Trimestre en cours',
                                'last_quarter' => 'Trimestre précédent',
                                'year'    => 'Année en cours',
                                'custom'  => 'Personnalisée',
                            ])
                            ->default('month')
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->updatePeriod($state)),
                        DatePicker::make('start_date')
                            ->label('Date de début')
                            ->required()
                            ->visible(fn ($get) => $get('period') === 'custom'),
                        DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->required()
                            ->visible(fn ($get) => $get('period') === 'custom'),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    public function updatePeriod(?string $period): void
    {
        $now = Carbon::now();
        match ($period) {
            'month'        => $this->data['start_date'] = $now->copy()->startOfMonth()->format('Y-m-d'),
            'last_month'   => $this->data['start_date'] = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d'),
            'quarter'      => $this->data['start_date'] = $now->copy()->startOfQuarter()->format('Y-m-d'),
            'last_quarter' => $this->data['start_date'] = $now->copy()->subQuarter()->startOfQuarter()->format('Y-m-d'),
            'year'         => $this->data['start_date'] = $now->copy()->startOfYear()->format('Y-m-d'),
            default        => null,
        };
        match ($period) {
            'month'        => $this->data['end_date'] = $now->copy()->endOfMonth()->format('Y-m-d'),
            'last_month'   => $this->data['end_date'] = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d'),
            'quarter'      => $this->data['end_date'] = $now->copy()->endOfQuarter()->format('Y-m-d'),
            'last_quarter' => $this->data['end_date'] = $now->copy()->subQuarter()->endOfQuarter()->format('Y-m-d'),
            'year'         => $this->data['end_date'] = $now->copy()->endOfYear()->format('Y-m-d'),
            default        => null,
        };
        // Réinitialiser le résultat si la période change
        $this->result = null;
        $this->declaration = null;
    }

    public function calculate(): void
    {
        $this->form->validate();

        $service = new Ca3DeclarationService();
        $this->result = $service->calculate(
            Filament::getTenant()->id,
            $this->data['start_date'],
            $this->data['end_date'],
        );
        $this->declaration = null;
    }

    public function saveDeclaration(): void
    {
        if (!$this->result) {
            return;
        }

        $service = new Ca3DeclarationService();
        $label   = $service->periodLabel(
            $this->data['start_date'],
            $this->data['end_date'],
            $this->data['period'] ?? 'custom'
        );

        $this->declaration = $service->save(
            companyId:   Filament::getTenant()->id,
            startDate:   $this->data['start_date'],
            endDate:     $this->data['end_date'],
            periodLabel: $label,
            data:        $this->result,
        );

        Notification::make()
            ->title('Déclaration sauvegardée')
            ->body("CA3 « {$label} » enregistrée avec succès.")
            ->success()
            ->send();
    }

    public function validateDeclaration(): void
    {
        if (!$this->declaration) {
            return;
        }
        $this->declaration->validate();

        Notification::make()
            ->title('Déclaration validée')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('historique')
                ->label('Historique')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->url(fn () => route('filament.admin.pages.ca3-history', ['tenant' => Filament::getTenant()->slug]))
                ->hidden(fn () => !method_exists($this, 'getHistorique')),

            Action::make('export_pdf')
                ->label('Exporter PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->disabled(fn () => !$this->result)
                ->action(function () {
                    $company = Filament::getTenant();
                    $pdf = Pdf::loadView('reports.ca3-declaration', [
                        'company'     => $company,
                        'declaration' => $this->result,
                        'period'      => [
                            'start' => Carbon::parse($this->data['start_date'])->translatedFormat('d F Y'),
                            'end'   => Carbon::parse($this->data['end_date'])->translatedFormat('d F Y'),
                            'label' => (new Ca3DeclarationService())->periodLabel(
                                $this->data['start_date'],
                                $this->data['end_date'],
                                $this->data['period'] ?? 'custom'
                            ),
                        ],
                        'currency' => $company->currency ?? 'EUR',
                    ])->setPaper([0, 0, 595.28, 841.89], 'portrait'); // A4 en points (72dpi)

                    $filename = 'CA3-' . Carbon::parse($this->data['start_date'])->format('Y-m') . '.pdf';

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        $filename,
                        ['Content-Type' => 'application/pdf']
                    );
                }),
        ];
    }

    public function getCurrency(): string
    {
        return Filament::getTenant()->currency ?? 'EUR';
    }

    /**
     * Déclarations CA3 sauvegardées pour cet espace
     */
    public function getHistory(): \Illuminate\Database\Eloquent\Collection
    {
        return VatDeclaration::where('company_id', Filament::getTenant()->id)
            ->orderByDesc('period_start')
            ->limit(12)
            ->get();
    }
}
