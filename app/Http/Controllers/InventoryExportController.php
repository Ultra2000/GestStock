<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Filament\Facades\Filament;
use Illuminate\Http\Response;

class InventoryExportController extends Controller
{
    public function exportExcel(Inventory $inventory): Response
    {
        if ($inventory->company) {
            Filament::setTenant($inventory->company);
        }
        $this->authorize('view', $inventory);

        $inventory->load([
            'warehouse',
            'items.product.supplier',
        ]);

        // Grouper les lignes par fournisseur
        $grouped = $inventory->items
            ->sortBy([
                fn ($a, $b) => strcmp(
                    $a->product?->supplier?->name ?? 'ZZZ_Sans fournisseur',
                    $b->product?->supplier?->name ?? 'ZZZ_Sans fournisseur'
                ),
                fn ($a, $b) => strcmp($a->product?->name ?? '', $b->product?->name ?? ''),
            ])
            ->groupBy(fn ($item) => $item->product?->supplier?->name ?? 'Sans fournisseur');

        $inventoryName = $inventory->name ?? $inventory->reference;
        $date          = $inventory->validated_at?->format('d/m/Y') ?? $inventory->inventory_date?->format('d/m/Y') ?? now()->format('d/m/Y');
        $warehouse     = $inventory->warehouse?->name ?? '—';

        $xml = $this->buildSpreadsheetML($inventory, $grouped, $inventoryName, $date, $warehouse);

        $filename = 'inventaire-' . $inventory->reference . '-' . now()->format('Y-m-d') . '.xls';

        return response($xml, 200, [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache',
        ]);
    }

    private function buildSpreadsheetML(Inventory $inventory, $grouped, string $name, string $date, string $warehouse): string
    {
        $rows = '';

        // === Titre de l'inventaire ===
        $rows .= $this->titleRow("Inventaire : {$name}", 9);
        $rows .= $this->titleRow("Entrepôt : {$warehouse}  |  Date : {$date}  |  Statut : " . $this->statusLabel($inventory->status), 9);
        $rows .= $this->emptyRow(9);

        // === En-tête colonnes ===
        $rows .= $this->headerRow([
            'Fournisseur', 'Produit', 'Code', 'Unité',
            'Qté attendue', 'Qté comptée', 'Écart', 'P.U. Achat HT', 'Valeur écart',
        ]);

        // === Lignes par fournisseur ===
        $grandTotalExpected = 0;
        $grandTotalCounted  = 0;
        $grandTotalDiff     = 0;
        $grandTotalValue    = 0;

        foreach ($grouped as $supplierName => $items) {
            $supplierExpected = 0;
            $supplierCounted  = 0;
            $supplierDiff     = 0;
            $supplierValue    = 0;

            // Ligne fournisseur (fond bleu clair)
            $rows .= $this->supplierRow($supplierName, 9);

            foreach ($items as $item) {
                $product  = $item->product;
                $expected = (float) $item->quantity_expected;
                $counted  = (float) ($item->quantity_counted ?? 0);
                $diff     = $counted - $expected;
                $unitCost = (float) ($item->unit_cost ?? $product?->purchase_price ?? 0);
                $value    = round($diff * $unitCost, 2);

                $supplierExpected += $expected;
                $supplierCounted  += $counted;
                $supplierDiff     += $diff;
                $supplierValue    += $value;

                $rows .= $this->dataRow([
                    $supplierName,
                    $product?->name ?? '—',
                    $product?->code ?? '—',
                    $product?->unit ?? 'pcs',
                    $expected,
                    $item->is_counted ? $counted : '',
                    $item->is_counted ? $diff : '',
                    $unitCost > 0 ? $unitCost : '',
                    $item->is_counted && $unitCost > 0 ? $value : '',
                ], $diff < 0 ? 'shortage' : ($diff > 0 ? 'surplus' : 'ok'), $item->is_counted);
            }

            // Sous-total fournisseur
            $rows .= $this->subtotalRow($supplierName, $supplierExpected, $supplierCounted, $supplierDiff, $supplierValue, 9);

            $grandTotalExpected += $supplierExpected;
            $grandTotalCounted  += $supplierCounted;
            $grandTotalDiff     += $supplierDiff;
            $grandTotalValue    += $supplierValue;
        }

        // === Total général ===
        $rows .= $this->emptyRow(9);
        $rows .= $this->grandTotalRow($grandTotalExpected, $grandTotalCounted, $grandTotalDiff, $grandTotalValue, 9);

        $colWidths = '<Column ss:Width="120"/><Column ss:Width="200"/><Column ss:Width="80"/><Column ss:Width="50"/>'
            . '<Column ss:Width="90"/><Column ss:Width="90"/><Column ss:Width="70"/><Column ss:Width="100"/><Column ss:Width="100"/>';

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:x="urn:schemas-microsoft-com:office:excel">
 <Styles>
  <Style ss:ID="title">
   <Font ss:Bold="1" ss:Size="13"/>
   <Alignment ss:Horizontal="Center"/>
   <Interior ss:Color="#1e3a5f" ss:Pattern="Solid"/>
   <Font ss:Color="#FFFFFF" ss:Bold="1" ss:Size="13"/>
  </Style>
  <Style ss:ID="header">
   <Font ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#2563eb" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center"/>
   <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1e40af"/></Borders>
  </Style>
  <Style ss:ID="supplier">
   <Font ss:Bold="1" ss:Color="#1e3a5f"/>
   <Interior ss:Color="#dbeafe" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="subtotal">
   <Font ss:Bold="1"/>
   <Interior ss:Color="#e0e7ff" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#6366f1"/>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#4338ca"/>
   </Borders>
  </Style>
  <Style ss:ID="grandtotal">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11"/>
   <Interior ss:Color="#1e3a5f" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="ok">
   <Interior ss:Color="#f0fdf4" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="0.00"/>
  </Style>
  <Style ss:ID="shortage">
   <Interior ss:Color="#fff1f2" ss:Pattern="Solid"/>
   <Font ss:Color="#b91c1c"/>
   <NumberFormat ss:Format="0.00"/>
  </Style>
  <Style ss:ID="surplus">
   <Interior ss:Color="#eff6ff" ss:Pattern="Solid"/>
   <Font ss:Color="#1d4ed8"/>
   <NumberFormat ss:Format="0.00"/>
  </Style>
  <Style ss:ID="uncounted">
   <Font ss:Color="#9ca3af" ss:Italic="1"/>
  </Style>
  <Style ss:ID="number">
   <NumberFormat ss:Format="0.00"/>
  </Style>
  <Style ss:ID="empty"/>
 </Styles>
 <Worksheet ss:Name="Inventaire">
  <Table>
   {$colWidths}
   {$rows}
  </Table>
 </Worksheet>
</Workbook>
XML;
    }

