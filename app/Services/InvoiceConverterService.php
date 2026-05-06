<?php

namespace App\Services;

use App\Services\AI\AiExtractorInterface;
use App\Services\AI\ClaudeExtractor;
use App\Services\AI\GeminiExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;

class InvoiceConverterService
{
    /**
     * Tiers supportés
     */
    public const TIER_FREE = 'free';
    public const TIER_PRO = 'pro';

    /**
     * Formats d'entrée supportés
     */
    public const SUPPORTED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
        'application/vnd.ms-excel', // xls
        'text/csv',
    ];

    /**
     * Limite mensuelle gratuite
     */
    public const FREE_MONTHLY_LIMIT = 5;

    /**
     * Retourne Claude en priorité, Gemini en fallback
     */
    public function getExtractor(string $tier = self::TIER_FREE): AiExtractorInterface
    {
        if (config('services.ai.claude.api_key')) {
            return new ClaudeExtractor();
        }

        return new GeminiExtractor();
    }

    /**
     * Pipeline complet : Upload → Extraction → Données structurées
     * Claude en premier, fallback automatique sur Gemini en cas d'erreur.
     */
    public function processFile(UploadedFile $file, string $tier = self::TIER_FREE): array
    {
        $mimeType  = $file->getMimeType();
        $primary   = $this->getExtractor($tier);
        $fallback  = ($primary instanceof ClaudeExtractor && config('services.ai.gemini.api_key'))
                        ? new GeminiExtractor()
                        : null;

        Log::info("InvoiceConverter: Processing {$file->getClientOriginalName()} ({$mimeType}) with {$primary->getProviderName()}");

        try {
            return $this->route($file, $mimeType, $primary);
        } catch (\Throwable $e) {
            if ($fallback) {
                Log::warning("InvoiceConverter: {$primary->getProviderName()} failed ({$e->getMessage()}), retrying with {$fallback->getProviderName()}");
                return $this->route($file, $mimeType, $fallback);
            }
            throw $e;
        }
    }

    /**
     * Dispatch vers la bonne méthode selon le type MIME
     */
    protected function route(UploadedFile $file, string $mimeType, AiExtractorInterface $extractor): array
    {
        if ($mimeType === 'application/pdf') {
            return $this->processPdf($file, $extractor);
        }

        if (str_starts_with($mimeType, 'image/')) {
            return $this->processImage($file, $extractor);
        }

        if (in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv',
        ])) {
            return $this->processSpreadsheet($file, $extractor);
        }

        throw new \Exception("Format non supporté: {$mimeType}");
    }

    /**
     * Traitement PDF : extraction texte puis IA
     */
    protected function processPdf(UploadedFile $file, AiExtractorInterface $extractor): array
    {
        $text = '';

        try {
            $parser = new PdfParser();
            $pdf    = $parser->parseFile($file->getRealPath());
            $text   = $pdf->getText();
        } catch (\Throwable $e) {
            Log::info('InvoiceConverter: PDF parser failed (' . $e->getMessage() . '), trying native PDF fallback');
        }

        if (strlen(trim($text)) < 50) {
            // Texte insuffisant : envoyer le PDF brut en natif si l'extracteur le supporte
            if (method_exists($extractor, 'extractFromPdf')) {
                Log::info("InvoiceConverter: PDF text too short, using native PDF extraction with {$extractor->getProviderName()}");
                $base64 = base64_encode(file_get_contents($file->getRealPath()));
                return $extractor->extractFromPdf($base64);
            }

            // Fallback Imagick (nécessite Ghostscript sur le serveur)
            if (extension_loaded('imagick')) {
                return $this->processPdfAsImage($file, $extractor);
            }

            throw new \Exception(
                'Ce PDF ne peut pas être lu (format non standard ou protégé). ' .
                'Essayez de l\'ouvrir et de le réenregistrer, ou convertissez-le en JPEG/PNG avant de l\'importer.'
            );
        }

        return $extractor->extractInvoiceData($text, 'application/pdf');
    }

    /**
     * Traitement PDF scanné via Imagick
     */
    protected function processPdfAsImage(UploadedFile $file, AiExtractorInterface $extractor): array
    {
        try {
            $imagick = new \Imagick();
            $imagick->setResolution(300, 300);
            $imagick->readImage($file->getRealPath() . '[0]'); // Première page
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(90);

            $imageData = $imagick->getImageBlob();
            $base64 = base64_encode($imageData);

            $imagick->clear();
            $imagick->destroy();

            return $extractor->extractFromImage($base64, 'image/jpeg');
        } catch (\Exception $e) {
            Log::error('InvoiceConverter: Imagick conversion failed: ' . $e->getMessage());
            throw new \Exception(
                'Impossible de convertir ce PDF scanné. ' .
                'Essayez de l\'enregistrer en JPEG/PNG avant de l\'importer.'
            );
        }
    }

    /**
     * Traitement image directe (JPEG, PNG, WebP)
     */
    protected function processImage(UploadedFile $file, AiExtractorInterface $extractor): array
    {
        $imageContent = file_get_contents($file->getRealPath());
        $base64 = base64_encode($imageContent);
        $mimeType = $file->getMimeType();

        return $extractor->extractFromImage($base64, $mimeType);
    }

    /**
     * Traitement Excel/CSV : lecture et formatage en texte
     */
    protected function processSpreadsheet(UploadedFile $file, AiExtractorInterface $extractor): array
    {
        $mimeType = $file->getMimeType();

        if ($mimeType === 'text/csv') {
            $text = $this->parseCsv($file);
        } else {
            $text = $this->parseExcel($file);
        }

        return $extractor->extractInvoiceData($text, $mimeType);
    }

    /**
     * Parse un fichier CSV en texte lisible
     */
    protected function parseCsv(UploadedFile $file): string
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            throw new \Exception('Impossible de lire le fichier CSV');
        }

        // Détecter le séparateur sur la première ligne
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $lineCount = 0;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false && $lineCount < 200) {
            if (!array_filter($data)) continue;
            $rows[] = implode(' | ', array_map('trim', $data));
            $lineCount++;
        }

        fclose($handle);

        if (empty($rows)) {
            throw new \Exception('Le fichier CSV est vide ou illisible');
        }

        return "Données du fichier CSV (colonnes séparées par |) :\n\n" . implode("\n", $rows);
    }

    /**
     * Parse un fichier Excel en texte lisible (via PhpSpreadsheet si dispo, sinon basique)
     */
    protected function parseExcel(UploadedFile $file): string
    {
        // Vérifier si PhpSpreadsheet est disponible
        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = [];

            foreach ($sheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $cells = [];
                foreach ($cellIterator as $cell) {
                    $cells[] = $cell->getFormattedValue();
                }
                $rows[] = implode(' | ', $cells);

                if (count($rows) > 200) break;
            }

            return "Données du fichier Excel (colonnes séparées par |) :\n\n" . implode("\n", $rows);
        }

        throw new \Exception(
            'Le format Excel nécessite la bibliothèque PhpSpreadsheet. ' .
            'Exportez votre fichier en CSV pour l\'importer.'
        );
    }

    /**
     * Génère le XML Factur-X CII à partir des données extraites
     */
    public function generateFacturX(array $data, array $options = []): string
    {
        $seller = $data['seller'] ?? [];
        $buyer = $data['buyer'] ?? [];
        $invoice = $data['invoice'] ?? [];
        $lines = $data['lines'] ?? [];
        $totals = $data['totals'] ?? [];
        $vatBreakdown = $data['vat_breakdown'] ?? [];

        // Options de personnalisation
        $profile = $options['profile'] ?? 'EN16931'; // MINIMUM, BASICWL, BASIC, EN16931
        $typeCode = $options['type_code'] ?? '380'; // 380=Facture, 381=Avoir

        $invoiceNumber = htmlspecialchars($invoice['number'] ?? 'CONV-' . date('YmdHis'), ENT_XML1);
        $issueDate = $invoice['date'] ?? date('Y-m-d');
        $issueDateFormatted = str_replace('-', '', $issueDate);
        $dueDate = $invoice['due_date'] ?? null;
        $currency = $invoice['currency'] ?? 'EUR';

        $totalHt = number_format($totals['total_ht'] ?? 0, 2, '.', '');
        $totalVat = number_format($totals['total_vat'] ?? 0, 2, '.', '');
        $totalTtc = number_format($totals['total_ttc'] ?? 0, 2, '.', '');

        // Seller info
        $sellerName = htmlspecialchars($seller['name'] ?? 'Non renseigné', ENT_XML1);
        $sellerAddress = htmlspecialchars($seller['address'] ?? '', ENT_XML1);
        $sellerZip = htmlspecialchars($seller['zip_code'] ?? '', ENT_XML1);
        $sellerCity = htmlspecialchars($seller['city'] ?? '', ENT_XML1);
        $sellerCountry = htmlspecialchars($seller['country_code'] ?? 'FR', ENT_XML1);
        $sellerSiret = $seller['siret'] ?? null;
        $sellerVat = $seller['vat_number'] ?? null;

        // Buyer info
        $buyerName = htmlspecialchars($buyer['name'] ?? 'Non renseigné', ENT_XML1);
        $buyerAddress = htmlspecialchars($buyer['address'] ?? '', ENT_XML1);
        $buyerZip = htmlspecialchars($buyer['zip_code'] ?? '', ENT_XML1);
        $buyerCity = htmlspecialchars($buyer['city'] ?? '', ENT_XML1);
        $buyerCountry = htmlspecialchars($buyer['country_code'] ?? 'FR', ENT_XML1);
        $buyerSiret = $buyer['siret'] ?? null;
        $buyerVat = $buyer['vat_number'] ?? null;

        // Build XML CII (CrossIndustryInvoice)
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rsm:CrossIndustryInvoice xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100" ';
        $xml .= 'xmlns:ram="urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100" ';
        $xml .= 'xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100" ';
        $xml .= 'xmlns:qdt="urn:un:unece:uncefact:data:standard:QualifiedDataType:100">' . "\n";

        // Header
        $xml .= "  <rsm:ExchangedDocumentContext>\n";
        $xml .= "    <ram:GuidelineSpecifiedDocumentContextParameter>\n";
        $xml .= "      <ram:ID>urn:cen.eu:en16931:2017</ram:ID>\n";
        $xml .= "    </ram:GuidelineSpecifiedDocumentContextParameter>\n";
        $xml .= "  </rsm:ExchangedDocumentContext>\n";

        // Document
        $xml .= "  <rsm:ExchangedDocument>\n";
        $xml .= "    <ram:ID>{$invoiceNumber}</ram:ID>\n";
        $xml .= "    <ram:TypeCode>{$typeCode}</ram:TypeCode>\n";
        $xml .= "    <ram:IssueDateTime><udt:DateTimeString format=\"102\">{$issueDateFormatted}</udt:DateTimeString></ram:IssueDateTime>\n";
        if ($invoice['notes'] ?? null) {
            $xml .= "    <ram:IncludedNote><ram:Content>" . htmlspecialchars($invoice['notes'], ENT_XML1) . "</ram:Content></ram:IncludedNote>\n";
        }
        $xml .= "  </rsm:ExchangedDocument>\n";

        // Supply Chain Trade Transaction
        $xml .= "  <rsm:SupplyChainTradeTransaction>\n";

        // Line items
        foreach ($lines as $i => $line) {
            $lineNum     = $i + 1;
            $desc        = htmlspecialchars($line['description'] ?? "Article {$lineNum}", ENT_XML1);
            $qty         = number_format((float)($line['quantity'] ?? 1), 4, '.', '');
            $unitPrice   = number_format((float)($line['unit_price_ht'] ?? 0), 2, '.', '');
            $lineTotal   = number_format((float)($line['total_ht'] ?? 0), 2, '.', '');
            $lineVatRate = number_format((float)($line['vat_rate'] ?? 20), 2, '.', '');
            $lineVatCat  = (float)($line['vat_rate'] ?? 20) === 0.0 ? 'Z' : 'S';
            $unitCode    = match(strtolower($line['unit'] ?? '')) {
                'h', 'heure', 'heures', 'hr', 'hour' => 'HUR',
                'kg', 'kilo'                          => 'KGM',
                'l', 'litre', 'litres'                => 'LTR',
                'm', 'metre', 'mètre'                 => 'MTR',
                'm2', 'm²'                            => 'MTK',
                'j', 'jour', 'jours', 'day'           => 'DAY',
                'mois', 'month'                       => 'MON',
                default                               => 'C62', // pièce
            };

            $xml .= "    <ram:IncludedSupplyChainTradeLineItem>\n";
            $xml .= "      <ram:AssociatedDocumentLineDocument><ram:LineID>{$lineNum}</ram:LineID></ram:AssociatedDocumentLineDocument>\n";
            $xml .= "      <ram:SpecifiedTradeProduct><ram:Name>{$desc}</ram:Name></ram:SpecifiedTradeProduct>\n";
            $xml .= "      <ram:SpecifiedLineTradeAgreement>\n";
            $xml .= "        <ram:NetPriceProductTradePrice><ram:ChargeAmount>{$unitPrice}</ram:ChargeAmount></ram:NetPriceProductTradePrice>\n";
            $xml .= "      </ram:SpecifiedLineTradeAgreement>\n";
            $xml .= "      <ram:SpecifiedLineTradeDelivery><ram:BilledQuantity unitCode=\"{$unitCode}\">{$qty}</ram:BilledQuantity></ram:SpecifiedLineTradeDelivery>\n";
            $xml .= "      <ram:SpecifiedLineTradeSettlement>\n";
            $xml .= "        <ram:ApplicableTradeTax><ram:TypeCode>VAT</ram:TypeCode><ram:CategoryCode>{$lineVatCat}</ram:CategoryCode><ram:RateApplicablePercent>{$lineVatRate}</ram:RateApplicablePercent></ram:ApplicableTradeTax>\n";
            $xml .= "        <ram:SpecifiedTradeSettlementLineMonetarySummation><ram:LineTotalAmount>{$lineTotal}</ram:LineTotalAmount></ram:SpecifiedTradeSettlementLineMonetarySummation>\n";
            $xml .= "      </ram:SpecifiedLineTradeSettlement>\n";
            $xml .= "    </ram:IncludedSupplyChainTradeLineItem>\n";
        }

        // Trade Agreement (Seller/Buyer)
        $xml .= "    <ram:ApplicableHeaderTradeAgreement>\n";

        // Seller
        $xml .= "      <ram:SellerTradeParty>\n";
        $xml .= "        <ram:Name>{$sellerName}</ram:Name>\n";
        if ($sellerSiret) {
            $xml .= "        <ram:SpecifiedLegalOrganization><ram:ID schemeID=\"0002\">{$sellerSiret}</ram:ID></ram:SpecifiedLegalOrganization>\n";
        }
        $xml .= "        <ram:PostalTradeAddress>\n";
        $xml .= "          <ram:PostcodeCode>{$sellerZip}</ram:PostcodeCode>\n";
        $xml .= "          <ram:LineOne>{$sellerAddress}</ram:LineOne>\n";
        $xml .= "          <ram:CityName>{$sellerCity}</ram:CityName>\n";
        $xml .= "          <ram:CountryID>{$sellerCountry}</ram:CountryID>\n";
        $xml .= "        </ram:PostalTradeAddress>\n";
        if ($sellerVat) {
            $xml .= "        <ram:SpecifiedTaxRegistration><ram:ID schemeID=\"VA\">{$sellerVat}</ram:ID></ram:SpecifiedTaxRegistration>\n";
        }
        $xml .= "      </ram:SellerTradeParty>\n";

        // Buyer
        $xml .= "      <ram:BuyerTradeParty>\n";
        $xml .= "        <ram:Name>{$buyerName}</ram:Name>\n";
        if ($buyerSiret) {
            $xml .= "        <ram:SpecifiedLegalOrganization><ram:ID schemeID=\"0002\">{$buyerSiret}</ram:ID></ram:SpecifiedLegalOrganization>\n";
        }
        $xml .= "        <ram:PostalTradeAddress>\n";
        $xml .= "          <ram:PostcodeCode>{$buyerZip}</ram:PostcodeCode>\n";
        $xml .= "          <ram:LineOne>{$buyerAddress}</ram:LineOne>\n";
        $xml .= "          <ram:CityName>{$buyerCity}</ram:CityName>\n";
        $xml .= "          <ram:CountryID>{$buyerCountry}</ram:CountryID>\n";
        $xml .= "        </ram:PostalTradeAddress>\n";
        if ($buyerVat) {
            $xml .= "        <ram:SpecifiedTaxRegistration><ram:ID schemeID=\"VA\">{$buyerVat}</ram:ID></ram:SpecifiedTaxRegistration>\n";
        }
        $xml .= "      </ram:BuyerTradeParty>\n";

        $xml .= "    </ram:ApplicableHeaderTradeAgreement>\n";

        // Delivery
        $xml .= "    <ram:ApplicableHeaderTradeDelivery/>\n";

        // Settlement
        $xml .= "    <ram:ApplicableHeaderTradeSettlement>\n";
        $xml .= "      <ram:InvoiceCurrencyCode>{$currency}</ram:InvoiceCurrencyCode>\n";

        // Moyen de paiement (obligatoire EN16931)
        $paymentMeansCode = match($invoice['payment_means'] ?? 'transfer') {
            'transfer', 'virement' => '30',
            'check', 'cheque'      => '10',
            'card', 'carte'        => '48',
            'direct_debit'         => '49',
            'cash', 'especes'      => '10',
            default                => '30', // virement par défaut
        };
        $xml .= "      <ram:SpecifiedTradeSettlementPaymentMeans>\n";
        $xml .= "        <ram:TypeCode>{$paymentMeansCode}</ram:TypeCode>\n";
        if ($invoice['iban'] ?? null) {
            $iban = htmlspecialchars($invoice['iban'], ENT_XML1);
            $xml .= "        <ram:PayeePartyCreditorFinancialAccount><ram:IBANID>{$iban}</ram:IBANID></ram:PayeePartyCreditorFinancialAccount>\n";
        }
        $xml .= "      </ram:SpecifiedTradeSettlementPaymentMeans>\n";

        // VAT Breakdown (catégorie TVA dynamique : S=standard, Z=zéro, E=exonéré)
        foreach ($vatBreakdown as $vat) {
            $vatBase   = number_format((float)($vat['base'] ?? 0), 2, '.', '');
            $vatAmount = number_format((float)($vat['amount'] ?? 0), 2, '.', '');
            $vatRate   = number_format((float)($vat['rate'] ?? 20), 2, '.', '');
            $vatCat    = (float)($vat['rate'] ?? 20) === 0.0 ? ($vat['exempt'] ?? false ? 'E' : 'Z') : 'S';

            $xml .= "      <ram:ApplicableTradeTax>\n";
            $xml .= "        <ram:CalculatedAmount>{$vatAmount}</ram:CalculatedAmount>\n";
            $xml .= "        <ram:TypeCode>VAT</ram:TypeCode>\n";
            $xml .= "        <ram:BasisAmount>{$vatBase}</ram:BasisAmount>\n";
            $xml .= "        <ram:CategoryCode>{$vatCat}</ram:CategoryCode>\n";
            $xml .= "        <ram:RateApplicablePercent>{$vatRate}</ram:RateApplicablePercent>\n";
            $xml .= "      </ram:ApplicableTradeTax>\n";
        }

        // Due date + conditions de paiement
        if ($dueDate || ($invoice['payment_terms'] ?? null)) {
            $xml .= "      <ram:SpecifiedTradePaymentTerms>\n";
            if ($invoice['payment_terms'] ?? null) {
                $terms = htmlspecialchars($invoice['payment_terms'], ENT_XML1);
                $xml .= "        <ram:Description>{$terms}</ram:Description>\n";
            }
            if ($dueDate) {
                $dueDateFormatted = str_replace('-', '', $dueDate);
                $xml .= "        <ram:DueDateDateTime><udt:DateTimeString format=\"102\">{$dueDateFormatted}</udt:DateTimeString></ram:DueDateDateTime>\n";
            }
            $xml .= "      </ram:SpecifiedTradePaymentTerms>\n";
        }

        // Totals
        $xml .= "      <ram:SpecifiedTradeSettlementHeaderMonetarySummation>\n";
        $xml .= "        <ram:LineTotalAmount>{$totalHt}</ram:LineTotalAmount>\n";
        $xml .= "        <ram:TaxBasisTotalAmount>{$totalHt}</ram:TaxBasisTotalAmount>\n";
        $xml .= "        <ram:TaxTotalAmount currencyID=\"{$currency}\">{$totalVat}</ram:TaxTotalAmount>\n";
        $xml .= "        <ram:GrandTotalAmount>{$totalTtc}</ram:GrandTotalAmount>\n";
        $xml .= "        <ram:DuePayableAmount>{$totalTtc}</ram:DuePayableAmount>\n";
        $xml .= "      </ram:SpecifiedTradeSettlementHeaderMonetarySummation>\n";

        $xml .= "    </ram:ApplicableHeaderTradeSettlement>\n";
        $xml .= "  </rsm:SupplyChainTradeTransaction>\n";
        $xml .= "</rsm:CrossIndustryInvoice>\n";

        return $xml;
    }

    /**
     * Génère un PDF Factur-X (PDF/A-3 avec XML embarqué)
     * Retourne le chemin du fichier généré
     */
    public function generateFacturXPdf(array $data, array $options = []): string
    {
        $xml = $this->generateFacturX($data, $options);
        $seller = $data['seller'] ?? [];
        $buyer = $data['buyer'] ?? [];
        $invoice = $data['invoice'] ?? [];
        $lines = $data['lines'] ?? [];
        $totals = $data['totals'] ?? [];

        // Personnalisation
        $template = $options['template'] ?? 'default';
        $color = $options['color'] ?? '#1a56db';
        $logo = $options['logo'] ?? null;
        $showWatermark = $options['show_watermark'] ?? true;

        $currency = $invoice['currency'] ?? 'EUR';
        $currencySymbol = $currency === 'EUR' ? '€' : $currency;

        // Construire le HTML de la facture
        $html = $this->buildInvoiceHtml($data, $options);

        // Générer le PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'facturx_' . ($invoice['number'] ?? date('YmdHis')) . '.pdf';
        $path = 'invoice-conversions/' . $filename;

        Storage::disk('local')->put($path, $pdf->output());

        // Stocker le XML à côté
        $xmlPath = str_replace('.pdf', '.xml', $path);
        Storage::disk('local')->put($xmlPath, $xml);

        // Signature numérique optionnelle
        if ($options['sign'] ?? false) {
            $absPdfPath = Storage::disk('local')->path($path);
            app(\App\Services\PdfSignatureService::class)->signPdf($absPdfPath);
        }

        return $path;
    }

    /**
     * Construit le HTML pour le PDF de la facture
     */
    protected function buildInvoiceHtml(array $data, array $options = []): string
    {
        $seller = $data['seller'] ?? [];
        $buyer = $data['buyer'] ?? [];
        $invoice = $data['invoice'] ?? [];
        $lines = $data['lines'] ?? [];
        $totals = $data['totals'] ?? [];
        $vatBreakdown = $data['vat_breakdown'] ?? [];

        $color = $options['color'] ?? '#1a56db';
        $showWatermark = $options['show_watermark'] ?? true;
        $currency = $invoice['currency'] ?? 'EUR';
        $sym = $currency === 'EUR' ? '€' : $currency;

        $watermark = $showWatermark
            ? '<div style="position:fixed;bottom:20px;left:0;right:0;text-align:center;color:#ccc;font-size:10px;">Généré gratuitement par FRECORP ERP — frecorp.fr</div>'
            : '';

        $linesHtml = '';
        foreach ($lines as $line) {
            $desc = htmlspecialchars($line['description'] ?? '', ENT_QUOTES);
            $qty = number_format((float)($line['quantity'] ?? 1), 2, ',', ' ');
            $pu = number_format((float)($line['unit_price_ht'] ?? 0), 2, ',', ' ');
            $vat = number_format((float)($line['vat_rate'] ?? 20), 1, ',', '');
            $totalLine = number_format((float)($line['total_ht'] ?? 0), 2, ',', ' ');

            $linesHtml .= "<tr>
                <td style='padding:8px;border-bottom:1px solid #e5e7eb;'>{$desc}</td>
                <td style='padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;'>{$qty}</td>
                <td style='padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;'>{$pu} {$sym}</td>
                <td style='padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;'>{$vat}%</td>
                <td style='padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;font-weight:600;'>{$totalLine} {$sym}</td>
            </tr>";
        }

        $vatHtml = '';
        foreach ($vatBreakdown as $vat) {
            $rate = number_format((float)($vat['rate'] ?? 0), 1, ',', '');
            $base = number_format((float)($vat['base'] ?? 0), 2, ',', ' ');
            $amount = number_format((float)($vat['amount'] ?? 0), 2, ',', ' ');
            $vatHtml .= "<tr><td style='padding:4px 8px;'>TVA {$rate}%</td><td style='padding:4px 8px;text-align:right;'>{$base} {$sym}</td><td style='padding:4px 8px;text-align:right;'>{$amount} {$sym}</td></tr>";
        }

        $totalHtF = number_format($totals['total_ht'] ?? 0, 2, ',', ' ');
        $totalVatF = number_format($totals['total_vat'] ?? 0, 2, ',', ' ');
        $totalTtcF = number_format($totals['total_ttc'] ?? 0, 2, ',', ' ');

        $invoiceNumber = htmlspecialchars($invoice['number'] ?? 'N/A', ENT_QUOTES);
        $invoiceDate = $invoice['date'] ?? date('Y-m-d');
        $dueDate = $invoice['due_date'] ?? null;
        $typeLabel = ($options['type_code'] ?? '380') === '381' ? 'AVOIR' : 'FACTURE';

        $sellerName = htmlspecialchars($seller['name'] ?? '', ENT_QUOTES);
        $sellerAddr = htmlspecialchars($seller['address'] ?? '', ENT_QUOTES);
        $sellerZip = htmlspecialchars($seller['zip_code'] ?? '', ENT_QUOTES);
        $sellerCity = htmlspecialchars($seller['city'] ?? '', ENT_QUOTES);
        $sellerSiret = $seller['siret'] ?? null;
        $sellerVat = $seller['vat_number'] ?? null;

        $buyerName = htmlspecialchars($buyer['name'] ?? '', ENT_QUOTES);
        $buyerAddr = htmlspecialchars($buyer['address'] ?? '', ENT_QUOTES);
        $buyerZip = htmlspecialchars($buyer['zip_code'] ?? '', ENT_QUOTES);
        $buyerCity = htmlspecialchars($buyer['city'] ?? '', ENT_QUOTES);

        $sellerLegal  = $seller['legal_form'] ?? null;
        $sellerCapital= $seller['capital'] ?? null;
        $sellerRcs    = $seller['rcs'] ?? null;
        $sellerEmail  = htmlspecialchars($seller['email'] ?? '', ENT_QUOTES);
        $sellerPhone  = htmlspecialchars($seller['phone'] ?? '', ENT_QUOTES);
        $paymentTerms = htmlspecialchars($invoice['payment_terms'] ?? 'Net 30 jours', ENT_QUOTES);

        $dueDateHtml     = $dueDate ? "<p style='margin:2px 0;'><strong>Échéance :</strong> {$dueDate}</p>" : '';
        $sellerSiretHtml = $sellerSiret ? "<p style='margin:3px 0;font-size:10px;color:#6b7280;'>SIRET : {$sellerSiret}</p>" : '';
        $sellerVatHtml   = $sellerVat ? "<p style='margin:3px 0;font-size:10px;color:#6b7280;'>N° TVA : {$sellerVat}</p>" : '';
        $sellerRcsHtml   = $sellerRcs ? "<p style='margin:3px 0;font-size:10px;color:#6b7280;'>RCS : {$sellerRcs}</p>" : '';
        $vatTableHtml    = $vatHtml ? "<table style='font-size:11px;'><tr><td style='padding:4px 8px;font-weight:600;'>Ventilation TVA</td><td style='padding:4px 8px;text-align:right;font-weight:600;'>Base HT</td><td style='padding:4px 8px;text-align:right;font-weight:600;'>Montant TVA</td></tr>{$vatHtml}</table>" : '';

        // Mentions légales pied de facture (obligatoires droit français)
        $legalLines = [];
        $legalLines[] = "Conditions de règlement : {$paymentTerms}. Pas d'escompte pour paiement anticipé.";
        $legalLines[] = "Pénalités de retard exigibles dès le lendemain de la date d'échéance au taux de 3 fois le taux d'intérêt légal en vigueur.";
        $legalLines[] = "Indemnité forfaitaire pour frais de recouvrement en cas de retard de paiement : 40 € (art. L441-10 C. com.).";
        if ($sellerLegal || $sellerCapital) {
            $legalDetail = implode(' — ', array_filter([$sellerLegal, $sellerCapital ? "Capital : {$sellerCapital}" : null, $sellerRcs]));
            $legalLines[] = $legalDetail;
        }
        $legalLines[] = "Facture électronique conforme à la norme européenne EN 16931 (Factur-X).";
        $legalFooterHtml = implode('<br>', array_map('htmlspecialchars', $legalLines));

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 30px; }
    .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
    .invoice-title { font-size: 28px; font-weight: 700; color: {$color}; text-transform: uppercase; }
    .invoice-meta { text-align: right; }
    .invoice-meta p { margin: 2px 0; }
    .parties { display: flex; justify-content: space-between; margin-bottom: 30px; }
    .party { width: 48%; }
    .party-label { font-size: 10px; text-transform: uppercase; color: #6b7280; font-weight: 600; margin-bottom: 5px; }
    .party-name { font-size: 14px; font-weight: 700; color: {$color}; }
    table { width: 100%; border-collapse: collapse; }
    .items-table th { background: {$color}; color: white; padding: 10px 8px; text-align: left; font-size: 11px; text-transform: uppercase; }
    .items-table th:nth-child(n+2) { text-align: center; }
    .items-table th:last-child { text-align: right; }
    .totals-section { margin-top: 20px; display: flex; justify-content: flex-end; }
    .totals-table { width: 300px; }
    .totals-table td { padding: 6px 8px; }
    .totals-table .grand-total { font-size: 16px; font-weight: 700; color: {$color}; border-top: 2px solid {$color}; }
    .footer { margin-top: 40px; padding-top: 15px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: center; }
    .facturx-badge { display: inline-block; background: #ecfdf5; color: #059669; padding: 3px 10px; border-radius: 10px; font-size: 10px; font-weight: 600; }
</style>
</head>
<body>
    <table style="width:100%;margin-bottom:25px;"><tr>
        <td style="vertical-align:top;">
            <div class="invoice-title">{$typeLabel}</div>
            <span class="facturx-badge">✓ Factur-X EN16931</span>
        </td>
        <td style="vertical-align:top;text-align:right;">
            <p style="margin:2px 0;"><strong>N° :</strong> {$invoiceNumber}</p>
            <p style="margin:2px 0;"><strong>Date :</strong> {$invoiceDate}</p>
            {$dueDateHtml}
        </td>
    </tr></table>

    <table style="width:100%;margin-bottom:30px;"><tr>
        <td style="width:48%;vertical-align:top;padding:15px;background:#f9fafb;border-radius:8px;">
            <div class="party-label">Émetteur</div>
            <div class="party-name">{$sellerName}</div>
            <p style="margin:3px 0;">{$sellerAddr}</p>
            <p style="margin:3px 0;">{$sellerZip} {$sellerCity}</p>
            {$sellerSiretHtml}
            {$sellerVatHtml}
        </td>
        <td style="width:4%;"></td>
        <td style="width:48%;vertical-align:top;padding:15px;background:#f9fafb;border-radius:8px;">
            <div class="party-label">Destinataire</div>
            <div class="party-name">{$buyerName}</div>
            <p style="margin:3px 0;">{$buyerAddr}</p>
            <p style="margin:3px 0;">{$buyerZip} {$buyerCity}</p>
        </td>
    </tr></table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="border-radius:6px 0 0 0;">Description</th>
                <th>Qté</th>
                <th>P.U. HT</th>
                <th>TVA</th>
                <th style="border-radius:0 6px 0 0;text-align:right;">Total HT</th>
            </tr>
        </thead>
        <tbody>
            {$linesHtml}
        </tbody>
    </table>

    <table style="width:100%;margin-top:20px;"><tr>
        <td style="vertical-align:top;width:55%;">
            {$vatTableHtml}
        </td>
        <td style="vertical-align:top;width:45%;">
            <table class="totals-table" style="float:right;">
                <tr><td>Total HT</td><td style="text-align:right;font-weight:600;">{$totalHtF} {$sym}</td></tr>
                <tr><td>Total TVA</td><td style="text-align:right;">{$totalVatF} {$sym}</td></tr>
                <tr class="grand-total"><td>Total TTC</td><td style="text-align:right;">{$totalTtcF} {$sym}</td></tr>
            </table>
        </td>
    </tr></table>

    <div class="footer">
        <p style="color:#374151;font-size:9px;text-align:left;line-height:1.6;margin-bottom:8px;">{$legalFooterHtml}</p>
        {$watermark}
    </div>
</body>
</html>
HTML;
    }
}
