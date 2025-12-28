<?php

namespace App\Services;

use App\Models\AccountingRule;
use App\Models\BankTransaction;
use Illuminate\Support\Str;

class AccountingService
{
    public function applyRules(BankTransaction $transaction): bool
    {
        // Si une catégorie est déjà définie, on ne fait rien (priorité à la saisie manuelle)
        if ($transaction->accounting_category_id) {
            return false;
        }

        // Charger les règles de l'entreprise
        $rules = AccountingRule::where('company_id', $transaction->bankAccount->company_id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc') // Les priorités les plus hautes d'abord
            ->get();

        foreach ($rules as $rule) {
            if ($this->matchesRule($transaction, $rule)) {
                $transaction->update([
                    'accounting_category_id' => $rule->accounting_category_id,
                    // On pourrait passer le statut à 'reconciled' si on est sûr, 
                    // mais gardons 'pending' pour validation humaine par sécurité pour l'instant.
                ]);
                return true; // Règle appliquée
            }
        }

        return false; // Aucune règle appliquée
    }

    protected function matchesRule(BankTransaction $transaction, AccountingRule $rule): bool
    {
        $value = Str::lower($transaction->label);
        $condition = Str::lower($rule->condition_value);

        return match ($rule->condition_type) {
            'contains' => Str::contains($value, $condition),
            'starts_with' => Str::startsWith($value, $condition),
            'ends_with' => Str::endsWith($value, $condition),
            'exact' => $value === $condition,
            default => false,
        };
    }
}
