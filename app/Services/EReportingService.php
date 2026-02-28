<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\CompanySetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service E-Reporting — Déclaration des opérations B2C et internationales
 * 
 * Conformité e-invoicing 2026 (Art. 290 CGI) :
 * - Les ventes B2C (sans SIRET client) ne transitent pas par le PPF
 * - Les opérations internationales (export, intra-UE) doivent être déclarées en E-Reporting
 * - La déclaration se fait par période (mensuelle ou trimestrielle)
 */
class EReportingService
{
    /**
     * Catégories E-Reporting selon la norme DGFIP
     */
    const CATEGORY_B2C_FRANCE = 'B2C_FR';         // Ventes B2C domestiques (France)
    const CATEGORY_B2B_INTERNATIONAL = 'B2B_INT';  // Ventes B2B international (hors France)
    const CATEGORY_B2C_INTERNATIONAL = 'B2C_INT';  // Ventes B2C international
    const CATEGORY_B2B_INTRA_EU = 'B2B_UE';       // Ventes B2B intra-UE
    const CATEGORY_EXPORT = 'EXPORT';              // Exportations hors UE

    /**
     * Pays de l'UE (codes ISO 3166-1 alpha-2)
     */
    const EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
        'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
    ];

    /**
     * Générer le rapport E-Reporting pour une période donnée
     */
    public function generateReport(int $companyId, string $dateFrom, string $dateTo): array
    {
        // Récupérer toutes les ventes completed/paid de la période
        $sales = Sale::where('company_id', $companyId)
            ->whereIn('status', ['completed', 'paid'])
            ->where('type', '!=', 'quote') // Exclure les devis
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->with(['customer', 'items'])
            ->get();

        // Classer les ventes
        $report = [
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'company_id' => $companyId,
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'total_sales' => $sales->count(),
                'b2b_domestic' => 0,     // B2B France (pas en E-Reporting — géré par PPF/Chorus)
                'b2c_france' => 0,       // B2C France → E-Reporting
                'b2b_intra_eu' => 0,     // B2B intra-UE → E-Reporting
                'b2c_international' => 0, // B2C international → E-Reporting
                'export' => 0,           // Export hors UE → E-Reporting
                'e_reporting_count' => 0,
            ],
            'totals' => [
                'b2c_france' => ['ht' => 0, 'vat' => 0, 'ttc' => 0],
                'b2b_intra_eu' => ['ht' => 0, 'vat' => 0, 'ttc' => 0],
                'b2c_international' => ['ht' => 0, 'vat' => 0, 'ttc' => 0],
                'export' => ['ht' => 0, 'vat' => 0, 'ttc' => 0],
            ],
            'vat_breakdown' => [],
            'lines' => [],
        ];

        foreach ($sales as $sale) {
            $category = $this->classifySale($sale);
            $line = $this->buildReportLine($sale, $category);

            // Comptage
            if ($category === 'B2B_FR') {
                $report['summary']['b2b_domestic']++;
                // Les B2B domestiques ne sont PAS en E-Reporting (géré par Chorus Pro)
                continue;
            }

            $report['summary']['e_reporting_count']++;
            $report['lines'][] = $line;

            // Totaux par catégorie
            $categoryKey = match ($category) {
                self::CATEGORY_B2C_FRANCE => 'b2c_france',
                self::CATEGORY_B2B_INTRA_EU => 'b2b_intra_eu',
                self::CATEGORY_B2C_INTERNATIONAL, self::CATEGORY_B2B_INTERNATIONAL => 'b2c_international',
                self::CATEGORY_EXPORT => 'export',
                default => 'b2c_france',
            };

            $report['summary'][$categoryKey] = ($report['summary'][$categoryKey] ?? 0) + 1;
            $report['totals'][$categoryKey]['ht'] += round($sale->total_ht ?? 0, 2);
            $report['totals'][$categoryKey]['vat'] += round($sale->total_vat ?? 0, 2);
            $report['totals'][$categoryKey]['ttc'] += round($sale->total ?? 0, 2);

            // Ventilation TVA
            foreach ($sale->getVatBreakdown() as $vat) {
                $rateKey = (string) $vat['rate'];
                if (!isset($report['vat_breakdown'][$rateKey])) {
                    $report['vat_breakdown'][$rateKey] = [
                        'rate' => $vat['rate'],
                        'base' => 0,
                        'amount' => 0,
                    ];
                }
                $report['vat_breakdown'][$rateKey]['base'] += $vat['base'];
                $report['vat_breakdown'][$rateKey]['amount'] += $vat['amount'];
            }
        }

        $report['vat_breakdown'] = array_values($report['vat_breakdown']);

        return $report;
    }

    /**
     * Classifier une vente selon les catégories E-Reporting
     */
    public function classifySale(Sale $sale): string
    {
        $customer = $sale->customer;

        // Pas de client → B2C France (vente comptoir, POS)
        if (!$customer) {
            return self::CATEGORY_B2C_FRANCE;
        }

        $countryCode = $customer->country_code ?? 'FR';
        $hasSiret = !empty($customer->siret) || !empty($customer->registration_number);

        // Client français avec SIRET → B2B domestique (PPF, pas E-Reporting)
        if ($countryCode === 'FR' && $hasSiret) {
            return 'B2B_FR';
        }

        // Client français sans SIRET → B2C France
        if ($countryCode === 'FR' && !$hasSiret) {
            return self::CATEGORY_B2C_FRANCE;
        }

        // Client intra-UE (hors France)
        if (in_array($countryCode, self::EU_COUNTRIES) && $countryCode !== 'FR') {
            return $hasSiret
                ? self::CATEGORY_B2B_INTRA_EU
                : self::CATEGORY_B2C_INTERNATIONAL;
        }

        // Client hors UE
        if (!in_array($countryCode, self::EU_COUNTRIES)) {
            return $hasSiret
                ? self::CATEGORY_EXPORT
                : self::CATEGORY_B2C_INTERNATIONAL;
        }

        return self::CATEGORY_B2C_FRANCE;
    }

    /**
     * Construire une ligne de rapport
     */
    protected function buildReportLine(Sale $sale, string $category): array
    {
        $customer = $sale->customer;

        return [
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'date' => $sale->created_at->format('Y-m-d'),
            'type' => $sale->type === 'credit_note' ? 'AVOIR' : 'FACTURE',
            'category' => $category,
            'category_label' => $this->getCategoryLabel($category),
            'customer_name' => $customer?->name ?? 'Client anonyme',
            'customer_country' => $customer?->country_code ?? 'FR',
            'customer_siret' => $customer?->siret ?? null,
            'customer_tax_number' => $customer?->tax_number ?? null,
            'total_ht' => round($sale->total_ht ?? 0, 2),
            'total_vat' => round($sale->total_vat ?? 0, 2),
            'total_ttc' => round($sale->total ?? 0, 2),
            'payment_method' => $sale->payment_method,
            'vat_breakdown' => $sale->getVatBreakdown(),
        ];
    }

    /**
     * Libellé de la catégorie
     */
    public function getCategoryLabel(string $category): string
    {
        return match ($category) {
            self::CATEGORY_B2C_FRANCE => 'B2C France',
            self::CATEGORY_B2B_INTERNATIONAL => 'B2B International',
            self::CATEGORY_B2C_INTERNATIONAL => 'B2C International',
            self::CATEGORY_B2B_INTRA_EU => 'B2B Intra-UE',
            self::CATEGORY_EXPORT => 'Export hors UE',
            'B2B_FR' => 'B2B France (Chorus Pro)',
            default => $category,
        };
    }

    /**
     * Exporter en CSV pour la DGFIP
     * Format : une ligne par facture soumise à E-Reporting
     */
    public function exportCsv(array $report): string
    {
        $csv = implode(';', [
            'N° Facture',
            'Date',
            'Type',
            'Catégorie',
            'Client',
            'Pays',
            'SIRET Client',
            'N° TVA Client',
            'Montant HT',
            'TVA',
            'Montant TTC',
            'Mode Paiement',
        ]) . "\n";

        foreach ($report['lines'] as $line) {
            $csv .= implode(';', [
                $line['invoice_number'],
                $line['date'],
                $line['type'],
                $line['category_label'],
                '"' . str_replace('"', '""', $line['customer_name']) . '"',
                $line['customer_country'],
                $line['customer_siret'] ?? '',
                $line['customer_tax_number'] ?? '',
                number_format($line['total_ht'], 2, '.', ''),
                number_format($line['total_vat'], 2, '.', ''),
                number_format($line['total_ttc'], 2, '.', ''),
                $line['payment_method'] ?? '',
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Exporter en XML pour la DGFIP (format simplifié E-Reporting)
     */
    public function exportXml(array $report): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<EReporting xmlns="urn:frecorp:e-reporting:2026">' . "\n";
        $xml .= '  <Period>' . "\n";
        $xml .= '    <From>' . $report['period']['from'] . '</From>' . "\n";
        $xml .= '    <To>' . $report['period']['to'] . '</To>' . "\n";
        $xml .= '  </Period>' . "\n";
        $xml .= '  <GeneratedAt>' . $report['generated_at'] . '</GeneratedAt>' . "\n";

        // Résumé
        $xml .= '  <Summary>' . "\n";
        $xml .= '    <TotalEReporting>' . $report['summary']['e_reporting_count'] . '</TotalEReporting>' . "\n";
        $xml .= '    <B2CFrance>' . $report['summary']['b2c_france'] . '</B2CFrance>' . "\n";
        $xml .= '    <B2BIntraEU>' . $report['summary']['b2b_intra_eu'] . '</B2BIntraEU>' . "\n";
        $xml .= '    <B2CInternational>' . $report['summary']['b2c_international'] . '</B2CInternational>' . "\n";
        $xml .= '    <Export>' . $report['summary']['export'] . '</Export>' . "\n";
        $xml .= '  </Summary>' . "\n";

        // Totaux par catégorie
        $xml .= '  <Totals>' . "\n";
        foreach ($report['totals'] as $catKey => $totals) {
            $xml .= '    <Category name="' . htmlspecialchars($catKey) . '">' . "\n";
            $xml .= '      <TotalHT>' . number_format($totals['ht'], 2, '.', '') . '</TotalHT>' . "\n";
            $xml .= '      <TotalVAT>' . number_format($totals['vat'], 2, '.', '') . '</TotalVAT>' . "\n";
            $xml .= '      <TotalTTC>' . number_format($totals['ttc'], 2, '.', '') . '</TotalTTC>' . "\n";
            $xml .= '    </Category>' . "\n";
        }
        $xml .= '  </Totals>' . "\n";

        // Ventilation TVA
        $xml .= '  <VATBreakdown>' . "\n";
        foreach ($report['vat_breakdown'] as $vat) {
            $xml .= '    <VAT rate="' . $vat['rate'] . '">' . "\n";
            $xml .= '      <Base>' . number_format($vat['base'], 2, '.', '') . '</Base>' . "\n";
            $xml .= '      <Amount>' . number_format($vat['amount'], 2, '.', '') . '</Amount>' . "\n";
            $xml .= '    </VAT>' . "\n";
        }
        $xml .= '  </VATBreakdown>' . "\n";

        // Lignes détaillées
        $xml .= '  <Invoices>' . "\n";
        foreach ($report['lines'] as $line) {
            $xml .= '    <Invoice>' . "\n";
            $xml .= '      <Number>' . htmlspecialchars($line['invoice_number']) . '</Number>' . "\n";
            $xml .= '      <Date>' . $line['date'] . '</Date>' . "\n";
            $xml .= '      <Type>' . $line['type'] . '</Type>' . "\n";
            $xml .= '      <Category>' . $line['category'] . '</Category>' . "\n";
            $xml .= '      <CustomerName>' . htmlspecialchars($line['customer_name']) . '</CustomerName>' . "\n";
            $xml .= '      <CustomerCountry>' . $line['customer_country'] . '</CustomerCountry>' . "\n";
            if ($line['customer_siret']) {
                $xml .= '      <CustomerSIRET>' . $line['customer_siret'] . '</CustomerSIRET>' . "\n";
            }
            if ($line['customer_tax_number']) {
                $xml .= '      <CustomerVATNumber>' . htmlspecialchars($line['customer_tax_number']) . '</CustomerVATNumber>' . "\n";
            }
            $xml .= '      <TotalHT>' . number_format($line['total_ht'], 2, '.', '') . '</TotalHT>' . "\n";
            $xml .= '      <TotalVAT>' . number_format($line['total_vat'], 2, '.', '') . '</TotalVAT>' . "\n";
            $xml .= '      <TotalTTC>' . number_format($line['total_ttc'], 2, '.', '') . '</TotalTTC>' . "\n";
            $xml .= '      <PaymentMethod>' . ($line['payment_method'] ?? '') . '</PaymentMethod>' . "\n";
            $xml .= '    </Invoice>' . "\n";
        }
        $xml .= '  </Invoices>' . "\n";
        $xml .= '</EReporting>' . "\n";

        return $xml;
    }
}
