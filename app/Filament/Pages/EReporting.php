<?php

namespace App\Filament\Pages;

use App\Services\EReportingService;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;

class EReporting extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationLabel = 'E-Reporting';
    protected static ?string $title = 'E-Reporting — B2C & International';
    protected static string $view = 'filament.pages.e-reporting';

    public ?string $date_from = null;
    public ?string $date_to = null;
    public ?string $period_preset = 'current_month';
    public ?array $report = null;
    public bool $showReport = false;

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('accounting') ?? true;
    }

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant?->isModuleEnabled('accounting')) return false;

        $user = auth()->user();
        if (!$user) return false;

        return $user->isAdmin() || $user->hasPermission('accounting.view') || $user->hasPermission('accounting.manage');
    }

    public function mount(): void
    {
        $this->applyPreset('current_month');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('period_preset')
                    ->label('Période')
                    ->options([
                        'current_month' => 'Mois en cours',
                        'last_month' => 'Mois précédent',
                        'current_quarter' => 'Trimestre en cours',
                        'last_quarter' => 'Trimestre précédent',
                        'current_year' => 'Année en cours',
                        'custom' => 'Personnalisé',
                    ])
                    ->default('current_month')
                    ->live()
                    ->afterStateUpdated(fn ($state) => $this->applyPreset($state)),

                DatePicker::make('date_from')
                    ->label('Du')
                    ->required()
                    ->visible(fn () => $this->period_preset === 'custom'),

                DatePicker::make('date_to')
                    ->label('Au')
                    ->required()
                    ->visible(fn () => $this->period_preset === 'custom'),
            ])
            ->columns(3);
    }

    public function applyPreset(string $preset): void
    {
        $this->period_preset = $preset;

        $now = now();

        switch ($preset) {
            case 'current_month':
                $this->date_from = $now->startOfMonth()->format('Y-m-d');
                $this->date_to = $now->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $this->date_from = $now->subMonth()->startOfMonth()->format('Y-m-d');
                $this->date_to = $now->endOfMonth()->format('Y-m-d');
                break;
            case 'current_quarter':
                $quarter = ceil($now->month / 3);
                $this->date_from = $now->setMonth(($quarter - 1) * 3 + 1)->startOfMonth()->format('Y-m-d');
                $this->date_to = $now->addMonths(2)->endOfMonth()->format('Y-m-d');
                break;
            case 'last_quarter':
                $quarter = ceil($now->month / 3) - 1;
                if ($quarter <= 0) {
                    $quarter = 4;
                    $now->subYear();
                }
                $this->date_from = $now->setMonth(($quarter - 1) * 3 + 1)->startOfMonth()->format('Y-m-d');
                $this->date_to = $now->addMonths(2)->endOfMonth()->format('Y-m-d');
                break;
            case 'current_year':
                $this->date_from = $now->startOfYear()->format('Y-m-d');
                $this->date_to = $now->endOfYear()->format('Y-m-d');
                break;
        }
    }

    /**
     * Générer le rapport
     */
    public function generateReport(): void
    {
        if (!$this->date_from || !$this->date_to) {
            Notification::make()
                ->title('Erreur')
                ->body('Veuillez sélectionner une période.')
                ->danger()
                ->send();
            return;
        }

        $companyId = Filament::getTenant()->id;
        $service = new EReportingService();
        $this->report = $service->generateReport($companyId, $this->date_from, $this->date_to);
        $this->showReport = true;
    }

    /**
     * Exporter en CSV
     */
    public function exportCsv()
    {
        if (!$this->report) {
            $this->generateReport();
        }

        $service = new EReportingService();
        $csv = $service->exportCsv($this->report);
        $filename = 'e-reporting_' . $this->date_from . '_' . $this->date_to . '.csv';

        return response()->streamDownload(function () use ($csv) {
            echo "\xEF\xBB\xBF"; // BOM UTF-8 pour Excel
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Exporter en XML
     */
    public function exportXml()
    {
        if (!$this->report) {
            $this->generateReport();
        }

        $service = new EReportingService();
        $xml = $service->exportXml($this->report);
        $filename = 'e-reporting_' . $this->date_from . '_' . $this->date_to . '.xml';

        return response()->streamDownload(function () use ($xml) {
            echo $xml;
        }, $filename, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
