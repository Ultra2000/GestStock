<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Company;
use App\Models\Customer;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FactPulseService
{
    protected string $apiUrl;
    protected string $email;
    protected string $password;
    protected string $clientUid;

    public function __construct()
    {
        $this->apiUrl = config('services.factpulse.api_url');
        $this->email = config('services.factpulse.email');
        $this->password = config('services.factpulse.password');
        $this->clientUid = config('services.factpulse.client_uid');
    }

    /**
     * Vérifie si l'intégration FactPulse est configurée
     */
    public function isConfigured(): bool
    {
        return !empty($this->email) && !empty($this->password) && !empty($this->clientUid);
    }

    /**
     * Transforme une vente GestStock en flux JSON FactPulse
     * et l'envoie pour traitement (conversion Factur-X + routage)
     * 
     * @param Sale $sale
     * @return array Réponse de l'API avec statut
     * @throws Exception
     */
    public function submitInvoice(Sale $sale): array
    {
        if (!$this->isConfigured()) {
            throw new Exception("L'intégration FactPulse n'est pas configurée dans les variables d'environnement.");
        }

        $sale->loadMissing(['customer', 'company', 'items.product']);
        
        $customer = $sale->customer;
        $company = $sale->company;

        if (!$company || empty($company->siret)) {
            throw new Exception("L'entreprise émettrice n'a pas de SIRET configuré.");
        }

        if (!$customer) {
            throw new Exception("Cette vente n'a pas de client associé.");
        }

        if (empty($customer->siret)) {
            throw new Exception("Le client ({$customer->name}) n'a pas de SIRET configuré, obligatoire pour le B2B.");
        }

        // 1. Headers avec les identifiants Sandbox / Prod (Selon la doc FactPulse)
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-FactPulse-Email' => $this->email,
            'X-FactPulse-Password' => $this->password,
            'X-FactPulse-Client-UID' => $this->clientUid,
        ];

        // 2. Construction des Lignes
        $lines = [];
        foreach ($sale->items as $item) {
            $productName = $item->product ? $item->product->name : 'Article ' . $item->product_id;
            
            $lines[] = [
                'description' => $productName,
                'quantity' => (float) $item->quantity,
                'unitPrice' => (float) $item->unit_price_ht,
                'vatRate' => (float) $item->vat_rate,
            ];
        }

        // 3. Construction de la Payload JSON
        $payload = [
            'invoiceData' => [
                'number' => $sale->invoice_number,
                'issueDate' => $sale->created_at->format('Y-m-d'),
                'dueDate' => $sale->due_date ? $sale->due_date->format('Y-m-d') : $sale->created_at->copy()->addDays(30)->format('Y-m-d'),
                'supplier' => [
                    'siret' => str_replace(' ', '', $company->siret),
                    'routingAddress' => trim($company->address . ', ' . $company->zip_code . ' ' . $company->city),
                ],
                'recipient' => [
                    'siret' => str_replace(' ', '', $customer->siret),
                    'routingAddress' => trim($customer->address . ', ' . $customer->zip_code . ' ' . $customer->city),
                ],
                'lines' => $lines
            ],
            'destination' => [
                'type' => 'auto' // Routage auto via l'annuaire PDP
            ]
        ];
        
        Log::info("Envoi facture via FactPulse démarré", ['invoice' => $sale->invoice_number, 'siret_client' => $customer->siret]);

        // Appel API. (On suppose l'endpoint de génération ici)
        $response = Http::withHeaders($headers)
            ->post($this->apiUrl . '/invoices/generate-and-submit', $payload);

        if ($response->failed()) {
            Log::error("Erreur FactPulse: " . $response->body());
            throw new Exception("Erreur de communication avec FactPulse: " . $response->status() . " - " . $response->body());
        }

        $result = $response->json();

        // 4. Mettre à jour la base locale avec l'ID du suivi de l'OD
        // Et on utilise le champ ppf_status ou ppf_id de GestStock pour le tracking.
        $sale->update([
            'ppf_id' => $result['id'] ?? null,
            'ppf_status' => 'submitted',
            'ppf_synced_at' => now(),
        ]);

        return $result;
    }
}
