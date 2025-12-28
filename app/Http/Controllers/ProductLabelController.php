<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductLabelController extends Controller
{
    public function print(Request $request)
    {
        $ids = collect(explode(',', (string)$request->query('ids')))
            ->filter(fn($v) => is_numeric($v))
            ->unique()
            ->values();
        $qtyParam = $request->query('q', '');
        $quantityMap = [];
        foreach (explode(',', $qtyParam) as $pair) {
            if (str_contains($pair, ':')) {
                [$id,$q] = explode(':', $pair, 2);
                if (is_numeric($id) && is_numeric($q)) {
                    $quantityMap[(int)$id] = max(1, (int)$q);
                }
            }
        }

        $products = Product::whereIn('id', $ids)->get();
        if ($products->isEmpty()) {
            abort(404, 'Aucun produit trouvé');
        }

        $labels = [];
        foreach ($products as $p) {
            $times = $quantityMap[$p->id] ?? 1;
            for ($i=0; $i<$times; $i++) {
                $labels[] = $p;
            }
        }

        $pdf = Pdf::loadView('pdf.product-labels', [
            'labels' => $labels,
            'generatedAt' => now(),
            'columns' => (int)($request->query('cols', 3)),
            'showPrice' => (bool)$request->boolean('price', false),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('etiquettes-produits.pdf');
    }
}
