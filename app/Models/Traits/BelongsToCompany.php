<?php

namespace App\Models\Traits;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        // Filtre automatique: ne voir que les données de son entreprise
        static::addGlobalScope('company', function (Builder $builder) {
            if (Filament::getTenant()) {
                // Utiliser le nom de table qualifié pour éviter les ambiguïtés dans les jointures
                $table = (new static)->getTable();
                $builder->where("{$table}.company_id", Filament::getTenant()->id);
            }
        });

        // Ajout automatique du company_id à la création
        static::creating(function (Model $model) {
            if (Filament::getTenant() && empty($model->company_id)) {
                $model->company_id = Filament::getTenant()->id;
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}
