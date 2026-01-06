<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Supplier extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'address'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('suppliers')
            ->setDescriptionForEvent(fn(string $eventName) => "Fournisseur {$eventName}")
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'notes',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
