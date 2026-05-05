<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Services\AI\ClaudeExtractor;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use Smalot\PdfParser\Parser as PdfParser;

class ImportPurchaseInvoice extends Page
{
    use WithFileUploads;

    protected static string $resource = PurchaseResource::class;
    protected static string $view = 'filament.resources.purchase-resource.pages.import-purchase-invoice';
    protected static ?string $title = 'Importer une facture fournisseur';

    public $pdfFile = null;
    public ?array $extractedData = null;
    public bool $isExtracting = false;
    public ?string $errorMessage = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Retour aux achats')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    public function extract(): void
    {
        $this->validate([
            'pdfFile' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'pdfFile.required' => 'Sélectionnez un fichier PDF.',
            'pdfFile.mimes'    => 'Le fichier doit être au format PDF.',
            'pdfFile.max'      => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        $this->isExtracting = true;
        $this->extractedData = null;
        $this->errorMessage = null;

        try {
            $apiKey = config('services.ai.claude.api_key');
            if (empty($apiKey)) {
                throw new \RuntimeException('Clé API Claude non configurée (CLAUDE_API_KEY).');
            }

            $realPath = $this->pdfFile->getRealPath();

            // Tenter l'extraction texte du PDF
            $text = '';
            try {
                $parser = new PdfParser();
                $pdf    = $parser->parseFile($realPath);
                $text   = $pdf->getText();
            } catch (\Throwable) {}

            // Nettoyer les caractères UTF-8 invalides issus du parseur PDF
            // (ISO-8859-1, Windows-1252, octets orphelins…)
            $text = $this->sanitizeUtf8($text);

            $extractor = new ClaudeExtractor();

            if (strlen(trim($text)) >= 50) {
                $data = $extractor->extractInvoiceData($text, 'application/pdf');
            } else {
                // PDF scanné / image → envoi base64 natif à Claude
                $base64 = base64_encode(file_get_contents($realPath));
                $data   = $extractor->extractFromPdf($base64);
            }

            $this->extractedData = $data;

            $lineCount = count($data['lines'] ?? []);
            Notification::make()
                ->title('Extraction réussie')
                ->body("{$lineCount} ligne(s) extraite(s) depuis la facture.")
                ->success()
                ->send();

        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
            Notification::make()
                ->title("Erreur d'extraction")
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isExtracting = false;
        }
    }

    public function resetExtraction(): void
    {
        $this->pdfFile       = null;
        $this->extractedData = null;
        $this->errorMessage  = null;
    }

    /**
     * Supprime les séquences UTF-8 invalides et les caractères de contrôle
     * que json_encode refuse (erreur "Malformed UTF-8 characters").
     */
    private function sanitizeUtf8(string $text): string
    {
        // 1. iconv supprime les octets invalides (ISO-8859-1 non déclarés, etc.)
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if ($clean === false) {
            // Fallback : re-encoder depuis ISO-8859-1
            $clean = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
        }

        // 2. Retirer les caractères de contrôle ASCII interdits en JSON
        //    (garder \t = 0x09, \n = 0x0A, \r = 0x0D)
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean);

        return $clean ?? '';
    }
}
