<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PdfSignatureService
{
    private string $certPath;
    private string $keyPath;
    private string $certPassword;

    public function __construct()
    {
        $this->certPath = storage_path('app/signature/certificate.pem');
        $this->keyPath  = storage_path('app/signature/private_key.pem');
        $this->certPassword = config('app.pdf_signature_password', '');
    }

    /**
     * Sign a PDF file in place using TCPDF + FPDI.
     * Returns true on success, false if TCPDF is not installed or signing fails.
     */
    public function signPdf(string $pdfPath, array $signerInfo = []): bool
    {
        if (!class_exists('TCPDF') || !class_exists('setasign\Fpdi\Tcpdf\Fpdi')) {
            Log::warning('PdfSignatureService: TCPDF or FPDI-TCPDF not available, skipping signature');
            return false;
        }

        if (!$this->certificateExists()) {
            Log::info('PdfSignatureService: No certificate found, skipping signature');
            return false;
        }

        try {
            $cert = file_get_contents($this->certPath);
            $key  = file_get_contents($this->keyPath);

            $fpdi = new \setasign\Fpdi\Tcpdf\Fpdi();
            $fpdi->setPageUnit('pt');

            $pageCount = $fpdi->setSourceFile($pdfPath);
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplIdx = $fpdi->importPage($i);
                $size   = $fpdi->getTemplateSize($tplIdx);
                $fpdi->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
                $fpdi->useTemplate($tplIdx);
            }

            $fpdi->setSignature(
                'file://' . $this->certPath,
                'file://' . $this->keyPath,
                $this->certPassword,
                '',
                2,
                [
                    'Name'        => $signerInfo['name'] ?? config('app.name', 'FRECORP ERP'),
                    'Location'    => $signerInfo['location'] ?? '',
                    'Reason'      => $signerInfo['reason'] ?? 'Signature numérique - Facture conforme',
                    'ContactInfo' => $signerInfo['contact'] ?? '',
                ]
            );

            $fpdi->Output($pdfPath, 'F');

            return true;
        } catch (\Throwable $e) {
            Log::error('PdfSignatureService: signing failed — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a self-signed certificate for FRECORP (stored in storage/app/signature/).
     * Run once via Artisan or superadmin action.
     */
    public function generateSelfSignedCertificate(array $info = []): bool
    {
        $dir = storage_path('app/signature');
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $dn = array_filter([
            'commonName'          => $info['name'] ?? config('app.name', 'FRECORP ERP'),
            'organizationName'    => $info['organization'] ?? config('app.name', 'FRECORP'),
            'countryName'         => $info['country'] ?? 'FR',
            'stateOrProvinceName' => $info['state'] ?? null,
            'localityName'        => $info['city'] ?? null,
            'emailAddress'        => $info['email'] ?? null,
        ], fn($v) => !empty($v));

        $privKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if (!$privKey) {
            Log::error('PdfSignatureService: openssl_pkey_new failed');
            return false;
        }

        $csr  = openssl_csr_new($dn, $privKey, ['digest_alg' => 'sha256']);
        $cert = openssl_csr_sign($csr, null, $privKey, 3650, ['digest_alg' => 'sha256']);

        openssl_x509_export($cert, $certPem);
        openssl_pkey_export($privKey, $keyPem, $this->certPassword ?: null);

        file_put_contents($this->certPath, $certPem);
        file_put_contents($this->keyPath, $keyPem);
        chmod($this->certPath, 0600);
        chmod($this->keyPath, 0600);

        Log::info('PdfSignatureService: self-signed certificate generated');

        return true;
    }

    public function certificateExists(): bool
    {
        return file_exists($this->certPath) && file_exists($this->keyPath);
    }

    public function getCertificateInfo(): ?array
    {
        if (!$this->certificateExists()) {
            return null;
        }

        $cert = openssl_x509_read(file_get_contents($this->certPath));
        if (!$cert) {
            return null;
        }

        $info = openssl_x509_parse($cert);

        return [
            'subject'  => $info['subject']['CN'] ?? 'Inconnu',
            'issuer'   => $info['issuer']['CN'] ?? 'Auto-signé',
            'valid_to' => date('Y-m-d', $info['validTo_time_t'] ?? 0),
            'self_signed' => ($info['subject'] === $info['issuer']),
        ];
    }
}
