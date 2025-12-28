<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AttendanceQrToken extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'token',
        'expires_at',
        'is_used',
        'used_by_employee_id',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    // Relations
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function usedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'used_by_employee_id');
    }

    // Génération d'un nouveau token
    public static function generateForWarehouse(Warehouse $warehouse, int $validityMinutes = 5): self
    {
        // Invalider les anciens tokens non utilisés
        static::where('warehouse_id', $warehouse->id)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        return static::create([
            'company_id' => $warehouse->company_id,
            'warehouse_id' => $warehouse->id,
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes($validityMinutes),
        ]);
    }

    // Valider un token
    public static function validateToken(string $token, int $warehouseId, ?int $employeeId = null): array
    {
        $qrToken = static::where('token', $token)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$qrToken) {
            return ['valid' => false, 'reason' => 'qr_invalid'];
        }

        if ($qrToken->expires_at->isPast()) {
            return ['valid' => false, 'reason' => 'qr_expired'];
        }

        if ($qrToken->is_used) {
            return ['valid' => false, 'reason' => 'qr_already_used'];
        }

        // Marquer comme utilisé
        $qrToken->update([
            'is_used' => true,
            'used_by_employee_id' => $employeeId,
            'used_at' => now(),
        ]);

        return ['valid' => true, 'token' => $qrToken];
    }

    // Vérifier si un token est valide (sans le marquer comme utilisé)
    public static function checkToken(string $token, int $warehouseId): array
    {
        $qrToken = static::where('token', $token)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$qrToken) {
            return ['valid' => false, 'reason' => 'qr_invalid'];
        }

        if ($qrToken->expires_at->isPast()) {
            return ['valid' => false, 'reason' => 'qr_expired'];
        }

        if ($qrToken->is_used) {
            return ['valid' => false, 'reason' => 'qr_already_used'];
        }

        return ['valid' => true, 'token' => $qrToken, 'expires_in' => $qrToken->expires_at->diffInSeconds(now())];
    }

    // Scope pour les tokens actifs
    public function scopeActive($query)
    {
        return $query->where('is_used', false)
            ->where('expires_at', '>', now());
    }

    // Générer le contenu du QR Code (JSON encodé)
    public function getQrContent(): string
    {
        return json_encode([
            'type' => 'attendance',
            'warehouse_id' => $this->warehouse_id,
            'token' => $this->token,
            'expires_at' => $this->expires_at->toIso8601String(),
        ]);
    }
}
