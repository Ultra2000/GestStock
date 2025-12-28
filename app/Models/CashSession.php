<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'opening_amount',
        'closing_amount',
        'expected_amount',
        'difference',
        'sales_count',
        'total_sales',
        'total_cash',
        'total_card',
        'total_mobile',
        'total_other',
        'notes',
        'opened_at',
        'closed_at',
        'status',
    ];

    protected $casts = [
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_cash' => 'decimal:2',
        'total_card' => 'decimal:2',
        'total_mobile' => 'decimal:2',
        'total_other' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Vérifie si la session est ouverte
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Ouvre une nouvelle session de caisse
     */
    public static function openSession(int $userId, int $companyId, float $openingAmount = 0): self
    {
        return static::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'opening_amount' => $openingAmount,
            'opened_at' => now(),
            'status' => 'open',
        ]);
    }

    /**
     * Ferme la session de caisse
     */
    public function closeSession(float $closingAmount, ?string $notes = null): self
    {
        $this->recalculate();
        
        $this->update([
            'closing_amount' => $closingAmount,
            'difference' => $closingAmount - $this->expected_amount,
            'notes' => $notes,
            'closed_at' => now(),
            'status' => 'closed',
        ]);

        return $this;
    }

    /**
     * Recalcule les totaux de la session
     */
    public function recalculate(): self
    {
        $sales = $this->sales()->where('status', 'completed')->get();
        
        $this->sales_count = $sales->count();
        $this->total_sales = $sales->sum('total');
        $this->total_cash = $sales->where('payment_method', 'cash')->sum('total');
        $this->total_card = $sales->where('payment_method', 'card')->sum('total');
        $this->total_mobile = $sales->where('payment_method', 'mobile')->sum('total');
        $this->total_other = $sales->whereNotIn('payment_method', ['cash', 'card', 'mobile'])->sum('total');
        
        // Montant attendu = fond de caisse + ventes espèces
        $this->expected_amount = $this->opening_amount + $this->total_cash;
        
        $this->save();
        
        return $this;
    }

    /**
     * Récupère la session ouverte pour un utilisateur
     */
    public static function getOpenSession(int $companyId, int $userId): ?self
    {
        return static::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->first();
    }

    /**
     * Total des ventes de la session
     */
    public function getTotalAttribute(): float
    {
        return $this->total_cash + $this->total_card + $this->total_mobile + $this->total_other;
    }
}
