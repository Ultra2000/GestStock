<?php

namespace App\Filament\Pages;

use App\Models\InvoiceConversion;
use App\Services\InvoiceConverterService;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class InvoiceConverter extends Page implements HasForms
{
    use InteractsWithForms, WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?int $navigationSort = 15;
    protected static ?string $navigationLabel = 'Convertir en Factur-X';
    protected static ?string $title = 'Convertisseur de factures → Factur-X';
    protected static string $view = 'filament.pages.invoice-converter';

    public $uploadedFile = null;
    public ?array $extractedData = null;
    public ?int $conversionId = null;
    public bool $showPreview = false;
    public bool $showResult = false;
    public bool $isProcessing = false;
    public ?string $errorMessage = null;
    public ?string $downloadUrl = null;
    public ?string $aiProvider = null;
    public ?int $processingTimeMs = null;

    // Champs éditables du formulaire de prévisualisation
    public string $sellerName = '';
    public string $sellerAddress = '';
    public string $sellerZipCode = '';
    public string $sellerCity = '';
    public string $sellerSiret = '';
    public string $sellerVatNumber = '';
    public string $buyerName = '';
    public string $buyerAddress = '';
    public string $buyerZipCode = '';
    public string $buyerCity = '';
    public string $buyerSiret = '';
    public string $buyerVatNumber = '';
    public string $invoiceNumber = '';
    public string $invoiceDate = '';
    public string $invoiceDueDate = '';
    public string $invoiceCurrency = 'EUR';
    public string $invoiceNotes = '';
    public array $lines = [];
    public string $totalHt = '0';
    public string $totalVat = '0';
    public string $totalTtc = '0';

    // Historique
    public array $recentConversions = [];

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('accounting') ?? true;
    }

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant?->isModuleEnabled('accounting')) {
            return false;
        }

        $user = auth()->user();
        if (!$user) return false;
        return $user->isAdmin() || $user->hasPermission('sales.create');
    }

    public function mount(): void
    {
        $this->loadRecentConversions();
    }

    /**
     * Charger les conversions récentes de l'utilisateur
     */
    protected function loadRecentConversions(): void
    {
        $this->recentConversions = InvoiceConversion::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'filename' => $c->original_filename,
                'status' => $c->status,
                'status_label' => $c->status_label,
                'status_color' => $c->status_color,
                'ai_provider' => $c->ai_provider,
                'created_at' => $c->created_at->diffForHumans(),
                'formatted_size' => $c->formatted_size,
                'has_output' => (bool) $c->output_pdf_path,
            ])
            ->toArray();
    }

    /**
     * Déterminer le tier de l'utilisateur
     */
    protected function getUserTier(): string
    {
        $user = auth()->user();
        $company = Filament::getTenant();

        // Si l'entreprise a une clé Claude configurée, c'est du Pro
        if (config('services.ai.claude.api_key') && ($company?->settings['ai_tier'] ?? 'free') === 'pro') {
            return InvoiceConverterService::TIER_PRO;
        }

        return InvoiceConverterService::TIER_FREE;
    }

    /**
     * Extraire les données du fichier uploadé via IA
     */
    public function extractData(): void
    {
        $this->validate([
            'uploadedFile' => 'required|file|max:10240',
        ], [
            'uploadedFile.required' => 'Veuillez sélectionner un fichier.',
            'uploadedFile.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        $this->isProcessing = true;
        $this->errorMessage = null;
        $this->showPreview = false;
        $this->showResult = false;

        try {
            $tier = $this->getUserTier();
            $service = new InvoiceConverterService();

            // Vérifier le mime type
            $mime = $this->uploadedFile->getMimeType();
            if (!in_array($mime, InvoiceConverterService::SUPPORTED_MIMES)) {
                throw new \Exception("Format non supporté ({$mime}). Formats acceptés : PDF, JPEG, PNG, WebP, Excel, CSV.");
            }

            // Créer l'enregistrement de conversion
            $conversion = InvoiceConversion::create([
                'user_id' => auth()->id(),
                'company_id' => Filament::getTenant()?->id,
                'original_filename' => $this->uploadedFile->getClientOriginalName(),
                'original_mime_type' => $mime,
                'original_size' => $this->uploadedFile->getSize(),
                'ai_provider' => $tier === InvoiceConverterService::TIER_PRO ? 'claude' : 'gemini',
                'tier' => $tier,
                'status' => InvoiceConversion::STATUS_PROCESSING,
                'ip_address' => request()->ip(),
            ]);

            $this->conversionId = $conversion->id;

            // Extraction via IA
            $startTime = microtime(true);
            $data = $service->processFile($this->uploadedFile, $tier);
            $endTime = microtime(true);
            $this->processingTimeMs = (int)(($endTime - $startTime) * 1000);

            // Stocker les données extraites
            $this->extractedData = $data;
            $this->aiProvider = $service->getExtractor($tier)->getProviderName();

            // Remplir les champs éditables
            $this->fillFormFromData($data);

            $conversion->update([
                'status' => InvoiceConversion::STATUS_COMPLETED,
                'extracted_data' => $data,
                'processing_time_ms' => $this->processingTimeMs,
            ]);

            $this->showPreview = true;
            $this->isProcessing = false;

            Notification::make()
                ->title('Extraction réussie')
                ->body("Données extraites en {$this->processingTimeMs}ms via {$this->aiProvider}")
                ->success()
                ->send();

        } catch (\Throwable $e) {
            $this->isProcessing = false;
            $this->errorMessage = $e->getMessage();

            if (isset($conversion)) {
                $conversion->markFailed($e->getMessage());
            }

            Log::error('InvoiceConverter: Extraction failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Notification::make()
                ->title('Erreur d\'extraction')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Remplir le formulaire avec les données extraites
     */
    protected function fillFormFromData(array $data): void
    {
        $seller = $data['seller'] ?? [];
        $buyer = $data['buyer'] ?? [];
        $invoice = $data['invoice'] ?? [];
        $totals = $data['totals'] ?? [];

        $this->sellerName = $seller['name'] ?? '';
        $this->sellerAddress = $seller['address'] ?? '';
        $this->sellerZipCode = $seller['zip_code'] ?? '';
        $this->sellerCity = $seller['city'] ?? '';
        $this->sellerSiret = $seller['siret'] ?? '';
        $this->sellerVatNumber = $seller['vat_number'] ?? '';

        $this->buyerName = $buyer['name'] ?? '';
        $this->buyerAddress = $buyer['address'] ?? '';
        $this->buyerZipCode = $buyer['zip_code'] ?? '';
        $this->buyerCity = $buyer['city'] ?? '';
        $this->buyerSiret = $buyer['siret'] ?? '';
        $this->buyerVatNumber = $buyer['vat_number'] ?? '';

        $this->invoiceNumber = $invoice['number'] ?? '';
        $this->invoiceDate = $invoice['date'] ?? date('Y-m-d');
        $this->invoiceDueDate = $invoice['due_date'] ?? '';
        $this->invoiceCurrency = $invoice['currency'] ?? 'EUR';
        $this->invoiceNotes = $invoice['notes'] ?? '';

        $this->lines = $data['lines'] ?? [];
        $this->totalHt = (string)($totals['total_ht'] ?? 0);
        $this->totalVat = (string)($totals['total_vat'] ?? 0);
        $this->totalTtc = (string)($totals['total_ttc'] ?? 0);
    }

    /**
     * Reconstruire les données depuis le formulaire édité
     */
    protected function buildDataFromForm(): array
    {
        return [
            'seller' => [
                'name' => $this->sellerName,
                'address' => $this->sellerAddress,
                'zip_code' => $this->sellerZipCode,
                'city' => $this->sellerCity,
                'country_code' => 'FR',
                'siret' => $this->sellerSiret ?: null,
                'vat_number' => $this->sellerVatNumber ?: null,
            ],
            'buyer' => [
                'name' => $this->buyerName,
                'address' => $this->buyerAddress,
                'zip_code' => $this->buyerZipCode,
                'city' => $this->buyerCity,
                'country_code' => 'FR',
                'siret' => $this->buyerSiret ?: null,
                'vat_number' => $this->buyerVatNumber ?: null,
            ],
            'invoice' => [
                'number' => $this->invoiceNumber,
                'date' => $this->invoiceDate,
                'due_date' => $this->invoiceDueDate ?: null,
                'currency' => $this->invoiceCurrency,
                'notes' => $this->invoiceNotes ?: null,
            ],
            'lines' => $this->lines,
            'totals' => [
                'total_ht' => (float) $this->totalHt,
                'total_vat' => (float) $this->totalVat,
                'total_ttc' => (float) $this->totalTtc,
            ],
            'vat_breakdown' => $this->extractedData['vat_breakdown'] ?? [],
        ];
    }

    /**
     * Ajouter une ligne de facture
     */
    public function addLine(): void
    {
        $this->lines[] = [
            'description' => '',
            'quantity' => 1,
            'unit_price_ht' => 0,
            'vat_rate' => 20,
            'total_ht' => 0,
        ];
    }

    /**
     * Supprimer une ligne de facture
     */
    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        $this->recalculateTotals();
    }

    /**
     * Recalculer les totaux
     */
    public function recalculateTotals(): void
    {
        $totalHt = 0;
        $totalVat = 0;

        foreach ($this->lines as &$line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $pu = (float) ($line['unit_price_ht'] ?? 0);
            $vatRate = (float) ($line['vat_rate'] ?? 20);

            $lineTotal = $qty * $pu;
            $line['total_ht'] = round($lineTotal, 2);

            $totalHt += $lineTotal;
            $totalVat += $lineTotal * $vatRate / 100;
        }

        $this->totalHt = (string) round($totalHt, 2);
        $this->totalVat = (string) round($totalVat, 2);
        $this->totalTtc = (string) round($totalHt + $totalVat, 2);
    }

    /**
     * Générer le Factur-X PDF + XML
     */
    public function generateFacturX(): void
    {
        $this->validate([
            'sellerName' => 'required|string|min:2',
            'buyerName' => 'required|string|min:2',
            'invoiceNumber' => 'required|string|min:1',
            'invoiceDate' => 'required|date',
        ], [
            'sellerName.required' => 'Le nom de l\'émetteur est obligatoire.',
            'buyerName.required' => 'Le nom du destinataire est obligatoire.',
            'invoiceNumber.required' => 'Le numéro de facture est obligatoire.',
            'invoiceDate.required' => 'La date de facture est obligatoire.',
        ]);

        try {
            $data = $this->buildDataFromForm();
            $service = new InvoiceConverterService();

            // Pas de watermark pour les utilisateurs pro
            $tier = $this->getUserTier();
            $options = [
                'show_watermark' => $tier !== InvoiceConverterService::TIER_PRO,
            ];

            $pdfPath = $service->generateFacturXPdf($data, $options);
            $xmlPath = str_replace('.pdf', '.xml', $pdfPath);

            // Mettre à jour la conversion
            if ($this->conversionId) {
                $conversion = InvoiceConversion::find($this->conversionId);
                $conversion?->update([
                    'output_pdf_path' => $pdfPath,
                    'output_xml_path' => $xmlPath,
                    'extracted_data' => $data,
                ]);
            }

            $this->showResult = true;
            $this->downloadUrl = route('invoice-converter.download', ['id' => $this->conversionId]);

            $this->loadRecentConversions();

            Notification::make()
                ->title('Factur-X généré !')
                ->body('Votre facture Factur-X est prête au téléchargement.')
                ->success()
                ->send();

        } catch (\Throwable $e) {
            Log::error('InvoiceConverter: Generation failed', [
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Erreur de génération')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Télécharger le PDF
     */
    public function downloadPdf()
    {
        if (!$this->conversionId) return;

        $conversion = InvoiceConversion::find($this->conversionId);
        if (!$conversion || !$conversion->output_pdf_path) return;

        $conversion->update(['status' => InvoiceConversion::STATUS_DOWNLOADED]);

        $path = Storage::disk('local')->path($conversion->output_pdf_path);
        return response()->download($path, 'facturx_' . $conversion->original_filename . '.pdf');
    }

    /**
     * Télécharger le XML CII
     */
    public function downloadXml()
    {
        if (!$this->conversionId) return;

        $conversion = InvoiceConversion::find($this->conversionId);
        if (!$conversion || !$conversion->output_xml_path) return;

        $path = Storage::disk('local')->path($conversion->output_xml_path);
        return response()->download($path, 'facturx_' . $conversion->original_filename . '.xml');
    }

    /**
     * Réinitialiser le formulaire
     */
    public function resetForm(): void
    {
        $this->uploadedFile = null;
        $this->extractedData = null;
        $this->conversionId = null;
        $this->showPreview = false;
        $this->showResult = false;
        $this->isProcessing = false;
        $this->errorMessage = null;
        $this->downloadUrl = null;
        $this->aiProvider = null;
        $this->processingTimeMs = null;
        $this->lines = [];
        $this->sellerName = '';
        $this->sellerAddress = '';
        $this->sellerZipCode = '';
        $this->sellerCity = '';
        $this->sellerSiret = '';
        $this->sellerVatNumber = '';
        $this->buyerName = '';
        $this->buyerAddress = '';
        $this->buyerZipCode = '';
        $this->buyerCity = '';
        $this->buyerSiret = '';
        $this->buyerVatNumber = '';
        $this->invoiceNumber = '';
        $this->invoiceDate = '';
        $this->invoiceDueDate = '';
        $this->invoiceCurrency = 'EUR';
        $this->invoiceNotes = '';
        $this->totalHt = '0';
        $this->totalVat = '0';
        $this->totalTtc = '0';
    }

    /**
     * Retélécharger une conversion précédente
     */
    public function redownload(int $id)
    {
        $conversion = InvoiceConversion::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$conversion || !$conversion->output_pdf_path) {
            Notification::make()
                ->title('Fichier introuvable')
                ->danger()
                ->send();
            return;
        }

        $path = Storage::disk('local')->path($conversion->output_pdf_path);
        if (!file_exists($path)) {
            Notification::make()
                ->title('Le fichier a expiré')
                ->body('Les fichiers convertis sont conservés 7 jours.')
                ->danger()
                ->send();
            return;
        }

        return response()->download($path, 'facturx_' . $conversion->original_filename . '.pdf');
    }
}
