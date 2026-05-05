<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\AI\ClaudeExtractor;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
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

    // Modal "Créer ce produit"
    public bool $showCreateProductModal = false;
    public int  $newProductLineIndex    = -1;
    public string $newProductName         = '';
    public string $newProductCode         = '';
    public float  $newProductPurchasePrice = 0.0;
    public float  $newProductVatRate       = 20.0;
    public float  $newProductSalePrice     = 0.0;

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
     * Sépare les lignes-remises globales, calcule les remises implicites par ligne,
     * puis tente un auto-matching avec les produits du catalogue.
     */
    private function initMappings(array $data): void
    {
        $regularLines   = [];
        $discountAmount = 0.0;

        foreach ($data['lines'] ?? [] as $line) {
            if ($this->isGlobalDiscountLine($line)) {
                $discountAmount += abs((float) ($line['total_ht'] ?? $line['unit_price_ht'] ?? 0));
            } else {
                $qty     = (float) ($line['quantity'] ?? 1);
                $pu      = (float) ($line['unit_price_ht'] ?? 0);
                $totalHt = (float) ($line['total_ht'] ?? 0);
                $gross   = $qty * $pu;

                $discountPercent = ($gross > 0 && $totalHt < $gross)
                    ? round((1 - $totalHt / $gross) * 100, 2)
                    : 0.0;

                $regularLines[] = [
                    'product_id'        => null,
                    'description'       => $line['description'] ?? '',
                    'quantity'          => $qty,
                    'unit_price'        => $pu,
                    'discount_percent'  => $discountPercent,
                    'vat_rate'          => (float) ($line['vat_rate'] ?? 20),
                    'match_confidence'  => 0,
                    'match_auto'        => false,
                ];
            }
        }

        $this->linesMappings = $regularLines;

        if ($discountAmount > 0) {
            $subtotal = collect($regularLines)->sum(
                fn ($l) => $l['quantity'] * $l['unit_price'] * (1 - $l['discount_percent'] / 100)
            );
            $this->globalDiscountAmount  = $discountAmount;
            $this->globalDiscountPercent = $subtotal > 0
                ? round(($discountAmount / ($subtotal + $discountAmount)) * 100, 2)
                : 0.0;
        }

        // Auto-matching : chercher les correspondances dans le catalogue
        $this->autoMatchProducts();
    }

    /**
     * Pour chaque ligne, tente de trouver le produit correspondant dans le catalogue.
     * Charge tous les produits une seule fois pour éviter les requêtes N+1.
     */
    private function autoMatchProducts(): void
    {
        $products = Product::get(['id', 'name', 'code', 'purchase_price_ht', 'vat_rate_purchase']);
        if ($products->isEmpty()) return;

        foreach ($this->linesMappings as $i => $line) {
            $match = $this->findBestProductMatch($line['description'], $products);
            if (!$match) continue;

            $product = $match['product'];
            $this->linesMappings[$i]['product_id']       = $product->id;
            $this->linesMappings[$i]['match_confidence']  = $match['confidence'];
            $this->linesMappings[$i]['match_auto']        = true;

            // Remplacer les prix uniquement si la fiche produit est renseignée
            if ($product->purchase_price_ht > 0) {
                $this->linesMappings[$i]['unit_price'] = (float) $product->purchase_price_ht;
            }
            if ($product->vat_rate_purchase) {
                $this->linesMappings[$i]['vat_rate'] = (float) $product->vat_rate_purchase;
            }
        }
    }

    /**
     * Retourne le meilleur produit correspondant à une description IA.
     * Stratégie : exact → code → contenu → similarité ≥ 78 %.
     */
    private function findBestProductMatch(string $description, $products): ?array
    {
        $desc = $this->normalizeForMatching($description);
        if (strlen($desc) < 2) return null;

        $bestScore   = 0;
        $bestProduct = null;

        foreach ($products as $product) {
            $name = $this->normalizeForMatching($product->name ?? '');
            $code = $this->normalizeForMatching($product->code ?? '');

            // Correspondance exacte (100 %)
            if ($name === $desc || ($code && $code === $desc)) {
                return ['product' => $product, 'confidence' => 100];
            }

            // Le code produit est contenu dans la description (95 %)
            if ($code && str_contains($desc, $code)) {
                if (95 > $bestScore) { $bestScore = 95; $bestProduct = $product; }
                continue;
            }

            // Inclusion réciproque (88 %)
            if (str_contains($desc, $name) || str_contains($name, $desc)) {
                if (88 > $bestScore) { $bestScore = 88; $bestProduct = $product; }
                continue;
            }

            // Similarité caractère par caractère
            similar_text($desc, $name, $percent);
            if ($percent > $bestScore) {
                $bestScore   = $percent;
                $bestProduct = $product;
            }
        }

        // Seuil minimum pour éviter les faux positifs
        if ($bestScore >= 78 && $bestProduct) {
            return ['product' => $bestProduct, 'confidence' => (int) round($bestScore)];
        }

        return null;
    }

    /**
     * Normalise une chaîne pour la comparaison :
     * minuscules, suppression des accents et caractères spéciaux.
     */
    private function normalizeForMatching(string $str): string
    {
        $str = mb_strtolower(trim($str));
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $str = ($normalized !== false && $normalized !== '') ? $normalized : $str;
        $str = preg_replace('/[^a-z0-9\s]/', ' ', $str) ?? $str;
        return trim(preg_replace('/\s+/', ' ', $str) ?? $str);
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

    public function openCreateProductModal(int $lineIndex): void
    {
        $line = $this->linesMappings[$lineIndex] ?? null;
        if (!$line) return;

        $this->newProductLineIndex     = $lineIndex;
        $this->newProductName          = $line['description'] ?? '';
        $this->newProductCode          = '';
        $this->newProductPurchasePrice = (float) ($line['unit_price'] ?? 0);
        $this->newProductVatRate       = (float) ($line['vat_rate'] ?? 20);
        $this->newProductSalePrice     = 0.0;
        $this->showCreateProductModal  = true;
        $this->resetValidation();
    }

    public function closeCreateProductModal(): void
    {
        $this->showCreateProductModal = false;
    }

    public function createProduct(): void
    {
        $this->validate([
            'newProductName'          => ['required', 'string', 'min:2'],
            'newProductPurchasePrice' => ['required', 'numeric', 'min:0'],
            'newProductVatRate'       => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'newProductName.required' => 'Le nom du produit est obligatoire.',
            'newProductName.min'      => 'Le nom doit comporter au moins 2 caractères.',
            'newProductPurchasePrice.required' => 'Le prix d\'achat est obligatoire.',
        ]);

        $priceHt  = round((float) $this->newProductPurchasePrice, 4);
        $vatRate  = (float) $this->newProductVatRate;
        $priceTtc = round($priceHt * (1 + $vatRate / 100), 2);

        $saleHt  = (float) $this->newProductSalePrice > 0
            ? round((float) $this->newProductSalePrice, 4)
            : $priceHt;
        $saleTtc = round($saleHt * (1 + $vatRate / 100), 2);

        $product = Product::create([
            'name'              => trim($this->newProductName),
            'code'              => $this->newProductCode ?: null,
            'purchase_price_ht' => $priceHt,
            'purchase_price'    => $priceTtc,
            'vat_rate_purchase' => $vatRate,
            'sale_price_ht'     => $saleHt,
            'price'             => $saleTtc,
            'vat_rate_sale'     => $vatRate,
            'supplier_id'       => $this->supplierId ?: null,
            'stock'             => 0,
        ]);

        // Associer le nouveau produit à la ligne
        $this->linesMappings[$this->newProductLineIndex]['product_id'] = $product->id;
        $this->linesMappings[$this->newProductLineIndex]['unit_price']  = $priceHt;
        $this->linesMappings[$this->newProductLineIndex]['vat_rate']    = $vatRate;

        $this->showCreateProductModal = false;

        // Invalider le cache du computed pour que le nouveau produit apparaisse
        unset($this->products);

        Notification::make()
            ->title('Produit créé')
            ->body("« {$product->name} » ajouté au catalogue et associé à la ligne.")
            ->success()
            ->send();
    }

    public function getMappedLinesCount(): int
    {
        return collect($this->linesMappings)->filter(fn ($l) => !empty($l['product_id']))->count();
    }

    public function createPurchaseOrder(): void
    {
        if (!$this->supplierId) {
            Notification::make()->title('Fournisseur requis')->body('Choisissez un fournisseur avant de créer le bon d\'achat.')->warning()->send();
            return;
        }

        $mappedLines = collect($this->linesMappings)->filter(fn ($l) => !empty($l['product_id']));
        if ($mappedLines->isEmpty()) {
            Notification::make()->title('Aucune ligne associée')->body('Associez au moins un article à un produit du catalogue.')->warning()->send();
            return;
        }

        $companyId = Filament::getTenant()?->id;
        if (!$companyId) {
            Notification::make()->title('Erreur')->body('Aucune entreprise active.')->danger()->send();
            return;
        }

        try {
            $purchase = DB::transaction(function () use ($companyId, $mappedLines) {
                $warehouseId = Warehouse::getDefault($companyId)?->id;

                $purchase = Purchase::create([
                    'company_id'       => $companyId,
                    'supplier_id'      => $this->supplierId,
                    'warehouse_id'     => $warehouseId,
                    'status'           => 'pending',
                    'payment_method'   => 'transfer',
                    'discount_percent' => $this->globalDiscountPercent,
                    'notes'            => 'Importé depuis facture fournisseur',
                ]);

                foreach ($mappedLines as $line) {
                    $qty      = (float) ($line['quantity'] ?? 1);
                    $puHt     = (float) ($line['unit_price'] ?? 0);
                    $disc     = (float) ($line['discount_percent'] ?? 0);
                    $vatRate  = (float) ($line['vat_rate'] ?? 20);

                    $totalHt  = round($qty * $puHt * (1 - $disc / 100), 4);
                    $vatAmt   = round($totalHt * ($vatRate / 100), 4);
                    $totalTtc = round($totalHt + $vatAmt, 2);
                    $discAmt  = round($qty * $puHt * ($disc / 100), 4);

                    PurchaseItem::create([
                        'purchase_id'      => $purchase->id,
                        'product_id'       => $line['product_id'],
                        'quantity'         => $qty,
                        'unit_price_ht'    => $puHt,
                        'unit_price'       => round($puHt * (1 + $vatRate / 100), 4),
                        'discount_percent' => $disc,
                        'discount_amount'  => $discAmt,
                        'vat_rate'         => $vatRate,
                        'vat_amount'       => $vatAmt,
                        'total_price_ht'   => $totalHt,
                        'total_price'      => $totalTtc,
                    ]);
                }

                $purchase->recalculateTotals();

                return $purchase;
            });

            Notification::make()
                ->title('Bon d\'achat créé')
                ->body("Bon d'achat {$purchase->invoice_number} créé avec succès.")
                ->success()
                ->send();

            $this->redirect(PurchaseResource::getUrl('edit', ['record' => $purchase->id, 'tenant' => Filament::getTenant()]));

        } catch (\Throwable $e) {
            Log::error('ImportPurchaseInvoice: createPurchaseOrder failed', ['error' => $e->getMessage()]);
            Notification::make()->title('Erreur création')->body($e->getMessage())->danger()->send();
        }
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
