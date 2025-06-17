<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'website',
        'logo_path',
        'tax_number',
        'registration_number',
        'footer_text',
    ];

    public static function getCompany()
    {
        return static::first() ?? new static();
    }
} 