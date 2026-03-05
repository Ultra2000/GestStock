<?php

namespace App\Http\Controllers;

use App\Models\InvoiceConversion;
use App\Services\InvoiceConverterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

class InvoiceConverterController extends Controller
{
    /**
     * Page publique du convertisseur
     */
    public function index()
    {
        $ip = request()->ip();
        $remaining = InvoiceConversion::remainingFreeConversions($ip);

        return view('invoice-converter.index', [
            'remaining' => $remaining,
            'limit' => InvoiceConverterService::FREE_MONTHLY_LIMIT,
        ]);
    }

    /**
     * Traiter le fichier uploadé (AJAX)
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ], [
            'file.required' => 'Veuillez sélectionner un fichier.',
            'file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        $ip = $request->ip();

        // Rate limiting: 10 requêtes par minute par IP
        $rateLimitKey = 'invoice-converter:' . $ip;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            return response()->json([
                'success' => false,
                'message' => 'Trop de requêtes. Veuillez patienter une minute.',
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        // Vérifier la limite mensuelle
        if (InvoiceConversion::hasReachedFreeLimit($ip)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez atteint la limite de ' . InvoiceConverterService::FREE_MONTHLY_LIMIT . ' conversions gratuites ce mois-ci. Créez un compte FRECORP pour des conversions illimitées.',
                'upgrade' => true,
            ], 403);
        }

        try {
            $file = $request->file('file');
            $mime = $file->getMimeType();

            // Vérifier le format
            if (!in_array($mime, InvoiceConverterService::SUPPORTED_MIMES)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format non supporté. Acceptés : PDF, JPEG, PNG, WebP, Excel, CSV.',
                ], 422);
            }

            // Créer l'enregistrement
            $conversion = InvoiceConversion::create([
                'user_id' => auth()->id(),
                'original_filename' => $file->getClientOriginalName(),
                'original_mime_type' => $mime,
                'original_size' => $file->getSize(),
                'ai_provider' => 'gemini',
                'tier' => InvoiceConverterService::TIER_FREE,
                'status' => InvoiceConversion::STATUS_PROCESSING,
                'ip_address' => $ip,
                'session_id' => session()->getId(),
            ]);

            // Extraction
            $service = new InvoiceConverterService();
            $startTime = microtime(true);
            $data = $service->processFile($file, InvoiceConverterService::TIER_FREE);
            $timeMs = (int)((microtime(true) - $startTime) * 1000);

            $conversion->markCompleted($data, null, null, $timeMs);

            return response()->json([
                'success' => true,
                'conversion_id' => $conversion->id,
                'data' => $data,
                'ai_provider' => 'Gemini Flash',
                'processing_time_ms' => $timeMs,
                'remaining' => InvoiceConversion::remainingFreeConversions($ip),
            ]);

        } catch (\Throwable $e) {
            Log::error('InvoiceConverter public: extraction failed', [
                'error' => $e->getMessage(),
                'ip' => $ip,
            ]);

            if (isset($conversion)) {
                $conversion->markFailed($e->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'extraction : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Générer le Factur-X (AJAX)
     */
    public function generate(Request $request)
    {
        $request->validate([
            'conversion_id' => 'required|integer',
            'data' => 'required|array',
        ]);

        $ip = $request->ip();

        $conversion = InvoiceConversion::where('id', $request->conversion_id)
            ->where(function ($q) use ($ip) {
                $q->where('ip_address', $ip)
                  ->orWhere('session_id', session()->getId());
            })
            ->first();

        if (!$conversion) {
            return response()->json([
                'success' => false,
                'message' => 'Conversion introuvable.',
            ], 404);
        }

        try {
            $service = new InvoiceConverterService();
            $data = $request->data;

            $options = ['show_watermark' => true]; // Toujours watermark pour public

            $pdfPath = $service->generateFacturXPdf($data, $options);
            $xmlPath = str_replace('.pdf', '.xml', $pdfPath);

            $conversion->update([
                'output_pdf_path' => $pdfPath,
                'output_xml_path' => $xmlPath,
                'extracted_data' => $data,
            ]);

            return response()->json([
                'success' => true,
                'download_pdf_url' => route('invoice-converter.download', ['id' => $conversion->id, 'type' => 'pdf']),
                'download_xml_url' => route('invoice-converter.download', ['id' => $conversion->id, 'type' => 'xml']),
            ]);

        } catch (\Throwable $e) {
            Log::error('InvoiceConverter public: generation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Télécharger le fichier généré
     */
    public function download(Request $request, int $id)
    {
        $type = $request->query('type', 'pdf');
        $ip = $request->ip();

        $conversion = InvoiceConversion::where('id', $id)
            ->where(function ($q) use ($ip) {
                $q->where('ip_address', $ip)
                  ->orWhere('session_id', session()->getId())
                  ->orWhere('user_id', auth()->id());
            })
            ->first();

        if (!$conversion) {
            abort(404, 'Conversion introuvable.');
        }

        $path = $type === 'xml' ? $conversion->output_xml_path : $conversion->output_pdf_path;

        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404, 'Fichier introuvable ou expiré.');
        }

        $conversion->update(['status' => InvoiceConversion::STATUS_DOWNLOADED]);

        $ext = $type === 'xml' ? '.xml' : '.pdf';
        $filename = 'facturx_' . pathinfo($conversion->original_filename, PATHINFO_FILENAME) . $ext;

        return Storage::disk('local')->download($path, $filename);
    }
}
