<?php

namespace App\Services\AI;

interface AiExtractorInterface
{
    /**
     * Extrait les données structurées d'une facture à partir du texte brut
     *
     * @param string $text Texte extrait du document
     * @param string|null $mimeType Type MIME du fichier original
     * @return array Données structurées de la facture
     */
    public function extractInvoiceData(string $text, ?string $mimeType = null): array;

    /**
     * Extrait les données d'une facture à partir d'une image (base64)
     *
     * @param string $base64Image Image encodée en base64
     * @param string $mimeType Type MIME de l'image
     * @return array Données structurées de la facture
     */
    public function extractFromImage(string $base64Image, string $mimeType): array;

    /**
     * Retourne le nom du provider
     */
    public function getProviderName(): string;
}
