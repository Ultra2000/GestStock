<?php

namespace App\Jobs;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeneratePdfReport implements ShouldQueue
{
    use Queueable;

    /**
     * Le nombre de tentatives maximum
     */
    public int $tries = 3;

    /**
     * Le timeout en secondes
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $reportType,
        public string $view,
        public array $data,
        public int $userId,
        public int $companyId,
        public ?string $filename = null
    ) {
        $this->onQueue('reports');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Générer le nom de fichier si non fourni
            $filename = $this->filename ?? $this->reportType . '_' . now()->format('Y-m-d_His') . '.pdf';
            
            // Générer le PDF
            $pdf = Pdf::loadView($this->view, $this->data);
            $pdf->setPaper('A4', 'portrait');
            
            // Sauvegarder le PDF
            $path = "reports/{$this->companyId}/{$filename}";
            Storage::disk('local')->put($path, $pdf->output());
            
            // Notifier l'utilisateur (si notifications activées)
            $user = User::find($this->userId);
            if ($user) {
                // Vous pouvez implémenter une notification ici
                // $user->notify(new ReportReadyNotification($path, $this->reportType));
                Log::info("PDF Report generated: {$path} for user {$user->email}");
            }
            
        } catch (\Exception $e) {
            Log::error("Failed to generate PDF report: " . $e->getMessage(), [
                'reportType' => $this->reportType,
                'userId' => $this->userId,
                'companyId' => $this->companyId,
            ]);
            
            throw $e; // Re-throw pour que le job puisse être réessayé
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("PDF Report job failed permanently", [
            'reportType' => $this->reportType,
            'userId' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
