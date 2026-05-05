<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Services\AI\ClaudeExtractor;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;
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
            'pdfFile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,gif', 'max:10240'],
        ], [
            'pdfFile.required' => 'Sélectionnez un fichier.',
            'pdfFile.mimes'    => 'Formats acceptés : PDF, JPEG, PNG, WebP.',
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
            $mimeType = $this->pdfFile->getMimeType() ?? 'application/octet-stream';
            $extractor = new ClaudeExtractor();

            Log::info('ImportPurchaseInvoice: processing file', ['mime' => $mimeType]);

            if (str_starts_with($mimeType, 'image/')) {
                // Fichier image (JPEG, PNG, WebP…) → envoi direct à Claude Vision
                $base64 = base64_encode(file_get_contents($realPath));
                $data   = $extractor->extractFromImage($base64, $mimeType);
            } else {
                // PDF : tenter l'extraction texte d'abord
                $text = '';
                try {
                    $parser = new PdfParser();
                    $pdf    = $parser->parseFile($realPath);
                    $text   = $pdf->getText();
                } catch (\Throwable) {}

                $text = $this->sanitizeUtf8($text);

                Log::info('ImportPurchaseInvoice: text extracted', [
                    'length'     => strlen($text),
                    'valid_utf8' => mb_check_encoding($text, 'UTF-8'),
                    'json_ok'    => json_encode($text) !== false,
                ]);

                if (strlen(trim($text)) >= 50) {
                    $data = $extractor->extractInvoiceData($text, 'application/pdf');
                } else {
                    // PDF scanné → envoi base64 natif à Claude
                    $base64 = base64_encode(file_get_contents($realPath));
                    $data   = $extractor->extractFromPdf($base64);
                }
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
     * Garantit une chaîne 100 % compatible json_encode.
     *
     * Cascade de nettoyage :
     *   1. Détection d'encodage et conversion vers UTF-8
     *   2. iconv //IGNORE pour retirer les octets invalides résiduels
     *   3. str_replace des caractères de contrôle (sans flag /u pour éviter null)
     *   4. Garantie finale via JSON_INVALID_UTF8_SUBSTITUTE
     */
    private function sanitizeUtf8(string $text): string
    {
        // 1. Détecter l'encodage réel et convertir en UTF-8
        $detected = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ISO-8859-15'], true);
        if ($detected && $detected !== 'UTF-8') {
            $text = mb_convert_encoding($text, 'UTF-8', $detected);
        }

        // 2. iconv supprime les octets invalides restants
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if ($clean === false) {
            $clean = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
        }

        // 3. Retirer les caractères de contrôle ASCII via str_replace
        //    (évite le flag /u de preg_replace qui renvoie null si UTF-8 encore invalide)
        $controls = array_map('chr', array_merge(range(0, 8), [11, 12], range(14, 31), [127]));
        $clean = str_replace($controls, '', (string) $clean);

        // 4. Garantie absolue : si json_encode échoue encore, on force via SUBSTITUTE
        if (json_encode($clean) === false) {
            $encoded = json_encode($clean, JSON_INVALID_UTF8_SUBSTITUTE);
            $clean   = $encoded !== false ? (string) json_decode($encoded) : '';
        }

        return $clean;
    }
}
