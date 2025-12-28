<?php

namespace App\Services\Integration;

use App\Models\CompanyIntegration;
use App\Models\Sale;
use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PpfService implements IntegrationServiceInterface
{
    protected FacturXGenerator $generator;

    public function __construct(FacturXGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Retourne les URLs en fonction de l'environnement
     */
    protected function getUrls(CompanyIntegration $integration): array
    {
        $settings = $integration->settings ?? [];
        $environment = $settings['environment'] ?? 'sandbox';

        if ($environment === 'production') {
            return [
                'auth' => 'https://oauth.piste.gouv.fr/api/oauth/token',
                'api' => 'https://api.piste.gouv.fr/cpro/factures/v1',
            ];
        }

        return [
            'auth' => 'https://sandbox-oauth.piste.gouv.fr/api/oauth/token',
            'api' => 'https://sandbox-api.piste.gouv.fr/cpro/factures/v1',
        ];
    }

    /**
     * Authentification OAuth
     */
    public function authenticate(CompanyIntegration $integration, string $role = 'fournisseur'): bool
    {
        $cacheKey = "ppf_token_{$integration->id}_{$role}";
        
        if (Cache::has($cacheKey)) {
            return true;
        }

        $settings = $integration->settings ?? [];
        $urls = $this->getUrls($integration);

        // Credentials OAuth PISTE
        $clientId = $settings['client_id'] ?? config('services.ppf.client_id');
        $clientSecret = $settings['client_secret'] ?? config('services.ppf.client_secret');

        if (!$clientId || !$clientSecret) {
            Log::error("PPF Integration: Missing PISTE client credentials for integration {$integration->id}");
            $integration->update(['last_error' => 'Credentials PISTE manquants (client_id/client_secret)']);
            return false;
        }

        try {
            $response = Http::asForm()->post($urls['auth'], [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => 'openid',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'];
                $expiresIn = $data['expires_in'];

                Cache::put($cacheKey, $accessToken, $expiresIn - 60);

                $integration->update([
                    'access_token' => $accessToken,
                    'expires_at' => now()->addSeconds($expiresIn),
                    'last_sync_at' => now(),
                    'last_success_at' => now(),
                    'last_error' => null,
                ]);

                return true;
            } else {
                Log::error("PPF Auth Failed: " . $response->status() . " - " . $response->body());
                $integration->update(['last_error' => "Auth failed: " . $response->body()]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("PPF Auth Exception: " . $e->getMessage());
            $integration->update(['last_error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Construit les headers pour l'API
     */
    protected function buildHeaders(CompanyIntegration $integration): array
    {
        $settings = $integration->settings ?? [];
        
        $apiKey = $settings['api_key'] ?? config('services.ppf.api_key');
        $login = $settings['fournisseur_login'] ?? config('services.ppf.cpro_account_login');
        $password = $settings['fournisseur_password'] ?? config('services.ppf.cpro_account_password');

        $headers = [
            'Accept' => 'application/json;charset=utf-8',
            'Content-Type' => 'application/json;charset=utf-8',
        ];

        if ($apiKey) {
            $headers['KeyId'] = $apiKey;
        }

        if ($login && $password) {
            $headers['cpro-account'] = base64_encode($login . ':' . $password);
        }

        return $headers;
    }

    /**
     * Récupère le token OAuth en cache
     */
    protected function getToken(CompanyIntegration $integration, string $role = 'fournisseur'): ?string
    {
        return Cache::get("ppf_token_{$integration->id}_{$role}");
    }

    public function sync(CompanyIntegration $integration): void
    {
        // Not implemented
    }

    /**
     * Envoie une facture au PPF
     */
    public function sendInvoice(Sale $sale): bool
    {
        $integration = $sale->company->integrations()->where('service_name', 'ppf')->first();

        if (!$integration || !$integration->is_active) {
            throw new \Exception("Integration PPF non configurée ou inactive. Configurez-la dans le panneau Super Admin.");
        }

        $settings = $integration->settings ?? [];
        
        $fournisseurLogin = $settings['fournisseur_login'] ?? config('services.ppf.cpro_account_login');
        if (!$fournisseurLogin) {
            throw new \Exception("Credentials fournisseur PPF non configurés.");
        }

        if (!$this->authenticate($integration, 'fournisseur')) {
            $error = $integration->last_error ?? "Erreur inconnue";
            throw new \Exception("Echec de l'authentification PPF: " . $error);
        }

        $token = $this->getToken($integration, 'fournisseur');
        $urls = $this->getUrls($integration);
        $xmlContent = $this->generator->generateXml($sale);
        
        try {
            $xmlBase64 = base64_encode($xmlContent);
            $headers = $this->buildHeaders($integration);

            $payload = [
                'fichierFlux' => $xmlBase64,
                'nomFichier' => 'facture_' . $sale->invoice_number . '.xml',
                'syntaxeFlux' => config('services.ppf.syntaxe_flux', 'IN_DP_E1_CII_16B'),
                'avecSignature' => false,
            ];

            Log::info("PPF: Envoi facture {$sale->invoice_number}");

            $response = Http::withToken($token)
                ->withHeaders($headers)
                ->timeout(30)
                ->post("{$urls['api']}/deposer/flux", $payload);

            Log::info("PPF Response Status: " . $response->status());

            if ($response->successful()) {
                $data = $response->json();
                
                $sale->update([
                    'ppf_status' => 'DEPOSEE',
                    'ppf_id' => $data['numeroFluxDepot'] ?? $data['identifiantFactureCPP'] ?? ('PPF-' . uniqid()),
                ]);
                
                return true;
            } else {
                $errorBody = $response->json() ?? ['message' => $response->body()];
                $errorMessage = $errorBody['message'] ?? $errorBody['libelle'] ?? $response->body();
                
                Log::error("PPF Submit Failed: " . $response->status() . " - " . $errorMessage);
                
                $sale->update(['ppf_status' => 'ERREUR']);
                
                throw new \Exception("Erreur PPF ({$response->status()}): " . $errorMessage);
            }

        } catch (\Exception $e) {
            Log::error("PPF Send Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère le statut d'une facture depuis Chorus Pro
     */
    public function getInvoiceStatus(Sale $sale): ?array
    {
        if (!$sale->ppf_id) {
            return null;
        }

        $integration = $sale->company->integrations()->where('service_name', 'ppf')->first();
        
        if (!$integration) {
            throw new \Exception("Integration PPF non configurée.");
        }

        if (!$this->authenticate($integration, 'fournisseur')) {
            throw new \Exception("Echec de l'authentification PPF");
        }

        $token = $this->getToken($integration, 'fournisseur');
        $urls = $this->getUrls($integration);
        $headers = $this->buildHeaders($integration);

        $response = Http::withToken($token)
            ->withHeaders($headers)
            ->timeout(30)
            ->post("{$urls['api']}/rechercher/fournisseur", [
                'numeroFacture' => substr($sale->invoice_number, 0, 20)
            ]);

        if ($response->successful()) {
            $data = $response->json();
            
            if (isset($data['listeFactures']) && count($data['listeFactures']) > 0) {
                return $data['listeFactures'][0];
            }
        }

        Log::warning("PPF: Facture {$sale->invoice_number} non trouvée dans Chorus Pro");
        return null;
    }

    /**
     * Synchronise le statut d'une facture depuis Chorus Pro
     */
    public function syncInvoiceStatus(Sale $sale): bool
    {
        try {
            $invoiceData = $this->getInvoiceStatus($sale);
            
            if ($invoiceData) {
                $sale->update([
                    'ppf_status' => $invoiceData['statut'] ?? $sale->ppf_status,
                    'ppf_chorus_id' => $invoiceData['identifiantFactureCPP'] ?? null,
                ]);
                
                Log::info("PPF: Statut de {$sale->invoice_number} mis à jour: {$invoiceData['statut']}");
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("PPF Sync Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Synchronise les statuts de toutes les factures en attente
     */
    public function syncAllPendingInvoices(?int $companyId = null): int
    {
        $query = Sale::whereNotNull('ppf_id')
            ->whereNotIn('ppf_status', ['PAYEE', 'REJETEE', 'ERREUR']);
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $sales = $query->get();
        $synced = 0;

        foreach ($sales as $sale) {
            if ($this->syncInvoiceStatus($sale)) {
                $synced++;
            }
            usleep(200000); // 200ms pause
        }

        return $synced;
    }
}
