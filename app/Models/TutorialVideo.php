<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutorialVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'section',
        'video_url',
        'video_type',
        'thumbnail_url',
        'duration_seconds',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * All available guide sections.
     */
    public const SECTIONS = [
        'getting-started' => 'Premiers pas',
        'dashboard'       => 'Tableau de bord',
        'sales'           => 'Module Ventes',
        'stock'           => 'Stocks & Achats',
        'pos'             => 'Caisse (POS)',
        'accounting'      => 'Comptabilite',
        'banking'         => 'Banque',
        'hr'              => 'Ressources Humaines',
        'admin'           => 'Administration',
        'einvoicing'      => 'Facturation electronique',
        'appendix'        => 'Annexes & Glossaire',
    ];

    /**
     * Extract the embed URL from a video URL.
     */
    public function getEmbedUrlAttribute(): ?string
    {
        $url = $this->video_url;

        if ($this->video_type === 'youtube') {
            // Support: youtube.com/watch?v=ID, youtu.be/ID, youtube.com/embed/ID
            if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
                return 'https://www.youtube.com/embed/' . $matches[1];
            }
        }

        if ($this->video_type === 'vimeo') {
            // Support: vimeo.com/ID, player.vimeo.com/video/ID
            if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
                return 'https://player.vimeo.com/video/' . $matches[1];
            }
        }

        // Direct URL — return as-is
        return $url;
    }

    /**
     * Get formatted duration (e.g., "5:32").
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration_seconds) {
            return null;
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Scope to active videos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to a specific section.
     */
    public function scopeForSection($query, string $section)
    {
        return $query->where('section', $section);
    }

    /**
     * Scope ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }
}
