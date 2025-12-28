<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeoLocationService
{
    /**
     * Détecte le pays et la devise en fonction de l'adresse IP
     * Utilise l'API gratuite ip-api.com
     */
    public function getLocationByIp(?string $ip = null): array
    {
        $ip = $ip ?? request()->ip();
        
        // Vérifier le cache pour éviter trop de requêtes API
        $cacheKey = "geolocation_{$ip}";
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Appel à l'API ip-api.com
            $response = Http::timeout(5)
                ->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'country,countryCode,currency',
                ])
                ->throw();

            $data = $response->json();

            if ($data && isset($data['countryCode'])) {
                $result = [
                    'country' => $data['country'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'currency' => $data['currency'] ?? null,
                    'success' => true,
                ];

                // Mettre en cache pendant 30 jours
                Cache::put($cacheKey, $result, now()->addDays(30));

                return $result;
            }
        } catch (\Exception $e) {
            // En cas d'erreur, logger et retourner des valeurs par défaut
            \Log::warning("GeoLocation API error for IP {$ip}: " . $e->getMessage());
        }

        return [
            'country' => null,
            'country_code' => null,
            'currency' => 'EUR', // Valeur par défaut
            'success' => false,
        ];
    }

    /**
     * Mapa des codes pays vers les devises (pour les cas où l'API ne retourne rien)
     */
    public static function countryToCurrency(string $countryCode): ?string
    {
        $mapping = [
            'SN' => 'XOF', // Sénégal - Franc CFA
            'ML' => 'XOF', // Mali - Franc CFA
            'CI' => 'XOF', // Côte d'Ivoire - Franc CFA
            'BJ' => 'XOF', // Bénin - Franc CFA
            'BF' => 'XOF', // Burkina Faso - Franc CFA
            'NE' => 'XOF', // Niger - Franc CFA
            'TG' => 'XOF', // Togo - Franc CFA
            'GA' => 'XAF', // Gabon - Franc CFA
            'CM' => 'XAF', // Cameroun - Franc CFA
            'US' => 'USD', // États-Unis
            'GB' => 'GBP', // Royaume-Uni
            'FR' => 'EUR', // France
            'DE' => 'EUR', // Allemagne
            'ES' => 'EUR', // Espagne
            'IT' => 'EUR', // Italie
            'CH' => 'CHF', // Suisse
            'CA' => 'CAD', // Canada
            'JP' => 'JPY', // Japon
            'CN' => 'CNY', // Chine
            'IN' => 'INR', // Inde
            'BR' => 'BRL', // Brésil
            'MX' => 'MXN', // Mexique
            'AU' => 'AUD', // Australie
        ];

        return $mapping[$countryCode] ?? null;
    }

    /**
     * Détecte automatiquement et configure la devise pour une entreprise à son premier accès
     */
    public function setupCurrencyForCompany($company): void
    {
        // Si la devise est déjà configurée, ne rien faire
        if ($company->currency && $company->currency !== 'XOF') {
            return;
        }

        $location = $this->getLocationByIp();

        if ($location['currency']) {
            $company->update([
                'currency' => $location['currency'],
                'country_code' => $location['country_code'],
            ]);
        }
    }
}