    private function esc(string $val): string
    {
        return htmlspecialchars($val, ENT_XML1, 'UTF-8');
    }

    private function cell(mixed $value, string $styleId = 'empty', ?string $type = null): string
    {
        if ($value === '' || $value === null) {
            return '<Cell ss:StyleID="' . $styleId . '"><Data ss:Type="String"></Data></Cell>';
        }

        $type  = $type ?? (is_numeric($value) ? 'Number' : 'String');
        $value = $type === 'String' ? $this->esc((string) $value) : (string) $value;

        return '<Cell ss:StyleID="' . $styleId . '"><Data ss:Type="' . $type . '">' . $value . '</Data></Cell>';
    }

    private function titleRow(string $text, int $span): string
    {
        return '<Row ss:Height="22">'
            . '<Cell ss:StyleID="title" ss:MergeAcross="' . ($span - 1) . '">'
            . '<Data ss:Type="String">' . $this->esc($text) . '</Data></Cell>'
            . '</Row>';
    }

    private function emptyRow(int $span): string
    {
        return '<Row ss:Height="8"><Cell ss:MergeAcross="' . ($span - 1) . '"><Data ss:Type="String"></Data></Cell></Row>';
    }

    private function headerRow(array $labels): string
    {
        $cells = implode('', array_map(fn ($l) => $this->cell($l, 'header'), $labels));
        return '<Row ss:Height="20">' . $cells . '</Row>';
    }

    private function supplierRow(string $name, int $span): string
    {
        return '<Row ss:Height="18">'
            . '<Cell ss:StyleID="supplier" ss:MergeAcross="' . ($span - 1) . '">'
            . '<Data ss:Type="String">' . $this->esc('▶ ' . $name) . '</Data></Cell>'
            . '</Row>';
    }

    private function dataRow(array $values, string $status, bool $isCounted): string
    {
        $style = $isCounted ? $status : 'uncounted';
        $cells = '';
        foreach ($values as $i => $val) {
            // Colonnes texte: 0-3, numériques: 4-8
            if ($i >= 4 && $val !== '') {
                $cells .= $this->cell($val, $style, 'Number');
            } else {
                $cells .= $this->cell((string) ($val ?? ''), $i === 0 ? 'empty' : $style);
            }
        }
        return '<Row>' . $cells . '</Row>';
    }

    private function subtotalRow(string $supplier, float $expected, float $counted, float $diff, float $value, int $span): string
    {
        $label = 'Sous-total ' . $supplier;
        return '<Row ss:Height="18">'
            . $this->cell($label, 'subtotal')
            . $this->cell('', 'subtotal')
            . $this->cell('', 'subtotal')
            . $this->cell('', 'subtotal')
            . $this->cell($expected, 'subtotal', 'Number')
            . $this->cell($counted, 'subtotal', 'Number')
            . $this->cell($diff, 'subtotal', 'Number')
            . $this->cell('', 'subtotal')
            . $this->cell($value, 'subtotal', 'Number')
            . '</Row>';
    }

    private function grandTotalRow(float $expected, float $counted, float $diff, float $value, int $span): string
    {
        return '<Row ss:Height="22">'
            . '<Cell ss:StyleID="grandtotal" ss:MergeAcross="3"><Data ss:Type="String">TOTAL GÉNÉRAL</Data></Cell>'
            . $this->cell($expected, 'grandtotal', 'Number')
            . $this->cell($counted, 'grandtotal', 'Number')
            . $this->cell($diff, 'grandtotal', 'Number')
            . $this->cell('', 'grandtotal')
            . $this->cell($value, 'grandtotal', 'Number')
            . '</Row>';
    }

    private function statusLabel(string $status): string
    {
        return match($status) {
            'validated'          => 'Validé',
            'pending_validation' => 'En attente de validation',
            'in_progress'        => 'En cours',
            'draft'              => 'Brouillon',
            'cancelled'          => 'Annulé',
            default              => $status,
        };
    }
}
