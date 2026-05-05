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

        $inventory->load(['warehouse', 'items.product.supplier']);

        $grouped = $inventory->items
            ->sortBy([
                fn ($a, $b) => strcmp(
                    $a->product?->supplier?->name ?? 'ZZZ',
                    $b->product?->supplier?->name ?? 'ZZZ'
                ),
                fn ($a, $b) => strcmp($a->product?->name ?? '', $b->product?->name ?? ''),
            ])
            ->groupBy(fn ($item) => $item->product?->supplier?->name ?? 'Sans fournisseur');

        $xlsx    = $this->buildXlsx($inventory, $grouped);
        $name    = preg_replace('/[^a-zA-Z0-9_-]/', '-', $inventory->reference ?? 'inventaire');
        $filename = 'inventaire-' . $name . '-' . now()->format('Y-m-d') . '.xlsx';

        return response($xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store',
            'Pragma'              => 'no-cache',
        ]);
    }

    // =========================================================
    // CONSTRUCTION DU FICHIER XLSX (OpenXML via ZipArchive)
    // =========================================================

    private function buildXlsx(Inventory $inventory, $grouped): string
    {
        $rows      = [];   // [ [values...], styleKey ]
        $styles    = $this->buildStyles();
        $sharedStr = [];   // index => string
        $ssIndex   = 0;

        $addStr = function (string $s) use (&$sharedStr, &$ssIndex): int {
            $key = array_search($s, $sharedStr);
            if ($key !== false) return $key;
            $sharedStr[$ssIndex] = $s;
            return $ssIndex++;
        };

        // Helper: cellule string partagée
        $s = fn ($v, $style = 0) => ['t' => 's', 'v' => $addStr((string) $v), 'style' => $style];
        // Helper: cellule nombre
        $n = fn ($v, $style = 0) => ['t' => 'n', 'v' => $v, 'style' => $style];
        // Helper: cellule vide
        $e = fn ($style = 0) => ['t' => 'e', 'v' => '', 'style' => $style];

        $HEADER  = 1; // style index for header (bleu)
        $SUPP    = 2; // style index for supplier row
        $SUBTOT  = 3; // style index for subtotal
        $GTOTAL  = 4; // style index for grand total
        $OK      = 5;
        $SHORT   = 6;
        $SURPLUS = 7;
        $GREY    = 8;
        $BOLD    = 9;
        $NUM     = 10;

        // --- Titre inventaire ---
        $warehouseName = $inventory->warehouse?->name ?? '—';
        $date = $inventory->validated_at?->format('d/m/Y') ?? $inventory->inventory_date?->format('d/m/Y') ?? now()->format('d/m/Y');
        $rows[] = [[$s("Inventaire : {$inventory->name}", $BOLD), $e(), $e(), $e(), $e(), $e(), $e(), $e(), $e()], 'title'];
        $rows[] = [[$s("Entrepôt : {$warehouseName}  |  Date : {$date}  |  Statut : " . $this->statusLabel($inventory->status), 0), $e(), $e(), $e(), $e(), $e(), $e(), $e(), $e()], ''];
        $rows[] = [[$e(), $e(), $e(), $e(), $e(), $e(), $e(), $e(), $e()], ''];

        // --- En-têtes colonnes ---
        $rows[] = [[
            $s('Fournisseur', $HEADER), $s('Produit', $HEADER), $s('Code', $HEADER), $s('Unité', $HEADER),
            $s('Qté attendue', $HEADER), $s('Qté comptée', $HEADER), $s('Écart', $HEADER),
            $s('P.U. Achat HT', $HEADER), $s('Valeur écart', $HEADER),
        ], ''];

        $grandExp = $grandCnt = $grandDiff = $grandVal = 0;

        foreach ($grouped as $supplierName => $items) {
            // Ligne fournisseur
            $rows[] = [[$s($supplierName, $SUPP), $e($SUPP), $e($SUPP), $e($SUPP), $e($SUPP), $e($SUPP), $e($SUPP), $e($SUPP), $e($SUPP)], ''];

            $supExp = $supCnt = $supDiff = $supVal = 0;

            foreach ($items as $item) {
                $product  = $item->product;
                $expected = (float) ($item->quantity_expected ?? 0);
                $counted  = (float) ($item->quantity_counted ?? 0);
                $diff     = $item->is_counted ? ($counted - $expected) : null;
                $unitCost = (float) ($item->unit_cost ?? $product?->purchase_price ?? 0);
                $value    = ($diff !== null && $unitCost > 0) ? round($diff * $unitCost, 2) : null;

                $cellStyle = !$item->is_counted ? $GREY : ($diff < 0 ? $SHORT : ($diff > 0 ? $SURPLUS : $OK));

                $rows[] = [[
                    $s($supplierName, $cellStyle),
                    $s($product?->name ?? '—', $cellStyle),
                    $s($product?->code ?? '—', $cellStyle),
                    $s($product?->unit ?? 'pcs', $cellStyle),
                    $n($expected, $NUM),
                    $item->is_counted ? $n($counted, $NUM) : $e(),
                    $diff !== null ? $n($diff, $NUM) : $e(),
                    $unitCost > 0 ? $n($unitCost, $NUM) : $e(),
                    $value !== null ? $n($value, $NUM) : $e(),
                ], ''];

                $supExp  += $expected;
                $supCnt  += $item->is_counted ? $counted : 0;
                $supDiff += $diff ?? 0;
                $supVal  += $value ?? 0;
            }

            // Sous-total
            $rows[] = [[
                $s('Sous-total ' . $supplierName, $SUBTOT),
                $e($SUBTOT), $e($SUBTOT), $e($SUBTOT),
                $n($supExp, $SUBTOT), $n($supCnt, $SUBTOT),
                $n($supDiff, $SUBTOT), $e($SUBTOT),
                $n($supVal, $SUBTOT),
            ], ''];

            $grandExp  += $supExp;
            $grandCnt  += $supCnt;
            $grandDiff += $supDiff;
            $grandVal  += $supVal;
        }

        // Grand total
        $rows[] = [[$e(), $e(), $e(), $e(), $e(), $e(), $e(), $e(), $e()], ''];
        $rows[] = [[
            $s('TOTAL GÉNÉRAL', $GTOTAL), $e($GTOTAL), $e($GTOTAL), $e($GTOTAL),
            $n($grandExp, $GTOTAL), $n($grandCnt, $GTOTAL),
            $n($grandDiff, $GTOTAL), $e($GTOTAL),
            $n($grandVal, $GTOTAL),
        ], ''];

        // Construire les XML
        $sheetXml      = $this->buildSheetXml($rows);
        $sharedStrXml  = $this->buildSharedStringsXml($sharedStr);
        $stylesXml     = $styles;

        // Créer le ZIP en mémoire
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml',       $this->contentTypes());
        $zip->addFromString('_rels/.rels',               $this->rootRels());
        $zip->addFromString('xl/workbook.xml',           $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/worksheets/sheet1.xml',  $sheetXml);
        $zip->addFromString('xl/styles.xml',             $stylesXml);
        $zip->addFromString('xl/sharedStrings.xml',      $sharedStrXml);

        $zip->close();

        $content = file_get_contents($tmp);
        unlink($tmp);

        return $content;
    }

    private function buildSheetXml(array $rows): string
    {
        $colWidths = '<col min="1" max="1" width="22" customWidth="1"/>'
            . '<col min="2" max="2" width="32" customWidth="1"/>'
            . '<col min="3" max="3" width="14" customWidth="1"/>'
            . '<col min="4" max="4" width="10" customWidth="1"/>'
            . '<col min="5" max="9" width="16" customWidth="1"/>';

        $rowsXml = '';
        foreach ($rows as $i => [$cells]) {
            $rowNum   = $i + 1;
            $cellsXml = '';
            $colNum   = 0;
            foreach ($cells as $cell) {
                $colNum++;
                $col   = $this->colLetter($colNum);
                $ref   = $col . $rowNum;
                $style = $cell['style'] ?? 0;

                if ($cell['t'] === 'e') {
                    $cellsXml .= '<c r="' . $ref . '" s="' . $style . '"/>';
                } elseif ($cell['t'] === 's') {
                    $cellsXml .= '<c r="' . $ref . '" t="s" s="' . $style . '"><v>' . $cell['v'] . '</v></c>';
                } else {
                    $cellsXml .= '<c r="' . $ref . '" s="' . $style . '"><v>' . htmlspecialchars((string) $cell['v'], ENT_XML1) . '</v></c>';
                }
            }
            $rowsXml .= '<row r="' . $rowNum . '">' . $cellsXml . '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<cols>' . $colWidths . '</cols>'
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . '</worksheet>';
    }

    private function buildSharedStringsXml(array $strings): string
    {
        $items = '';
        foreach ($strings as $str) {
            $items .= '<si><t xml:space="preserve">' . htmlspecialchars($str, ENT_XML1, 'UTF-8') . '</t></si>';
        }
        $count = count($strings);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' count="' . $count . '" uniqueCount="' . $count . '">'
            . $items . '</sst>';
    }

    private function buildStyles(): string
    {
        // Styles OpenXML (indexés, référencés par s="N" dans les cellules)
        // 0=default, 1=header, 2=supplier, 3=subtotal, 4=grandtotal
        // 5=ok, 6=shortage, 7=surplus, 8=grey/uncounted, 9=bold, 10=number
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>'
            . '<fonts count="4">'
            .   '<font><sz val="11"/><name val="Calibri"/></font>'                          // 0 default
            .   '<font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font>' // 1 white bold
            .   '<font><b/><color rgb="FF1e3a5f"/><sz val="11"/><name val="Calibri"/></font>' // 2 dark blue bold
            .   '<font><b/><sz val="11"/><name val="Calibri"/></font>'                      // 3 bold
            . '</fonts>'
            . '<fills count="9">'
            .   '<fill><patternFill patternType="none"/></fill>'         // 0 default
            .   '<fill><patternFill patternType="gray125"/></fill>'      // 1 required
            .   '<fill><patternFill patternType="solid"><fgColor rgb="FF2563eb"/></patternFill></fill>'  // 2 header blue
            .   '<fill><patternFill patternType="solid"><fgColor rgb="FFdbeafe"/></patternFill></fill>'  // 3 supplier light blue
            .   '<fill><patternFill patternType="solid"><fgColor rgb="FFe0e7ff"/></patternFill></fill>'  // 4 subtotal indigo
            .   '<fill><patternFill patternType="solid"><fgColor rgb="FF1e3a5f"/></patternFill></fill>'  // 5 grand total dark
            .   '<fill><patternFill patternType="solid"><fgColor rgb="FFf0fdf4"/></patternFill></fill>'  // 6 ok green
            .   '<fill><patternFill patternType="solid"><fgColor rgb="FFfff1f2"/></patternFill></fill>'  // 7 shortage red
            .   '<fill><patternFill patternType="solid"><fgColor rgb="FFeff6ff"/></patternFill></fill>'  // 8 surplus blue
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="11">'
            .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'              // 0 default
            .   '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'  // 1 header
            .   '<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'  // 2 supplier
            .   '<xf numFmtId="0" fontId="3" fillId="4" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'  // 3 subtotal
            .   '<xf numFmtId="0" fontId="1" fillId="5" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'  // 4 grand total
            .   '<xf numFmtId="164" fontId="0" fillId="6" borderId="0" xfId="0" applyFill="1" applyNumberFormat="1"/>'  // 5 ok
            .   '<xf numFmtId="164" fontId="0" fillId="7" borderId="0" xfId="0" applyFill="1" applyNumberFormat="1"/>'  // 6 shortage
            .   '<xf numFmtId="164" fontId="0" fillId="8" borderId="0" xfId="0" applyFill="1" applyNumberFormat="1"/>'  // 7 surplus
            .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'              // 8 grey (same as default, italic not in basic OpenXML)
            .   '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'// 9 bold
            .   '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'  // 10 number
            . '</cellXfs>'
            . '</styleSheet>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Inventaire" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            . '</Relationships>';
    }

    private function colLetter(int $n): string
    {
        $letters = '';
        while ($n > 0) {
            $n--;
            $letters = chr(65 + ($n % 26)) . $letters;
            $n       = intdiv($n, 26);
        }
        return $letters;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'validated'          => 'Validé',
            'pending_validation' => 'En attente de validation',
            'in_progress'        => 'En cours',
            'draft'              => 'Brouillon',
            'cancelled'          => 'Annulé',
            default              => $status,
        };
    }
}
