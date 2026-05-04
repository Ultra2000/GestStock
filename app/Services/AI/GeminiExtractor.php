<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiExtractor implements AiExtractorInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.ai.gemini.api_key', '');
        $this->model = config('services.ai.gemini.model', 'gemini-2.5-flash');
    }

    public function getProviderName(): string
    {
        return 'Gemini Flash';
    }

    public function extractInvoiceData(string $text, ?string $mimeType = null): array
    {
        $prompt = $this->buildPrompt($text);

        $payload = [
            'contents' => [[
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.1],
        ];

        $response = $this->callWithRetry($payload, 'Gemini API error');

        $content = $response->json('candidates.0.content.parts.0.text');

        if (!$content) {
            throw new \Exception('Réponse vide de Gemini');
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Tenter d'extraire le JSON du texte
            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                $data = json_decode($matches[0], true);
            }
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Réponse Gemini non parseable en JSON');
            }
        }

        return $this->normalizeData($data);
    }

    public function extractFromPdf(string $base64Pdf): ?array
    {
        return null; // Gemini ne supporte pas les PDFs natifs via cette API
    }

    public function extractFromImage(string $base64Image, string $mimeType): array
    {
        $prompt = $this->buildPrompt('');

        $payload = [
            'contents' => [[
                'parts' => [
                    ['inlineData' => ['mimeType' => $mimeType, 'data' => $base64Image]],
                    ['text' => $prompt],
                ],
            ]],
            'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.1],
        ];

        $response = $this->callWithRetry($payload, 'Gemini Vision API error');

        $content = $response->json('candidates.0.content.parts.0.text');

        if (!$content) {
            throw new \Exception('Réponse vide de Gemini Vision');
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                $data = json_decode($matches[0], true);
            }
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Réponse Gemini Vision non parseable');
            }
        }

        return $this->normalizeData($data);
    }

    private function callWithRetry(array $payload, string $logContext): \Illuminate\Http\Client\Response
    {
        $url      = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
        $attempts = 3;

        for ($i = 1; $i <= $attempts; $i++) {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(60)
                ->post($url, $payload);

            if ($response->successful()) {
                return $response;
            }

            $status   = $response->status();
            $errorMsg = $response->json('error.message') ?? $response->json('message') ?? 'Unknown error';

            if (in_array($status, [429, 503]) && $i < $attempts) {
                // Lire le délai conseillé par l'API ("Please retry in 15.6s")
                $retryAfter = 0;
                if (preg_match('/retry in ([\d.]+)s/i', $errorMsg, $m)) {
                    $retryAfter = (int) ceil((float) $m[1]);
                }
                $delay = max($retryAfter, $status === 429 ? 20 : 5) * $i;
                Log::warning($logContext . ' (retry ' . $i . '/' . $attempts . ')', [
                    'status' => $status, 'delay' => $delay . 's', 'error' => mb_substr($errorMsg, 0, 150),
                ]);
                sleep($delay);
                continue;
            }

            Log::error($logContext, ['status' => $status, 'error' => mb_substr($errorMsg, 0, 300), 'model' => $this->model]);
            throw new \Exception("Erreur Gemini API [{$status}]: {$errorMsg}");
        }

        throw new \Exception("Gemini API indisponible après {$attempts} tentatives");
    }

    protected function buildPrompt(string $text): string
    {
        $instruction = <<<'PROMPT'
Tu es un expert en extraction de données de factures. Analyse le document suivant et extrais les informations dans un JSON strictement conforme à cette structure :

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

Règles:
- Tous les montants sont des nombres (pas de chaînes)
- Les dates sont au format YYYY-MM-DD
- Si une information est absente, mets null
- N'invente JAMAIS de données, mets null si tu ne trouves pas
- Les taux de TVA français courants: 20%, 10%, 5.5%, 2.1%, 0%
- Vérifie que total_ht + total_vat = total_ttc
- Vérifie que la somme des lignes correspond aux totaux
PROMPT;

        if ($text) {
            return $instruction . "\n\nVoici le contenu du document :\n\n" . $text;
        }

        return $instruction . "\n\nAnalyse l'image de facture ci-jointe et extrais les données.";
    }

    protected function normalizeData(array $data): array
    {
        // S'assurer que les clés principales existent
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
