<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory, BelongsToCompany;

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
