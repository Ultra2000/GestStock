<?php

namespace App\Services\Integration;

use App\Models\CompanyIntegration;

interface IntegrationServiceInterface
{
    public function authenticate(CompanyIntegration $integration): bool;
    public function sync(CompanyIntegration $integration): void;
}