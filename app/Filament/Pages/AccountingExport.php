<?php

namespace App\Filament\Pages;

use App\Models\AccountingSetting;
use App\Services\FecExportService;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Response;
use Filament\Facades\Filament;

class AccountingExport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

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

    protected static string $view = 'filament.pages.accounting-export';

    protected static ?string $navigationLabel = 'Export Comptable';

    protected static ?string $title = 'Export Comptable';

    protected static ?string $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];
    public ?array $previewData = null;
    public bool $showPreview = false;

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfYear()->format('Y-m-d'),
            'end_date' => now()->endOfYear()->format('Y-m-d'),
            'format' => 'fec',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')
                    ->label('Date de début')
                    ->required()
                    ->default(now()->startOfYear()),

                DatePicker::make('end_date')
                    ->label('Date de fin')
                    ->required()
                    ->default(now()->endOfYear())
                    ->after('start_date'),

                Select::make('format')
                    ->label('Format d\'export')
                    ->required()
                    ->options([
                        'fec' => 'FEC (Fichier des Écritures Comptables)',
                        'csv' => 'CSV (Export simple)',
                    ])
                    ->default('fec')
                    ->helperText('Le format FEC est obligatoire pour les contrôles fiscaux en France'),
            ])
            ->statePath('data');
    }

    public function previewFec(): void
    {
        $data = $this->form->getState();

        try {
            $companyId = filament()->getTenant()->id;
            $service = new FecExportService($companyId);
            
            // Générer les écritures
            $content = $service->generate($data['start_date'], $data['end_date']);
            
            // Parser le contenu pour l'affichage
            $lines = explode("\n", $content);
            $header = array_shift($lines); // Retirer l'en-tête
            
            $this->previewData = [
                'header' => explode('|', $header),
                'entries' => array_map(fn($line) => explode('|', $line), array_slice($lines, 0, 100)), // Limiter à 100 lignes
                'total_count' => count($lines),
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ];
            
            $this->showPreview = true;

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur lors de la prévisualisation')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->previewData = null;
    }

    public function exportFec(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $data = $this->form->getState();

        try {
            $companyId = filament()->getTenant()->id;
            $service = new FecExportService($companyId);
            
            $content = $service->generate($data['start_date'], $data['end_date']);
            $filename = $service->getFileName($data['end_date']);

            // Créer un fichier temporaire
            $tempFile = tempnam(sys_get_temp_dir(), 'fec_');
            file_put_contents($tempFile, $content);

            Notification::make()
                ->title('Export généré avec succès')
                ->success()
                ->send();

            return Response::download($tempFile, $filename, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur lors de l\'export')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $data = $this->form->getState();

        try {
            $companyId = filament()->getTenant()->id;
            $service = new FecExportService($companyId);
            
            // Utiliser le même service mais formater en CSV
            $content = $service->generate($data['start_date'], $data['end_date']);
            
            // Remplacer les pipes par des points-virgules pour le format CSV
            $csvContent = str_replace('|', ';', $content);
            
            $filename = 'export_comptable_' . date('Y-m-d') . '.csv';

            // Créer un fichier temporaire
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
            file_put_contents($tempFile, "\xEF\xBB\xBF" . $csvContent); // BOM UTF-8 pour Excel

            Notification::make()
                ->title('Export CSV généré avec succès')
                ->success()
                ->send();

            return Response::download($tempFile, $filename, [
                'Content-Type' => 'text/csv; charset=utf-8',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur lors de l\'export')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
