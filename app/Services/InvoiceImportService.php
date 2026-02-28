<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use horstoeko\zugferd\ZugferdDocumentReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Service d'import de factures fournisseurs au format UBL (EN16931) et CII (Factur-X / ZUGFeRD)
 * Conformité e-invoicing 2026
 */
class InvoiceImportService
{
    /**
     * Résultat de l'import
     */
    protected array $result = [
        'success' => false,
        'message' => '',
        'purchase_id' => null,
        'supplier' => null,
        'items_count' => 0,
        'total_ht' => 0,
        'total_vat' => 0,
        'total_ttc' => 0,
        'warnings' => [],
    ];

    /** Cache pour la vérification des colonnes en base */
    protected ?bool $supplierHasExtendedFields = null;

    /**
     * Vérifie si la migration des champs étendus fournisseurs a été appliquée
     */
    protected function checkSupplierExtendedFields(): bool
    {
        if ($this->supplierHasExtendedFields === null) {
            $this->supplierHasExtendedFields = Schema::hasColumn('suppliers', 'siret');
        }
        return $this->supplierHasExtendedFields;
    }

    /**
     * Import depuis un fichier XML (détecte automatiquement UBL ou CII)
     */
    public function importFromFile(string $filePath, int $companyId): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return $this->error('Impossible de lire le fichier.');
        }

        return $this->importFromContent($content, $companyId);
    }

    /**
     * Import depuis un contenu XML brut
     */
    public function importFromContent(string $xmlContent, int $companyId): array
    {
        $format = $this->detectFormat($xmlContent);

        try {
            return match ($format) {
                'CII' => $this->importCII($xmlContent, $companyId),
                'UBL' => $this->importUBL($xmlContent, $companyId),
                default => $this->error('Format XML non reconnu. Formats supportés : UBL (EN16931), CII (Factur-X/ZUGFeRD).'),
            };
        } catch (\Throwable $e) {
            Log::error('Erreur import facture XML: ' . $e->getMessage(), [
                'format' => $format,
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error('Erreur lors de l\'import : ' . $e->getMessage());
        }
    }

    /**
     * Détecte le format XML (UBL ou CII)
     */
    protected function detectFormat(string $xmlContent): string
    {
        // CII / Factur-X / ZUGFeRD
        if (str_contains($xmlContent, 'CrossIndustryInvoice')
            || str_contains($xmlContent, 'urn:un:unece:uncefact')
            || str_contains($xmlContent, 'rsm:')) {
            return 'CII';
        }

        // UBL
        if (str_contains($xmlContent, 'urn:oasis:names:specification:ubl')
            || str_contains($xmlContent, '<Invoice')
            || str_contains($xmlContent, '<CreditNote')
            || str_contains($xmlContent, 'cbc:')
            || str_contains($xmlContent, 'cac:')) {
            return 'UBL';
        }

        return 'unknown';
    }

    /**
     * Import CII (Factur-X / ZUGFeRD) via horstoeko/zugferd
     */
    protected function importCII(string $xmlContent, int $companyId): array
    {
        $reader = ZugferdDocumentReader::readAndGuessFromContent($xmlContent);

        // 1. Extraction des informations du document
        $documentNo = $documentTypeCode = $invoiceCurrency = $documentName = $documentLanguage = null;
        $documentDate = $effectiveDate = null;
        $reader->getDocumentInformation(
            $documentNo, $documentTypeCode, $documentDate,
            $invoiceCurrency, $taxCurrency, $documentName,
            $documentLanguage, $effectiveDate
        );

        // 2. Extraction du vendeur (= fournisseur)
        $sellerName = $sellerDescription = null;
        $sellerIds = null;
        $reader->getDocumentSeller($sellerName, $sellerIds, $sellerDescription);

        $sellerAddress = $sellerPostCode = $sellerCity = $sellerCountry = null;
        $sellerLineTwo = $sellerLineThree = null;
        $sellerSubDivision = null;
        $reader->getDocumentSellerAddress(
            $sellerAddress, $sellerLineTwo, $sellerLineThree,
            $sellerPostCode, $sellerCity, $sellerCountry, $sellerSubDivision
        );

        $sellerTaxReg = null;
        $reader->getDocumentSellerTaxRegistration($sellerTaxReg);

        $sellerLegalOrgId = $sellerLegalOrgType = $sellerLegalOrgName = null;
        $reader->getDocumentSellerLegalOrganisation($sellerLegalOrgId, $sellerLegalOrgType, $sellerLegalOrgName);

        // Contact vendeur
        $sellerEmail = $sellerPhone = null;
        if ($reader->firstDocumentSellerContact()) {
            $contactName = $contactDept = $contactFax = null;
            $reader->getDocumentSellerContact(
                $contactName, $contactDept, $sellerPhone, $contactFax, $sellerEmail
            );
        }

        // 3. Extraction des totaux
        $grandTotal = $duePayable = $lineTotal = $chargeTotal = $allowanceTotal = null;
        $taxBasisTotal = $taxTotal = $roundingAmount = $prepaidAmount = null;
        $reader->getDocumentSummation(
            $grandTotal, $duePayable, $lineTotal,
            $chargeTotal, $allowanceTotal, $taxBasisTotal,
            $taxTotal, $roundingAmount, $prepaidAmount
        );

        // 4. Extraction des lignes
        $items = [];
        if ($reader->firstDocumentPosition()) {
            do {
                $item = $this->extractCIILineItem($reader);
                if ($item) {
                    $items[] = $item;
                }
            } while ($reader->nextDocumentPosition());
        }

        // Extraire SIRET du sellerId (legal org)
        $siret = $sellerLegalOrgId ?? null;
        $siren = $siret ? substr($siret, 0, 9) : null;

        // Extraire TVA intracommunautaire
        $taxNumber = null;
        if ($sellerTaxReg && is_array($sellerTaxReg)) {
            foreach ($sellerTaxReg as $type => $value) {
                if ($type === 'VA' || str_starts_with($value, 'FR')) {
                    $taxNumber = $value;
                    break;
                }
            }
            // Fallback : prendre la première valeur
            if (!$taxNumber) {
                $taxNumber = reset($sellerTaxReg);
            }
        }

        // 5. Créer en base
        return $this->createPurchaseFromParsedData(
            companyId: $companyId,
            invoiceNumber: $documentNo,
            supplierName: $sellerName ?? 'Fournisseur inconnu',
            supplierSiret: $siret,
            supplierSiren: $siren,
            supplierTaxNumber: $taxNumber,
            supplierEmail: $sellerEmail,
            supplierPhone: $sellerPhone,
            supplierAddress: $sellerAddress,
            supplierZipCode: $sellerPostCode,
            supplierCity: $sellerCity,
            supplierCountry: $sellerCountry,
            supplierCountryCode: $sellerCountry, // CII uses ISO country code
            totalHt: $taxBasisTotal ?? $lineTotal ?? 0,
            totalVat: $taxTotal ?? 0,
            totalTtc: $grandTotal ?? 0,
            items: $items,
            currency: $invoiceCurrency ?? 'EUR',
            documentDate: $documentDate,
        );
    }

    /**
     * Extraire une ligne article CII
     */
    protected function extractCIILineItem(ZugferdDocumentReader $reader): ?array
    {
        // Détails produit
        $productName = $productDesc = $sellerProductId = $buyerProductId = null;
        $globalIdType = $globalId = null;
        $reader->getDocumentPositionProductDetails(
            $productName, $productDesc, $sellerProductId,
            $buyerProductId, $globalIdType, $globalId
        );

        // Prix net unitaire
        $netPrice = $netBasisQty = null;
        $netBasisQtyUnitCode = null;
        $reader->getDocumentPositionNetPrice($netPrice, $netBasisQty, $netBasisQtyUnitCode);

        // Quantité
        $billedQty = $billedQtyUnit = null;
        $chargeFreeQty = $chargeFreeQtyUnit = $packageQty = $packageQtyUnit = null;
        $reader->getDocumentPositionQuantity(
            $billedQty, $billedQtyUnit,
            $chargeFreeQty, $chargeFreeQtyUnit,
            $packageQty, $packageQtyUnit
        );

        // TVA de la ligne
        $vatRate = 20.0;
        if ($reader->firstDocumentPositionTax()) {
            $categoryCode = $typeCode = $exemptionReason = $exemptionReasonCode = null;
            $ratePercent = $calcAmount = null;
            $reader->getDocumentPositionTax(
                $categoryCode, $typeCode, $ratePercent,
                $calcAmount, $exemptionReason, $exemptionReasonCode
            );
            if ($ratePercent !== null) {
                $vatRate = (float) $ratePercent;
            }
        }

        // Total ligne
        $lineTotalAmount = null;
        $reader->getDocumentPositionLineSummationSimple($lineTotalAmount);

        return [
            'name' => $productName ?? 'Article importé',
            'description' => $productDesc,
            'seller_product_id' => $sellerProductId,
            'buyer_product_id' => $buyerProductId,
            'ean' => ($globalIdType === '0160') ? $globalId : null,
            'quantity' => $billedQty ?? 1,
            'unit_price_ht' => $netPrice ?? 0,
            'vat_rate' => $vatRate,
            'total_ht' => $lineTotalAmount ?? (($netPrice ?? 0) * ($billedQty ?? 1)),
        ];
    }

    /**
     * Import UBL (EN16931) via SimpleXML
     */
    protected function importUBL(string $xmlContent, int $companyId): array
    {
        $xml = new \SimpleXMLElement($xmlContent);

        // Enregistrer les namespaces
        $namespaces = $xml->getDocNamespaces(true);
        $cbc = $namespaces['cbc'] ?? 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
        $cac = $namespaces['cac'] ?? 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

        $xml->registerXPathNamespace('cbc', $cbc);
        $xml->registerXPathNamespace('cac', $cac);

        // Numéro de facture
        $invoiceNumber = (string) ($xml->xpath('//cbc:ID')[0] ?? '');

        // Date
        $issueDateStr = (string) ($xml->xpath('//cbc:IssueDate')[0] ?? '');
        $documentDate = $issueDateStr ? new \DateTime($issueDateStr) : null;

        // Devise
        $currency = (string) ($xml->xpath('//cbc:DocumentCurrencyCode')[0] ?? 'EUR');

        // Fournisseur (AccountingSupplierParty)
        $supplierParty = $xml->xpath('//cac:AccountingSupplierParty/cac:Party');
        $supplierData = $this->extractUBLParty($supplierParty[0] ?? null, $cbc, $cac);

        // Totaux
        $legalTotal = $xml->xpath('//cac:LegalMonetaryTotal');
        $totalHt = 0;
        $totalTtc = 0;
        $totalVat = 0;

        if (!empty($legalTotal)) {
            $lt = $legalTotal[0];
            $lt->registerXPathNamespace('cbc', $cbc);
            $totalHt = (float) ($lt->xpath('cbc:TaxExclusiveAmount')[0] ?? 0);
            $totalTtc = (float) ($lt->xpath('cbc:TaxInclusiveAmount')[0] ?? $lt->xpath('cbc:PayableAmount')[0] ?? 0);
        }

        // TVA totale
        $taxTotals = $xml->xpath('//cac:TaxTotal/cbc:TaxAmount');
        if (!empty($taxTotals)) {
            $totalVat = (float) ($taxTotals[0] ?? 0);
        }

        // Lignes
        $items = [];
        $invoiceLines = $xml->xpath('//cac:InvoiceLine');
        if (empty($invoiceLines)) {
            $invoiceLines = $xml->xpath('//cac:CreditNoteLine');
        }

        foreach ($invoiceLines as $line) {
            $line->registerXPathNamespace('cbc', $cbc);
            $line->registerXPathNamespace('cac', $cac);
            $item = $this->extractUBLLineItem($line, $cbc, $cac);
            if ($item) {
                $items[] = $item;
            }
        }

        return $this->createPurchaseFromParsedData(
            companyId: $companyId,
            invoiceNumber: $invoiceNumber,
            supplierName: $supplierData['name'] ?? 'Fournisseur inconnu',
            supplierSiret: $supplierData['siret'] ?? null,
            supplierSiren: $supplierData['siren'] ?? null,
            supplierTaxNumber: $supplierData['tax_number'] ?? null,
            supplierEmail: $supplierData['email'] ?? null,
            supplierPhone: $supplierData['phone'] ?? null,
            supplierAddress: $supplierData['address'] ?? null,
            supplierZipCode: $supplierData['zip_code'] ?? null,
            supplierCity: $supplierData['city'] ?? null,
            supplierCountry: $supplierData['country'] ?? null,
            supplierCountryCode: $supplierData['country_code'] ?? null,
            totalHt: $totalHt,
            totalVat: $totalVat,
            totalTtc: $totalTtc,
            items: $items,
            currency: $currency,
            documentDate: $documentDate,
        );
    }

    /**
     * Extraire les données d'un Party UBL (fournisseur ou client)
     */
    protected function extractUBLParty(?\SimpleXMLElement $party, string $cbc, string $cac): array
    {
        if (!$party) {
            return [];
        }

        $party->registerXPathNamespace('cbc', $cbc);
        $party->registerXPathNamespace('cac', $cac);

        $data = [];

        // Nom
        $partyName = $party->xpath('cac:PartyName/cbc:Name');
        $data['name'] = !empty($partyName) ? (string) $partyName[0] : null;

        // Nom légal
        if (!$data['name']) {
            $legalName = $party->xpath('cac:PartyLegalEntity/cbc:RegistrationName');
            $data['name'] = !empty($legalName) ? (string) $legalName[0] : null;
        }

        // SIRET (CompanyID dans PartyLegalEntity)
        $companyId = $party->xpath('cac:PartyLegalEntity/cbc:CompanyID');
        if (!empty($companyId)) {
            $id = (string) $companyId[0];
            if (strlen($id) === 14 && ctype_digit($id)) {
                $data['siret'] = $id;
                $data['siren'] = substr($id, 0, 9);
            } elseif (strlen($id) === 9 && ctype_digit($id)) {
                $data['siren'] = $id;
            }
        }

        // TVA (PartyTaxScheme/CompanyID)
        $taxId = $party->xpath('cac:PartyTaxScheme/cbc:CompanyID');
        if (!empty($taxId)) {
            $data['tax_number'] = (string) $taxId[0];
        }

        // Adresse
        $address = $party->xpath('cac:PostalAddress');
        if (!empty($address)) {
            $addr = $address[0];
            $addr->registerXPathNamespace('cbc', $cbc);

            $streetName = $addr->xpath('cbc:StreetName');
            $addStreetName = $addr->xpath('cbc:AdditionalStreetName');
            $data['address'] = !empty($streetName) ? (string) $streetName[0] : null;
            if (!empty($addStreetName)) {
                $data['address'] .= ', ' . (string) $addStreetName[0];
            }

            $postalZone = $addr->xpath('cbc:PostalZone');
            $data['zip_code'] = !empty($postalZone) ? (string) $postalZone[0] : null;

            $cityName = $addr->xpath('cbc:CityName');
            $data['city'] = !empty($cityName) ? (string) $cityName[0] : null;

            $countryNode = $addr->xpath('cac:Country/cbc:IdentificationCode');
            $data['country_code'] = !empty($countryNode) ? (string) $countryNode[0] : null;
            $data['country'] = $data['country_code']; // Will be resolved later
        }

        // Contact
        $contact = $party->xpath('cac:Contact');
        if (!empty($contact)) {
            $contact[0]->registerXPathNamespace('cbc', $cbc);
            $email = $contact[0]->xpath('cbc:ElectronicMail');
            $phone = $contact[0]->xpath('cbc:Telephone');
            $data['email'] = !empty($email) ? (string) $email[0] : null;
            $data['phone'] = !empty($phone) ? (string) $phone[0] : null;
        }

        // Endpoint (email alternatif via EndpointID)
        if (empty($data['email'])) {
            $endpoint = $party->xpath('cbc:EndpointID');
            if (!empty($endpoint)) {
                $epValue = (string) $endpoint[0];
                if (filter_var($epValue, FILTER_VALIDATE_EMAIL)) {
                    $data['email'] = $epValue;
                }
            }
        }

        return $data;
    }

    /**
     * Extraire une ligne article UBL
     */
    protected function extractUBLLineItem(\SimpleXMLElement $line, string $cbc, string $cac): ?array
    {
        $line->registerXPathNamespace('cbc', $cbc);
        $line->registerXPathNamespace('cac', $cac);

        // Quantité
        $qty = $line->xpath('cbc:InvoicedQuantity');
        if (empty($qty)) {
            $qty = $line->xpath('cbc:CreditedQuantity');
        }
        $quantity = !empty($qty) ? (float) $qty[0] : 1;

        // Total ligne HT
        $lineAmount = $line->xpath('cbc:LineExtensionAmount');
        $totalHt = !empty($lineAmount) ? (float) $lineAmount[0] : 0;

        // Article
        $itemNode = $line->xpath('cac:Item');
        $productName = 'Article importé';
        $productDesc = null;
        $sellerProductId = null;
        $ean = null;
        $vatRate = 20.0;

        if (!empty($itemNode)) {
            $item = $itemNode[0];
            $item->registerXPathNamespace('cbc', $cbc);
            $item->registerXPathNamespace('cac', $cac);

            $name = $item->xpath('cbc:Name');
            if (!empty($name)) {
                $productName = (string) $name[0];
            }

            $desc = $item->xpath('cbc:Description');
            if (!empty($desc)) {
                $productDesc = (string) $desc[0];
            }

            // Référence fournisseur
            $sellerId = $item->xpath('cac:SellersItemIdentification/cbc:ID');
            if (!empty($sellerId)) {
                $sellerProductId = (string) $sellerId[0];
            }

            // EAN / GTIN
            $stdId = $item->xpath('cac:StandardItemIdentification/cbc:ID');
            if (!empty($stdId)) {
                $ean = (string) $stdId[0];
            }

            // TVA
            $taxCategory = $item->xpath('cac:ClassifiedTaxCategory/cbc:Percent');
            if (!empty($taxCategory)) {
                $vatRate = (float) $taxCategory[0];
            }
        }

        // Prix unitaire
        $priceNode = $line->xpath('cac:Price/cbc:PriceAmount');
        $unitPrice = !empty($priceNode) ? (float) $priceNode[0] : ($quantity > 0 ? $totalHt / $quantity : 0);

        return [
            'name' => $productName,
            'description' => $productDesc,
            'seller_product_id' => $sellerProductId,
            'buyer_product_id' => null,
            'ean' => $ean,
            'quantity' => $quantity,
            'unit_price_ht' => $unitPrice,
            'vat_rate' => $vatRate,
            'total_ht' => $totalHt,
        ];
    }

    /**
     * Créer l'achat en base à partir des données parsées
     */
    protected function createPurchaseFromParsedData(
        int $companyId,
        ?string $invoiceNumber,
        string $supplierName,
        ?string $supplierSiret,
        ?string $supplierSiren,
        ?string $supplierTaxNumber,
        ?string $supplierEmail,
        ?string $supplierPhone,
        ?string $supplierAddress,
        ?string $supplierZipCode,
        ?string $supplierCity,
        ?string $supplierCountry,
        ?string $supplierCountryCode,
        float $totalHt,
        float $totalVat,
        float $totalTtc,
        array $items,
        string $currency,
        ?\DateTime $documentDate,
    ): array {
        return DB::transaction(function () use (
            $companyId, $invoiceNumber, $supplierName,
            $supplierSiret, $supplierSiren, $supplierTaxNumber,
            $supplierEmail, $supplierPhone, $supplierAddress,
            $supplierZipCode, $supplierCity, $supplierCountry, $supplierCountryCode,
            $totalHt, $totalVat, $totalTtc, $items, $currency, $documentDate
        ) {
            // 1. Trouver ou créer le fournisseur
            $supplier = $this->findOrCreateSupplier(
                $companyId, $supplierName, $supplierSiret, $supplierSiren,
                $supplierTaxNumber, $supplierEmail, $supplierPhone,
                $supplierAddress, $supplierZipCode, $supplierCity,
                $supplierCountry, $supplierCountryCode
            );

            // 2. Vérifier doublon (même fournisseur + même numéro de facture)
            if ($invoiceNumber) {
                $existingPurchase = Purchase::where('company_id', $companyId)
                    ->where('supplier_id', $supplier->id)
                    ->whereRaw('LOWER(invoice_number) = ?', [strtolower($invoiceNumber)])
                    ->first();

                if ($existingPurchase) {
                    $this->result['warnings'][] = "Une facture avec ce numéro ({$invoiceNumber}) existe déjà pour ce fournisseur.";
                    return $this->error(
                        "Doublon détecté : la facture {$invoiceNumber} de {$supplier->name} existe déjà (Achat #{$existingPurchase->id})."
                    );
                }
            }

            // 3. Entrepôt par défaut
            $warehouse = Warehouse::getDefault($companyId);

            // 4. Créer l'achat (en pending, sans déclencher la réception stock)
            $purchase = Purchase::create([
                'company_id' => $companyId,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse?->id,
                'status' => 'pending',
                'payment_method' => 'transfer',
                'total_ht' => round($totalHt, 2),
                'total_vat' => round($totalVat, 2),
                'total' => round($totalTtc, 2),
                'discount_percent' => 0,
                'tax_percent' => 0,
                'notes' => "Importé depuis facture XML" . ($invoiceNumber ? " (réf. fournisseur: {$invoiceNumber})" : ''),
            ]);

            // 5. Créer les lignes articles
            $createdItems = 0;
            foreach ($items as $itemData) {
                $product = $this->findOrCreateProduct($companyId, $itemData);

                $unitPriceHt = (float) ($itemData['unit_price_ht'] ?? 0);
                $quantity = (float) ($itemData['quantity'] ?? 1);
                $vatRate = (float) ($itemData['vat_rate'] ?? 20);
                $totalPriceHt = round($unitPriceHt * $quantity, 2);
                $vatAmount = round($totalPriceHt * ($vatRate / 100), 2);
                $totalPrice = round($totalPriceHt + $vatAmount, 2);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPriceHt, // unit_price = HT pour achats
                    'vat_rate' => $vatRate,
                    'unit_price_ht' => $unitPriceHt,
                    'vat_amount' => $vatAmount,
                    'total_price_ht' => $totalPriceHt,
                    'total_price' => $totalPrice,
                ]);

                $createdItems++;
            }

            // 6. Recalculer les totaux réels
            $purchase->recalculateTotals();
            $purchase->refresh();

            $this->result = [
                'success' => true,
                'message' => "Facture importée avec succès : {$purchase->invoice_number}",
                'purchase_id' => $purchase->id,
                'invoice_number' => $purchase->invoice_number,
                'supplier' => $supplier->name,
                'supplier_id' => $supplier->id,
                'items_count' => $createdItems,
                'total_ht' => $purchase->total_ht,
                'total_vat' => $purchase->total_vat,
                'total_ttc' => $purchase->total,
                'warnings' => $this->result['warnings'],
                'is_new_supplier' => $supplier->wasRecentlyCreated,
            ];

            return $this->result;
        });
    }

    /**
     * Trouver ou créer le fournisseur basé sur SIRET/SIREN, TVA intra, ou nom
     */
    protected function findOrCreateSupplier(
        int $companyId,
        string $name,
        ?string $siret,
        ?string $siren,
        ?string $taxNumber,
        ?string $email,
        ?string $phone,
        ?string $address,
        ?string $zipCode,
        ?string $city,
        ?string $country,
        ?string $countryCode,
    ): Supplier {
        $hasExtendedFields = $this->checkSupplierExtendedFields();

        // Recherche par SIRET (identifiant le plus précis) — uniquement si la colonne existe
        if ($hasExtendedFields && $siret) {
            $supplier = Supplier::where('company_id', $companyId)
                ->where('siret', $siret)
                ->first();
            if ($supplier) {
                $this->updateSupplierIfNeeded($supplier, $taxNumber, $email, $phone, $address, $zipCode, $city, $country, $countryCode);
                return $supplier;
            }
        }

        // Recherche par N° TVA intracommunautaire — uniquement si la colonne existe
        if ($hasExtendedFields && $taxNumber) {
            $supplier = Supplier::where('company_id', $companyId)
                ->where('tax_number', $taxNumber)
                ->first();
            if ($supplier) {
                $this->updateSupplierIfNeeded($supplier, $taxNumber, $email, $phone, $address, $zipCode, $city, $country, $countryCode);
                return $supplier;
            }
        }

        // Recherche par nom exact
        $supplier = Supplier::where('company_id', $companyId)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();
        if ($supplier) {
            $this->updateSupplierIfNeeded($supplier, $taxNumber, $email, $phone, $address, $zipCode, $city, $country, $countryCode);
            return $supplier;
        }

        // Créer un nouveau fournisseur
        $this->result['warnings'][] = "Nouveau fournisseur créé : {$name}";

        $supplierData = [
            'company_id' => $companyId,
            'name' => $name,
            'email' => $email ?? 'import-' . uniqid() . '@placeholder.local',
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'country' => $country,
            'notes' => 'Créé automatiquement lors de l\'import de facture XML',
        ];

        // Ajouter les champs étendus uniquement si la migration a été appliquée
        if ($hasExtendedFields) {
            $supplierData['siret'] = $siret;
            $supplierData['siren'] = $siren;
            $supplierData['tax_number'] = $taxNumber;
            $supplierData['zip_code'] = $zipCode;
            $supplierData['country_code'] = $countryCode ?? 'FR';
        } else {
            $this->result['warnings'][] = 'Migration des champs SIRET/TVA manquante — exécutez php artisan migrate.';
        }

        return Supplier::create($supplierData);
    }

    /**
     * Mettre à jour les champs vides du fournisseur existant
     */
    protected function updateSupplierIfNeeded(
        Supplier $supplier,
        ?string $taxNumber,
        ?string $email,
        ?string $phone,
        ?string $address,
        ?string $zipCode,
        ?string $city,
        ?string $country,
        ?string $countryCode,
    ): void {
        $hasExtendedFields = $this->checkSupplierExtendedFields();

        $updates = [];
        if ($email && !$supplier->email) $updates['email'] = $email;
        if ($phone && !$supplier->phone) $updates['phone'] = $phone;
        if ($address && !$supplier->address) $updates['address'] = $address;
        if ($city && !$supplier->city) $updates['city'] = $city;
        if ($country && !$supplier->country) $updates['country'] = $country;

        // Champs étendus uniquement si la migration a été appliquée
        if ($hasExtendedFields) {
            if ($taxNumber && !$supplier->tax_number) $updates['tax_number'] = $taxNumber;
            if ($zipCode && !$supplier->zip_code) $updates['zip_code'] = $zipCode;
            if ($countryCode && !$supplier->country_code) $updates['country_code'] = $countryCode;
        }

        if (!empty($updates)) {
            $supplier->update($updates);
            $this->result['warnings'][] = "Fournisseur {$supplier->name} mis à jour avec les données de la facture.";
        }
    }

    /**
     * Trouver ou créer un produit depuis les données de la ligne
     */
    protected function findOrCreateProduct(int $companyId, array $itemData): Product
    {
        // Recherche par code interne (EAN/GTIN ou réf. fournisseur)
        if (!empty($itemData['ean'])) {
            $product = Product::where('company_id', $companyId)
                ->where('code', $itemData['ean'])
                ->first();
            if ($product) return $product;
        }

        // Recherche par code = référence fournisseur
        if (!empty($itemData['seller_product_id'])) {
            $product = Product::where('company_id', $companyId)
                ->where('code', $itemData['seller_product_id'])
                ->first();
            if ($product) return $product;
        }

        // Recherche par nom exact
        $product = Product::where('company_id', $companyId)
            ->whereRaw('LOWER(name) = ?', [strtolower($itemData['name'])])
            ->first();
        if ($product) return $product;

        // Construire la description avec les références importées
        $description = $itemData['description'] ?? null;
        $refs = [];
        if (!empty($itemData['ean'])) $refs[] = 'EAN: ' . $itemData['ean'];
        if (!empty($itemData['seller_product_id'])) $refs[] = 'Réf. fournisseur: ' . $itemData['seller_product_id'];
        if (!empty($refs)) {
            $description = ($description ? $description . "\n" : '') . implode(' | ', $refs);
        }

        // Créer le produit (code auto-généré par le boot du modèle)
        $this->result['warnings'][] = "Nouveau produit créé : {$itemData['name']}";
        return Product::create([
            'company_id' => $companyId,
            'name' => $itemData['name'],
            'description' => $description,
            'purchase_price' => $itemData['unit_price_ht'] ?? 0,
            'price' => $itemData['unit_price_ht'] ?? 0,
            'vat_rate_purchase' => $itemData['vat_rate'] ?? 20,
            'vat_rate_sale' => $itemData['vat_rate'] ?? 20,
        ]);
    }

    protected function error(string $message): array
    {
        $this->result['success'] = false;
        $this->result['message'] = $message;
        return $this->result;
    }

    /**
     * Valider un fichier XML avant import (mode aperçu)
     */
    public function preview(string $xmlContent): array
    {
        $format = $this->detectFormat($xmlContent);

        if ($format === 'unknown') {
            return ['valid' => false, 'error' => 'Format XML non reconnu.'];
        }

        try {
            if ($format === 'CII') {
                return $this->previewCII($xmlContent);
            } else {
                return $this->previewUBL($xmlContent);
            }
        } catch (\Throwable $e) {
            Log::error('Erreur preview facture XML', [
                'format' => $format,
                'class' => get_class($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return ['valid' => false, 'error' => 'Erreur parsing : ' . $e->getMessage()];
        }
    }

    protected function previewCII(string $xmlContent): array
    {
        $reader = ZugferdDocumentReader::readAndGuessFromContent($xmlContent);

        $documentNo = $documentTypeCode = $invoiceCurrency = $documentName = $documentLanguage = null;
        $documentDate = $effectiveDate = null;
        $reader->getDocumentInformation($documentNo, $documentTypeCode, $documentDate, $invoiceCurrency, $taxCurrency, $documentName, $documentLanguage, $effectiveDate);

        $sellerName = $sellerDescription = null;
        $sellerIds = null;
        $reader->getDocumentSeller($sellerName, $sellerIds, $sellerDescription);

        $grandTotal = $duePayable = $lineTotal = $chargeTotal = $allowanceTotal = $taxBasisTotal = $taxTotal = $roundingAmount = $prepaidAmount = null;
        $reader->getDocumentSummation($grandTotal, $duePayable, $lineTotal, $chargeTotal, $allowanceTotal, $taxBasisTotal, $taxTotal, $roundingAmount, $prepaidAmount);

        $itemCount = 0;
        if ($reader->firstDocumentPosition()) {
            do { $itemCount++; } while ($reader->nextDocumentPosition());
        }

        return [
            'valid' => true,
            'format' => 'CII (Factur-X / ZUGFeRD)',
            'invoice_number' => $documentNo,
            'date' => $documentDate?->format('d/m/Y'),
            'supplier' => $sellerName,
            'currency' => $invoiceCurrency ?? 'EUR',
            'total_ht' => $taxBasisTotal ?? $lineTotal,
            'total_vat' => $taxTotal,
            'total_ttc' => $grandTotal,
            'items_count' => $itemCount,
        ];
    }

    protected function previewUBL(string $xmlContent): array
    {
        $xml = new \SimpleXMLElement($xmlContent);
        $namespaces = $xml->getDocNamespaces(true);
        $cbc = $namespaces['cbc'] ?? 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
        $cac = $namespaces['cac'] ?? 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
        $xml->registerXPathNamespace('cbc', $cbc);
        $xml->registerXPathNamespace('cac', $cac);

        $invoiceNumber = (string) ($xml->xpath('//cbc:ID')[0] ?? '');
        $issueDate = (string) ($xml->xpath('//cbc:IssueDate')[0] ?? '');
        $currency = (string) ($xml->xpath('//cbc:DocumentCurrencyCode')[0] ?? 'EUR');

        $supplierParty = $xml->xpath('//cac:AccountingSupplierParty/cac:Party');
        $supplierData = $this->extractUBLParty($supplierParty[0] ?? null, $cbc, $cac);

        $legalTotal = $xml->xpath('//cac:LegalMonetaryTotal');
        $totalHt = 0;
        $totalTtc = 0;
        if (!empty($legalTotal)) {
            $lt = $legalTotal[0];
            $lt->registerXPathNamespace('cbc', $cbc);
            $totalHt = (float) ($lt->xpath('cbc:TaxExclusiveAmount')[0] ?? 0);
            $totalTtc = (float) ($lt->xpath('cbc:TaxInclusiveAmount')[0] ?? $lt->xpath('cbc:PayableAmount')[0] ?? 0);
        }

        $taxTotals = $xml->xpath('//cac:TaxTotal/cbc:TaxAmount');
        $totalVat = !empty($taxTotals) ? (float) $taxTotals[0] : 0;

        $invoiceLines = $xml->xpath('//cac:InvoiceLine');
        if (empty($invoiceLines)) {
            $invoiceLines = $xml->xpath('//cac:CreditNoteLine');
        }

        return [
            'valid' => true,
            'format' => 'UBL (EN16931)',
            'invoice_number' => $invoiceNumber,
            'date' => $issueDate ? (new \DateTime($issueDate))->format('d/m/Y') : null,
            'supplier' => $supplierData['name'] ?? 'Inconnu',
            'currency' => $currency,
            'total_ht' => $totalHt,
            'total_vat' => $totalVat,
            'total_ttc' => $totalTtc,
            'items_count' => count($invoiceLines),
        ];
    }
}
