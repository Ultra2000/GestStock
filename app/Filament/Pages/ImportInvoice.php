<?php

namespace App\Filament\Pages;

use App\Services\InvoiceImportService;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;

class ImportInvoice extends Page implements HasForms
{
    use InteractsWithForms, WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?string $navigationGroup = 'Stocks & Achats';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Import factures XML';
    protected static ?string $title = 'Import de factures fournisseurs (UBL / CII)';
    protected static string $view = 'filament.pages.import-invoice';

    public $xmlFile = null;
    public ?array $preview = null;
    public ?array $importResult = null;
    public bool $showPreview = false;
    public bool $showResult = false;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->isAdmin() || $user->hasPermission('purchases.create');
    }

    /**
     * Prévisualiser le fichier XML avant import
     */
    public function previewFile(): void
    {
        $this->validate([
            'xmlFile' => 'required|file|mimes:xml,txt|max:10240',
        ], [
            'xmlFile.required' => 'Veuillez sélectionner un fichier XML.',
            'xmlFile.mimes' => 'Le fichier doit être au format XML.',
            'xmlFile.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        try {
            $path = $this->xmlFile->getRealPath();
            if (!$path || !file_exists($path)) {
                Notification::make()
                    ->title('Fichier expiré')
                    ->body('Le fichier temporaire a expiré. Veuillez re-sélectionner le fichier.')
                    ->danger()
                    ->send();
                $this->xmlFile = null;
                return;
            }

            $content = file_get_contents($path);
            if ($content === false || empty(trim($content))) {
                Notification::make()
                    ->title('Erreur')
                    ->body('Impossible de lire le contenu du fichier.')
                    ->danger()
                    ->send();
                return;
            }

            $service = new InvoiceImportService();
            $this->preview = $service->preview($content);
            $this->showPreview = true;
            $this->showResult = false;

            if (!$this->preview['valid']) {
                Notification::make()
                    ->title('Fichier invalide')
                    ->body($this->preview['error'] ?? 'Format non reconnu.')
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::error('Erreur prévisualisation facture XML', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            Notification::make()
                ->title('Erreur')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Importer la facture
     */
    public function importFile(): void
    {
        $this->validate([
            'xmlFile' => 'required|file|mimes:xml,txt|max:10240',
        ]);

        try {
            $path = $this->xmlFile->getRealPath();
            if (!$path || !file_exists($path)) {
                Notification::make()
                    ->title('Fichier expiré')
                    ->body('Le fichier temporaire a expiré. Veuillez re-sélectionner le fichier.')
                    ->danger()
                    ->send();
                $this->xmlFile = null;
                return;
            }

            $content = file_get_contents($path);
            if ($content === false || empty(trim($content))) {
                Notification::make()
                    ->title('Erreur')
                    ->body('Impossible de lire le contenu du fichier.')
                    ->danger()
                    ->send();
                return;
            }

            $tenant = Filament::getTenant();
            if (!$tenant) {
                Notification::make()
                    ->title('Erreur')
                    ->body('Impossible de déterminer l\'entreprise. Veuillez vous reconnecter.')
                    ->danger()
                    ->send();
                return;
            }

            $companyId = $tenant->id;
            $service = new InvoiceImportService();
            $this->importResult = $service->importFromContent($content, $companyId);
            $this->showResult = true;
            $this->showPreview = false;

            if ($this->importResult['success']) {
                Notification::make()
                    ->title('Import réussi')
                    ->body("Facture {$this->importResult['invoice_number']} importée — {$this->importResult['items_count']} article(s)")
                    ->success()
                    ->send();
                    
                // Reset le fichier
                $this->xmlFile = null;
            } else {
                Notification::make()
                    ->title('Échec de l\'import')
                    ->body($this->importResult['message'])
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::error('Erreur import facture XML', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            Notification::make()
                ->title('Erreur')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Réinitialiser le formulaire
     */
    public function resetForm(): void
    {
        $this->xmlFile = null;
        $this->preview = null;
        $this->importResult = null;
        $this->showPreview = false;
        $this->showResult = false;
    }
}
