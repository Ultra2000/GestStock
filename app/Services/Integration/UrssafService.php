<?php

namespace App\Services\Integration;

use App\Models\CompanyIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UrssafService implements IntegrationServiceInterface
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.urssaf.url', 'https://api.urssaf.fr');
    }

    public function authenticate(CompanyIntegration $integration): bool
    {
        // Simplified Auth Logic
        if ($integration->access_token && $integration->expires_at && $integration->expires_at->isFuture()) {
            return true;
        }
        
        // Mock Refresh
        $integration->update([
            'access_token' => 'mock_urssaf_token_' . uniqid(),
            'expires_at' => now()->addHours(1),
        ]);
        
        return true;
    }

    public function sync(CompanyIntegration $integration): void
    {
        // Not used directly
    }

    public function getAccountSituation(CompanyIntegration $integration): array
    {
        if (!$this->authenticate($integration)) {
            return ['error' => 'Auth failed'];
        }

        // Mock API Response
        // In real life: Http::withToken($integration->access_token)->get(...)
        
        return [
            'balance' => 1250.00, // Dette
            'next_due_date' => now()->addDays(15)->format('Y-m-d'),
            'next_due_amount' => 450.00,
            'status' => 'compliant', // or 'debt'
        ];
    }

    public function getVigilanceCertificate(CompanyIntegration $integration): ?string
    {
        if (!$this->authenticate($integration)) {
            return null;
        }
        
        // Mock PDF URL
        return 'https://urssaf.fr/attestation_vigilance_mock.pdf';
    }
}