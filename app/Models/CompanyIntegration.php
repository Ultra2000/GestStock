<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyIntegration extends Model
{
    protected $fillable = [
        'company_id',
        'service_name',
        'access_token',
        'refresh_token',
        'expires_at',
        'settings',
        'is_active',
        'last_sync_at',
        'last_success_at',
        'last_error',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'settings' => 'array',
        'expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'last_success_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
