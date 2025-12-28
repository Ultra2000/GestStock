<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BarcodeGenerator
{
    /**
     * Génère le prochain code interne de type P + année(2) + séquence 6 chiffres.
     */
    public static function nextInternalCode(): string
    {
        $year = date('y');

        // On stocke un compteur par année pour éviter de scanner la table entière.
        return DB::transaction(function () use ($year) {
            $row = DB::table('sequences')->where('name', 'product_'.$year)->lockForUpdate()->first();
            if (!$row) {
                DB::table('sequences')->insert([
                    'name' => 'product_'.$year,
                    'value' => 1,
                ]);
                $value = 1;
            } else {
                $value = $row->value + 1;
                DB::table('sequences')->where('id', $row->id)->update(['value' => $value]);
            }

            return 'P' . $year . str_pad((string)$value, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Fallback si la table sequences n'existe pas encore (premier déploiement avant migration). Analyse les codes existants.
     */
    public static function naiveInternalCode(): string
    {
        $year = date('y');
        $maxNumeric = DB::table('products')
            ->selectRaw("MAX(CAST(SUBSTR(code, 3) AS UNSIGNED)) as max_part")
            ->where('code', 'like', 'P'.$year.'%')
            ->value('max_part');
        $next = ((int)$maxNumeric) + 1;
        return 'P'.$year.str_pad((string)$next, 6, '0', STR_PAD_LEFT);
    }
}
