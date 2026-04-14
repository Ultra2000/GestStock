<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeExtractor implements AiExtractorInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.ai.claude.api_key', '');
        $this->model = config('services.ai.claude.model', 'claude-sonnet-4-20250514');
    }

    public function getProviderName(): string
    {
        return 'Claude Sonnet';
    }

    public function extractInvoiceData(string $text, ?string $mimeType = null): array
    {
        $prompt = $this->buildPrompt();

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 4096,
            'temperature' => 0.1,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt . "\n\nVoici le contenu du document :\n\n" . $text,
                ],
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Claude API error', ['status' => $response->status(), 'hint' => substr($response->body(), 0, 200)]);
            throw new \Exception('Erreur Claude API: ' . ($response->json('error.message') ?? $response->body()));
        }

        $content = $response->json('content.0.text');

        if (!$content) {
            throw new \Exception('Réponse vide de Claude');
        }

        return $this->parseJsonResponse($content);
    }

    public function extractFromImage(string $base64Image, string $mimeType): array
    {
        $prompt = $this->buildPrompt();

        // Claude accepte: image/jpeg, image/png, image/gif, image/webp
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedTypes)) {
            $mimeType = 'image/jpeg'; // Fallback
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 4096,
            'temperature' => 0.1,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mimeType,
                                'data' => $base64Image,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt . "\n\nAnalyse l'image de facture ci-dessus et extrais toutes les données.",
                        ],
                    ],
                ],
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Claude Vision API error', ['status' => $response->status(), 'hint' => substr($response->body(), 0, 200)]);
            throw new \Exception('Erreur Claude Vision: ' . ($response->json('error.message') ?? $response->body()));
        }

        $content = $response->json('content.0.text');

        if (!$content) {
            throw new \Exception('Réponse vide de Claude Vision');
        }

        return $this->parseJsonResponse($content);
    }

    protected function buildPrompt(): string
    {
        return <<<'PROMPT'
Tu es un expert en extraction de données de factures françaises. Extrais les informations et retourne UNIQUEMENT un JSON valide (sans markdown, sans ```json```, sans texte autour) conforme à cette structure :

{
  "seller": {
    "name": "Nom de l'entreprise vendeuse",
    "address": "Adresse complète",
    "zip_code": "Code postal",
    "city": "Ville",
    "country_code": "FR",
    "siret": "SIRET (14 chiffres) ou null",
    "vat_number": "Numéro TVA intracommunautaire ou null",
    "phone": "Téléphone ou null",
    "email": "Email ou null"
  },
  "buyer": {
    "name": "Nom du client/acheteur",
    "address": "Adresse ou null",
    "zip_code": "Code postal ou null",
    "city": "Ville ou null",
    "country_code": "FR",
    "siret": "SIRET ou null",
    "vat_number": "Numéro TVA ou null"
  },
  "invoice": {
    "number": "Numéro de facture",
    "date": "Date au format YYYY-MM-DD",
    "due_date": "Date d'échéance YYYY-MM-DD ou null",
    "currency": "EUR",
    "payment_method": "Moyen de paiement ou null",
    "notes": "Mentions ou commentaires ou null"
  },
  "lines": [
    {
      "description": "Description du produit/service",
      "quantity": 1.00,
      "unit_price_ht": 100.00,
      "vat_rate": 20.00,
      "total_ht": 100.00,
      "total_ttc": 120.00
    }
  ],
  "totals": {
    "total_ht": 100.00,
    "total_vat": 20.00,
    "total_ttc": 120.00
  },
  "vat_breakdown": [
    {
      "rate": 20.00,
      "base": 100.00,
      "amount": 20.00
    }
  ]
}

Règles :
- Tous les montants sont des nombres décimaux (pas de chaînes)
- Les dates sont au format YYYY-MM-DD
- Si une information est absente, mets null
- N'invente JAMAIS de données — null si introuvable
- TVA françaises courantes: 20%, 10%, 5.5%, 2.1%, 0%
- Vérifie: total_ht + total_vat = total_ttc
- Vérifie: somme des lignes = totaux
PROMPT;
    }

    protected function parseJsonResponse(string $content): array
    {
        // Nettoyer les éventuels blocs markdown
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Tenter d'extraire le JSON du texte
            if (preg_match('/\{[\s\S]*\}/s', $content, $matches)) {
                $data = json_decode($matches[0], true);
            }
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Claude response not valid JSON: ' . substr($content, 0, 500));
                throw new \Exception('La réponse de Claude n\'est pas un JSON valide');
            }
        }

        return $this->normalizeData($data);
    }

    protected function normalizeData(array $data): array
    {
        return [
            'seller' => $data['seller'] ?? [],
            'buyer' => $data['buyer'] ?? [],
            'invoice' => $data['invoice'] ?? [],
            'lines' => $data['lines'] ?? [],
            'totals' => [
                'total_ht' => (float) ($data['totals']['total_ht'] ?? 0),
                'total_vat' => (float) ($data['totals']['total_vat'] ?? 0),
                'total_ttc' => (float) ($data['totals']['total_ttc'] ?? 0),
            ],
            'vat_breakdown' => $data['vat_breakdown'] ?? [],
        ];
    }
}
