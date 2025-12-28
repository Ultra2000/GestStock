<?php

namespace App\Filament\Resources\RegisterCompanyResource\Pages;

use App\Filament\Resources\RegisterCompanyResource;
use Filament\Resources\Pages\Page;

class RegisterCompany extends Page
{
    protected static string $resource = RegisterCompanyResource::class;

    protected static string $view = 'filament.resources.register-company-resource.pages.register-company';
}
