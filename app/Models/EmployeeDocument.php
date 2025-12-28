<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'type',
        'file_path',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date->isBetween(now(), now()->addDays($days));
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'contract' => 'Contrat',
            'id_card' => "Pièce d'identité",
            'diploma' => 'Diplôme',
            'certificate' => 'Certificat',
            'medical' => 'Certificat médical',
            'driving_license' => 'Permis de conduire',
            'other' => 'Autre',
            default => $this->type,
        };
    }
}
