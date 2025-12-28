<?php

namespace App\Filament\Resources\AccountingReportsResource\Pages;

use App\Filament\Resources\AccountingReportsResource;
use Filament\Resources\Pages\Page;

class AccountingReports extends Page
{
    protected static string $resource = AccountingReportsResource::class;

    protected static string $view = 'filament.resources.accounting-reports-resource.pages.accounting-reports';
}
