<?php

namespace App\Console\Commands;

use App\Models\InvoiceConversion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanOldConversions extends Command
{
    protected $signature   = 'conversions:clean {--days=7 : Supprimer les conversions plus vieilles que N jours}';
    protected $description = 'Supprime les fichiers PDF/XML des anciennes conversions de factures';

    public function handle(): void
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $conversions = InvoiceConversion::withoutGlobalScopes()
            ->where('created_at', '<', $cutoff)
            ->whereNotNull('output_pdf_path')
            ->orWhere(function ($q) use ($cutoff) {
                $q->where('created_at', '<', $cutoff)->whereNotNull('output_xml_path');
            })
            ->get();

        $deleted = 0;
        foreach ($conversions as $conversion) {
            if ($conversion->output_pdf_path && Storage::disk('local')->exists($conversion->output_pdf_path)) {
                Storage::disk('local')->delete($conversion->output_pdf_path);
            }
            if ($conversion->output_xml_path && Storage::disk('local')->exists($conversion->output_xml_path)) {
                Storage::disk('local')->delete($conversion->output_xml_path);
            }
            $conversion->withoutGlobalScopes()->where('id', $conversion->id)->update([
                'output_pdf_path' => null,
                'output_xml_path' => null,
            ]);
            $deleted++;
        }

        $this->info("Nettoyage terminé : {$deleted} conversions ({$days}j+) — fichiers supprimés.");
    }
}
