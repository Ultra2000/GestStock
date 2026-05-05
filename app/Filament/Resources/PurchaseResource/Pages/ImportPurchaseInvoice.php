<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\AI\ClaudeExtractor;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;
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

    // 4b — mapping
    public ?int $supplierId = null;
    public array $linesMappings = [];

    // Remise globale détectée (ligne-remise dans la facture fournisseur)
    public float $globalDiscountPercent = 0.0;
    public ?float $globalDiscountAmount = null;

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

    #[Computed]
    public function products(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::orderBy('name')->get(['id', 'name', 'code', 'purchase_price_ht', 'vat_rate_purchase']);
    }

    #[Computed]
    public function suppliers(): \Illuminate\Database\Eloquent\Collection
    {
        return Supplier::orderBy('name')->get(['id', 'name']);
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

        $this->isExtracting          = true;
        $this->extractedData         = null;
        $this->errorMessage          = null;
        $this->linesMappings         = [];
        $this->supplierId            = null;
        $this->globalDiscountPercent = 0.0;
        $this->globalDiscountAmount  = null;

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
                $base64 = base64_encode(file_get_contents($realPath));
                $data   = $extractor->extractFromImage($base64, $mimeType);
            } else {
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
                    $base64 = base64_encode(file_get_contents($realPath));
                    $data   = $extractor->extractFromPdf($base64);
                }
            }

            $this->extractedData = $data;
            $this->initMappings($data);
            $this->autoMatchSupplier($data['seller']['name'] ?? null);

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

    /**
     * Initialise les lignes de mapping.
     * Sépare les lignes-remises globales des lignes articles normales.
     */
    private function initMappings(array $data): void
    {
        $regularLines   = [];
        $discountAmount = 0.0;

        foreach ($data['lines'] ?? [] as $line) {
            if ($this->isGlobalDiscountLine($line)) {
                $discountAmount += abs((float) ($line['total_ht'] ?? $line['unit_price_ht'] ?? 0));
            } else {
                // Détecter la remise implicite par ligne : si total_ht < qty * unit_price
                $qty     = (float) ($line['quantity'] ?? 1);
                $pu      = (float) ($line['unit_price_ht'] ?? 0);
                $totalHt = (float) ($line['total_ht'] ?? 0);
                $gross   = $qty * $pu;

                $discountPercent = ($gross > 0 && $totalHt < $gross)
                    ? round((1 - $totalHt / $gross) * 100, 2)
                    : 0.0;

                $regularLines[] = [
                    'product_id'       => null,
                    'description'      => $line['description'] ?? '',
                    'quantity'         => $qty,
                    'unit_price'       => $pu,
                    'discount_percent' => $discountPercent,
                    'vat_rate'         => (float) ($line['vat_rate'] ?? 20),
                ];
            }
        }

        $this->linesMappings = $regularLines;

        // Convertir le montant de remise globale en pourcentage du sous-total HT
        if ($discountAmount > 0) {
            $subtotal = collect($regularLines)->sum(
                fn ($l) => $l['quantity'] * $l['unit_price'] * (1 - $l['discount_percent'] / 100)
            );
            $this->globalDiscountAmount  = $discountAmount;
            $this->globalDiscountPercent = $subtotal > 0
                ? round(($discountAmount / ($subtotal + $discountAmount)) * 100, 2)
                : 0.0;
        }
    }

    /**
     * Détermine si une ligne de facture est une remise globale plutôt qu'un article.
     * Critères : montant négatif OU description contenant un mot-clé de remise.
     */
    private function isGlobalDiscountLine(array $line): bool
    {
        $totalHt = (float) ($line['total_ht'] ?? 0);
        $unitPrice = (float) ($line['unit_price_ht'] ?? 0);

        if ($totalHt < 0 || $unitPrice < 0) {
            return true;
        }

        $keywords = ['remise', 'rabais', 'escompte', 'ristourne', 'réduction', 'reduction',
                     'discount', 'avoir', 'remise globale', 'remise commerciale'];
        $desc = mb_strtolower($line['description'] ?? '');

        foreach ($keywords as $kw) {
            if (str_contains($desc, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tente de pré-sélectionner le fournisseur par correspondance de nom.
     */
    private function autoMatchSupplier(?string $detectedName): void
    {
        if (!$detectedName) return;

        $match = Supplier::where('name', 'like', '%' . $detectedName . '%')
            ->orWhere(fn ($q) => $q->whereRaw('? like CONCAT(\'%\', name, \'%\')', [$detectedName]))
            ->first();

        if ($match) {
            $this->supplierId = $match->id;
        }
    }

    /**
     * Quand un produit est sélectionné sur une ligne, auto-remplit prix d'achat et TVA.
     */
    public function selectProduct(int $lineIndex, ?string $productId): void
    {
        $id = $productId ? (int) $productId : null;
        $this->linesMappings[$lineIndex]['product_id'] = $id;

        if ($id) {
            $product = Product::find($id);
            if ($product) {
                if ($product->purchase_price_ht > 0) {
                    $this->linesMappings[$lineIndex]['unit_price'] = (float) $product->purchase_price_ht;
                }
                if ($product->vat_rate_purchase) {
                    $this->linesMappings[$lineIndex]['vat_rate'] = (float) $product->vat_rate_purchase;
                }
            }
        }
    }

    public function resetExtraction(): void
    {
        $this->pdfFile               = null;
        $this->extractedData         = null;
        $this->errorMessage          = null;
        $this->linesMappings         = [];
        $this->supplierId            = null;
        $this->globalDiscountPercent = 0.0;
        $this->globalDiscountAmount  = null;
    }

    public function getMappedLinesCount(): int
    {
        return collect($this->linesMappings)->filter(fn ($l) => !empty($l['product_id']))->count();
    }

    /**
     * Garantit une chaîne 100 % compatible json_encode.
     */
    private function sanitizeUtf8(string $text): string
    {
        $detected = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ISO-8859-15'], true);
        if ($detected && $detected !== 'UTF-8') {
            $text = mb_convert_encoding($text, 'UTF-8', $detected);
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if ($clean === false) {
            $clean = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
        }

        $controls = array_map('chr', array_merge(range(0, 8), [11, 12], range(14, 31), [127]));
        $clean = str_replace($controls, '', (string) $clean);

        if (json_encode($clean) === false) {
            $encoded = json_encode($clean, JSON_INVALID_UTF8_SUBSTITUTE);
            $clean   = $encoded !== false ? (string) json_decode($encoded) : '';
        }

        return $clean;
    }
}
