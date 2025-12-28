<?php

namespace App\Services\Integration;

use App\Models\Sale;
use Illuminate\Support\Facades\Storage;

class FacturXGenerator
{
    /**
     * Génère le XML CII (CrossIndustryInvoice) pour Chorus Pro
     * Format validé le 28/12/2025
     */
    public function generateXml(Sale $sale): string
    {
        $company = $sale->company;
        $customer = $sale->customer;
        
        // Format dates
        $issueDate = $sale->created_at->format('Ymd');
        $dueDate = $sale->created_at->addDays(30)->format('Ymd');

        // Numéro de facture (max 20 caractères pour Chorus Pro)
        $invoiceNumber = substr($sale->invoice_number, 0, 20);

        // Calculate tax amounts - Le total inclut la TVA
        $grandTotal = $sale->total;
        $taxPercent = $sale->tax_percent ?? 20;
        $taxBasisTotal = round($grandTotal / (1 + $taxPercent / 100), 2);
        $taxAmount = round($grandTotal - $taxBasisTotal, 2);

        // Code pays ISO 3166-1 alpha-2 (2 caractères)
        $sellerCountry = substr($company->country_code ?? 'FR', 0, 2);
        $buyerCountry = substr($customer->country_code ?? 'FR', 0, 2);

        // S'assurer que le SIRET est bien formaté (14 chiffres)
        $sellerSiret = preg_replace('/[^0-9]/', '', $company->siret ?? '');
        $buyerSiret = preg_replace('/[^0-9]/', '', $customer->siret ?? '');

        // Code type de paiement (30 = virement, 42 = prélèvement, 10 = espèces, 48 = carte)
        $paymentTypeCode = '30'; // Virement bancaire par défaut

        // Catégorie TVA selon UNTDID 5305 pour Chorus Pro:
        // S = Standard rate (taux normal avec TVA)
        // E = Exempt from tax (exonéré de TVA - le plus courant pour 0%)
        // O = Services outside scope of tax (hors champ TVA)
        // AE = VAT Reverse Charge (autoliquidation)
        // Pour une facture B2G française avec TVA à 0%, utiliser 'E' (Exempt)
        if ($taxPercent > 0) {
            $taxCategoryCode = 'S'; // Standard
        } else {
            $taxCategoryCode = 'E'; // Exempt (pour TVA 0%)
        }

        // Devise
        $currency = $company->currency ?? 'EUR';

        // Adresse seller (avec valeurs par défaut)
        $sellerAddress = $company->address ?? 'Adresse';
        $sellerPostcode = $company->zip_code ?? '75001';
        $sellerCity = $company->city ?? 'Paris';

        // Adresse buyer (avec valeurs par défaut)
        $buyerAddress = $customer->address ?? 'Adresse';
        $buyerPostcode = $customer->zip_code ?? '75001';
        $buyerCity = $customer->city ?? 'Paris';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rsm:CrossIndustryInvoice xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100" xmlns:ram="urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100" xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100">' . "\n";
        
        // Document Context
        $xml .= '    <rsm:ExchangedDocumentContext>' . "\n";
        $xml .= '        <ram:GuidelineSpecifiedDocumentContextParameter>' . "\n";
        $xml .= '            <ram:ID>urn:cen.eu:en16931:2017#compliant#urn:factur-x.eu:1p0:basic</ram:ID>' . "\n";
        $xml .= '        </ram:GuidelineSpecifiedDocumentContextParameter>' . "\n";
        $xml .= '    </rsm:ExchangedDocumentContext>' . "\n";
        
        // Document Header
        $xml .= '    <rsm:ExchangedDocument>' . "\n";
        $xml .= '        <ram:ID>' . htmlspecialchars($invoiceNumber) . '</ram:ID>' . "\n";
        $xml .= '        <ram:TypeCode>380</ram:TypeCode>' . "\n";
        $xml .= '        <ram:IssueDateTime>' . "\n";
        $xml .= '            <udt:DateTimeString format="102">' . $issueDate . '</udt:DateTimeString>' . "\n";
        $xml .= '        </ram:IssueDateTime>' . "\n";
        $xml .= '    </rsm:ExchangedDocument>' . "\n";
        
        // Supply Chain Trade Transaction
        $xml .= '    <rsm:SupplyChainTradeTransaction>' . "\n";
        
        // Line Items
        $lineNumber = 1;
        foreach ($sale->items as $item) {
            $lineTotal = round($item->quantity * $item->unit_price, 2);
            $productName = htmlspecialchars($item->product->name ?? 'Produit');
            
            $xml .= '        <ram:IncludedSupplyChainTradeLineItem>' . "\n";
            $xml .= '            <ram:AssociatedDocumentLineDocument>' . "\n";
            $xml .= '                <ram:LineID>' . $lineNumber . '</ram:LineID>' . "\n";
            $xml .= '            </ram:AssociatedDocumentLineDocument>' . "\n";
            $xml .= '            <ram:SpecifiedTradeProduct>' . "\n";
            $xml .= '                <ram:Name>' . $productName . '</ram:Name>' . "\n";
            $xml .= '            </ram:SpecifiedTradeProduct>' . "\n";
            $xml .= '            <ram:SpecifiedLineTradeAgreement>' . "\n";
            $xml .= '                <ram:NetPriceProductTradePrice>' . "\n";
            $xml .= '                    <ram:ChargeAmount>' . number_format($item->unit_price, 2, '.', '') . '</ram:ChargeAmount>' . "\n";
            $xml .= '                </ram:NetPriceProductTradePrice>' . "\n";
            $xml .= '            </ram:SpecifiedLineTradeAgreement>' . "\n";
            $xml .= '            <ram:SpecifiedLineTradeDelivery>' . "\n";
            $xml .= '                <ram:BilledQuantity unitCode="C62">' . $item->quantity . '</ram:BilledQuantity>' . "\n";
            $xml .= '            </ram:SpecifiedLineTradeDelivery>' . "\n";
            $xml .= '            <ram:SpecifiedLineTradeSettlement>' . "\n";
            $xml .= '                <ram:ApplicableTradeTax>' . "\n";
            $xml .= '                    <ram:TypeCode>VAT</ram:TypeCode>' . "\n";
            $xml .= '                    <ram:CategoryCode>' . $taxCategoryCode . '</ram:CategoryCode>' . "\n";
            $xml .= '                    <ram:RateApplicablePercent>' . number_format($taxPercent, 2, '.', '') . '</ram:RateApplicablePercent>' . "\n";
            $xml .= '                </ram:ApplicableTradeTax>' . "\n";
            $xml .= '                <ram:SpecifiedTradeSettlementLineMonetarySummation>' . "\n";
            $xml .= '                    <ram:LineTotalAmount>' . number_format($lineTotal, 2, '.', '') . '</ram:LineTotalAmount>' . "\n";
            $xml .= '                </ram:SpecifiedTradeSettlementLineMonetarySummation>' . "\n";
            $xml .= '            </ram:SpecifiedLineTradeSettlement>' . "\n";
            $xml .= '        </ram:IncludedSupplyChainTradeLineItem>' . "\n";
            
            $lineNumber++;
        }
        
        // Header Trade Agreement (Seller & Buyer)
        $xml .= '        <ram:ApplicableHeaderTradeAgreement>' . "\n";
        
        // Seller (Fournisseur) - schemeID="SIRET" obligatoire pour Chorus Pro
        $xml .= '            <ram:SellerTradeParty>' . "\n";
        $xml .= '                <ram:Name>' . htmlspecialchars($company->name) . '</ram:Name>' . "\n";
        $xml .= '                <ram:SpecifiedLegalOrganization>' . "\n";
        $xml .= '                    <ram:ID schemeID="SIRET">' . $sellerSiret . '</ram:ID>' . "\n";
        $xml .= '                </ram:SpecifiedLegalOrganization>' . "\n";
        $xml .= '                <ram:PostalTradeAddress>' . "\n";
        $xml .= '                    <ram:PostcodeCode>' . htmlspecialchars($sellerPostcode) . '</ram:PostcodeCode>' . "\n";
        $xml .= '                    <ram:LineOne>' . htmlspecialchars($sellerAddress) . '</ram:LineOne>' . "\n";
        $xml .= '                    <ram:CityName>' . htmlspecialchars($sellerCity) . '</ram:CityName>' . "\n";
        $xml .= '                    <ram:CountryID>' . $sellerCountry . '</ram:CountryID>' . "\n";
        $xml .= '                </ram:PostalTradeAddress>' . "\n";
        $xml .= '            </ram:SellerTradeParty>' . "\n";
        
        // Buyer (Débiteur/Client) - schemeID="SIRET" obligatoire pour Chorus Pro
        $xml .= '            <ram:BuyerTradeParty>' . "\n";
        $xml .= '                <ram:Name>' . htmlspecialchars($customer->name) . '</ram:Name>' . "\n";
        $xml .= '                <ram:SpecifiedLegalOrganization>' . "\n";
        $xml .= '                    <ram:ID schemeID="SIRET">' . $buyerSiret . '</ram:ID>' . "\n";
        $xml .= '                </ram:SpecifiedLegalOrganization>' . "\n";
        $xml .= '                <ram:PostalTradeAddress>' . "\n";
        $xml .= '                    <ram:PostcodeCode>' . htmlspecialchars($buyerPostcode) . '</ram:PostcodeCode>' . "\n";
        $xml .= '                    <ram:LineOne>' . htmlspecialchars($buyerAddress) . '</ram:LineOne>' . "\n";
        $xml .= '                    <ram:CityName>' . htmlspecialchars($buyerCity) . '</ram:CityName>' . "\n";
        $xml .= '                    <ram:CountryID>' . $buyerCountry . '</ram:CountryID>' . "\n";
        $xml .= '                </ram:PostalTradeAddress>' . "\n";
        $xml .= '            </ram:BuyerTradeParty>' . "\n";
        
        $xml .= '        </ram:ApplicableHeaderTradeAgreement>' . "\n";
        
        // Delivery
        $xml .= '        <ram:ApplicableHeaderTradeDelivery>' . "\n";
        $xml .= '            <ram:ActualDeliverySupplyChainEvent>' . "\n";
        $xml .= '                <ram:OccurrenceDateTime>' . "\n";
        $xml .= '                    <udt:DateTimeString format="102">' . $issueDate . '</udt:DateTimeString>' . "\n";
        $xml .= '                </ram:OccurrenceDateTime>' . "\n";
        $xml .= '            </ram:ActualDeliverySupplyChainEvent>' . "\n";
        $xml .= '        </ram:ApplicableHeaderTradeDelivery>' . "\n";
        
        // Settlement (Payment Terms, Tax, Totals)
        $xml .= '        <ram:ApplicableHeaderTradeSettlement>' . "\n";
        $xml .= '            <ram:InvoiceCurrencyCode>' . $currency . '</ram:InvoiceCurrencyCode>' . "\n";
        
        // OBLIGATOIRE: Moyen de paiement
        $xml .= '            <ram:SpecifiedTradeSettlementPaymentMeans>' . "\n";
        $xml .= '                <ram:TypeCode>' . $paymentTypeCode . '</ram:TypeCode>' . "\n";
        $xml .= '            </ram:SpecifiedTradeSettlementPaymentMeans>' . "\n";
        
        // OBLIGATOIRE: Détail de la TVA au niveau header
        $xml .= '            <ram:ApplicableTradeTax>' . "\n";
        $xml .= '                <ram:CalculatedAmount>' . number_format($taxAmount, 2, '.', '') . '</ram:CalculatedAmount>' . "\n";
        $xml .= '                <ram:TypeCode>VAT</ram:TypeCode>' . "\n";
        $xml .= '                <ram:BasisAmount>' . number_format($taxBasisTotal, 2, '.', '') . '</ram:BasisAmount>' . "\n";
        $xml .= '                <ram:CategoryCode>' . $taxCategoryCode . '</ram:CategoryCode>' . "\n";
        $xml .= '                <ram:RateApplicablePercent>' . number_format($taxPercent, 2, '.', '') . '</ram:RateApplicablePercent>' . "\n";
        $xml .= '            </ram:ApplicableTradeTax>' . "\n";
        
        // Date d'échéance de paiement
        $xml .= '            <ram:SpecifiedTradePaymentTerms>' . "\n";
        $xml .= '                <ram:DueDateDateTime>' . "\n";
        $xml .= '                    <udt:DateTimeString format="102">' . $dueDate . '</udt:DateTimeString>' . "\n";
        $xml .= '                </ram:DueDateDateTime>' . "\n";
        $xml .= '            </ram:SpecifiedTradePaymentTerms>' . "\n";
        
        // Totaux
        $xml .= '            <ram:SpecifiedTradeSettlementHeaderMonetarySummation>' . "\n";
        $xml .= '                <ram:LineTotalAmount>' . number_format($taxBasisTotal, 2, '.', '') . '</ram:LineTotalAmount>' . "\n";
        $xml .= '                <ram:TaxBasisTotalAmount>' . number_format($taxBasisTotal, 2, '.', '') . '</ram:TaxBasisTotalAmount>' . "\n";
        $xml .= '                <ram:TaxTotalAmount currencyID="' . $currency . '">' . number_format($taxAmount, 2, '.', '') . '</ram:TaxTotalAmount>' . "\n";
        $xml .= '                <ram:GrandTotalAmount>' . number_format($grandTotal, 2, '.', '') . '</ram:GrandTotalAmount>' . "\n";
        $xml .= '                <ram:DuePayableAmount>' . number_format($grandTotal, 2, '.', '') . '</ram:DuePayableAmount>' . "\n";
        $xml .= '            </ram:SpecifiedTradeSettlementHeaderMonetarySummation>' . "\n";
        
        $xml .= '        </ram:ApplicableHeaderTradeSettlement>' . "\n";
        $xml .= '    </rsm:SupplyChainTradeTransaction>' . "\n";
        $xml .= '</rsm:CrossIndustryInvoice>';

        return $xml;
    }

    public function generatePdfWithXml(Sale $sale): string
    {
        // Placeholder for PDF generation logic
        // In a real implementation, we would generate the PDF using a library like DomPDF or Snappy
        // and then embed the XML using FPDI or similar.
        // For now, we'll just return the XML path or a dummy path.
        
        $xml = $this->generateXml($sale);
        $fileName = 'factur-x-' . $sale->invoice_number . '.xml';
        Storage::disk('local')->put('invoices/' . $fileName, $xml);
        
        return 'invoices/' . $fileName;
    }
}