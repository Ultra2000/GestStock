<?php

namespace App\Console\Commands;

use App\Services\PdfSignatureService;
use Illuminate\Console\Command;

class GenerateSignatureCertificate extends Command
{
    protected $signature   = 'signature:generate-cert
                                {--name= : Nom affiché dans la signature}
                                {--country=FR : Code pays (2 lettres)}
                                {--email= : Email du signataire}
                                {--force : Remplacer le certificat existant}';

    protected $description = 'Génère un certificat auto-signé pour la signature numérique des PDFs';

    public function handle(): void
    {
        $service = new PdfSignatureService();

        if ($service->certificateExists() && !$this->option('force')) {
            $info = $service->getCertificateInfo();
            $this->warn('Un certificat existe déjà :');
            $this->line("  Sujet   : " . ($info['subject'] ?? '?'));
            $this->line("  Valide jusqu'au : " . ($info['valid_to'] ?? '?'));
            $this->line('Utilisez --force pour le remplacer.');
            return;
        }

        $name    = $this->option('name') ?: config('app.name', 'FRECORP ERP');
        $country = $this->option('country') ?: 'FR';
        $email   = $this->option('email') ?: '';

        $this->info("Génération du certificat auto-signé pour « {$name} »...");

        $ok = $service->generateSelfSignedCertificate([
            'name'    => $name,
            'country' => $country,
            'email'   => $email,
        ]);

        if ($ok) {
            $info = $service->getCertificateInfo();
            $this->info('Certificat généré avec succès.');
            $this->line("  Sujet           : " . ($info['subject'] ?? '?'));
            $this->line("  Valide jusqu'au : " . ($info['valid_to'] ?? '?'));
            $this->line("  Type            : " . ($info['self_signed'] ? 'Auto-signé' : 'Signé par CA'));
        } else {
            $this->error('Échec de la génération du certificat. Vérifiez que OpenSSL est disponible.');
        }
    }
}
