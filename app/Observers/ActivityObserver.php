<?php

namespace App\Observers;

use Spatie\Activitylog\Models\Activity;

class ActivityObserver
{
    /**
     * Automatiquement ajouter le company_id lors de la création d'un log
     */
    public function creating(Activity $activity): void
    {
        // Si le subject a un company_id, on l'ajoute au log
        if ($activity->subject && method_exists($activity->subject, 'getAttribute')) {
            $companyId = $activity->subject->getAttribute('company_id');
            
            if ($companyId) {
                $activity->company_id = $companyId;
            }
        }
        
        // Si pas de company_id via subject, essayer via le causer (utilisateur)
        if (!$activity->company_id && $activity->causer) {
            // Si l'utilisateur est dans un contexte Filament avec tenant
            if (function_exists('filament') && filament()->getTenant()) {
                $activity->company_id = filament()->getTenant()->id;
            }
        }
    }
}
