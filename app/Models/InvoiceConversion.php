<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'original_filename',
        'original_mime_type',
        'original_size',
        'extracted_data',
        'ai_provider',
        'tier',
        'status',
        'error_message',
        'output_pdf_path',
        'output_xml_path',
        'ip_address',
        'session_id',
        'processing_time_ms',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'original_size' => 'integer',
        'processing_time_ms' => 'integer',
    ];

    /**
     * Statuts
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DOWNLOADED = 'downloaded';

    /**
     * Relations
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CompanySetting::class, 'company_id');
    }

    /**
     * Scopes
     */
    public function scopeForIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_DOWNLOADED]);
    }

    /**
     * Vérifie si l'IP a atteint la limite mensuelle gratuite
     */
    public static function hasReachedFreeLimit(string $ip, int $limit = 5): bool
    {
        return static::forIp($ip)
            ->thisMonth()
            ->completed()
            ->count() >= $limit;
    }

    /**
     * Nombre de conversions restantes pour une IP ce mois
     */
    public static function remainingFreeConversions(string $ip, int $limit = 5): int
    {
        $used = static::forIp($ip)
            ->thisMonth()
            ->completed()
            ->count();

        return max(0, $limit - $used);
    }

    /**
     * Marquer comme en cours de traitement
     */
    public function markProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    /**
     * Marquer comme terminé
     */
    public function markCompleted(array $data, ?string $pdfPath = null, ?string $xmlPath = null, ?int $timeMs = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'extracted_data' => $data,
            'output_pdf_path' => $pdfPath,
            'output_xml_path' => $xmlPath,
            'processing_time_ms' => $timeMs,
        ]);
    }

    /**
     * Marquer comme échoué
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
        ]);
    }

    /**
     * Taille formatée
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->original_size;

        if ($bytes < 1024) return $bytes . ' o';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' Ko';

        return round($bytes / 1048576, 1) . ' Mo';
    }

    /**
     * Label du statut
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En cours',
            self::STATUS_COMPLETED => 'Terminé',
            self::STATUS_FAILED => 'Échoué',
            self::STATUS_DOWNLOADED => 'Téléchargé',
            default => $this->status,
        };
    }

    /**
     * Couleur du badge statut
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_PROCESSING => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_DOWNLOADED => 'success',
            default => 'gray',
        };
    }
}
