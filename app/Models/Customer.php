<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'address'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('customers')
            ->setDescriptionForEvent(fn(string $eventName) => "Client {$eventName}")
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    protected $fillable = [
        'company_id',
        'name',
        'registration_number',
        'siret',
        'tax_number',
        'email',
        'phone',
        'address',
        'zip_code',
        'city',
        'country',
        'country_code',
        'notes',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
