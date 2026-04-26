<?php

namespace App\Filament\Superadmin\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SystemLogs extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-bug-ant';
    protected static ?string $navigationLabel = 'Logs système';
    protected static ?string $title           = 'Logs système';
    protected static ?int    $navigationSort  = 10;

    protected static string $view = 'filament.superadmin.pages.system-logs';

    /** Filtre actif : all | error | warning | info | debug */
    public string $levelFilter = 'error';

    /** Recherche libre */
    public string $search = '';

    /** Nombre d'entrées à afficher */
    public int $limit = 100;

    /** Polling auto (secondes) */
    public bool $autoRefresh = false;

    /**
     * Lit et parse les N derniers Ko du fichier de log Laravel.
     */
    public function getParsedLogs(): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            return [];
        }

        // Lire les 500 Ko de la fin du fichier pour éviter les problèmes de mémoire
        $maxBytes = 512 * 1024;
        $size     = filesize($logPath);
        $offset   = max(0, $size - $maxBytes);

        $fp      = fopen($logPath, 'rb');
        fseek($fp, $offset);
        $content = fread($fp, $maxBytes);
        fclose($fp);

        // Si on n'est pas au début du fichier, on ignore la première ligne partielle
        if ($offset > 0) {
            $content = substr($content, strpos($content, "\n") + 1);
        }

        // Regex pour parser les entrées Laravel : [2026-04-13 12:34:56] production.ERROR: ...
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\]\s+(\w+)\.(\w+):\s+(.*?)(?=^\[|\z)/ms';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $entries = [];
        foreach ($matches as $m) {
            $datetime  = $m[1];
            $env       = $m[2];
            $level     = strtolower($m[3]);
            $rawBody   = trim($m[4]);

            // Séparer le message du contexte JSON et de la stack trace
            $message   = '';
            $context   = [];
            $stack     = '';
            $url       = null;
            $userId    = null;

            // Extraire le JSON de contexte s'il y en a
            $jsonStart = false;
            foreach ([' {"', ' {[', ' {'] as $needle) {
                $pos = strpos($rawBody, $needle);
                if ($pos !== false) { $jsonStart = $pos; break; }
            }

            if ($jsonStart !== false) {
                $message = trim(substr($rawBody, 0, $jsonStart));
                $jsonStr = substr($rawBody, $jsonStart + 1);
                // Couper avant la stack trace
                foreach (["\n[stacktrace]", "\n#0 "] as $marker) {
                    $cut = strpos($jsonStr, $marker);
                    if ($cut !== false) { $jsonStr = substr($jsonStr, 0, $cut); break; }
                }
                $jsonStr = trim($jsonStr);
                $decoded = json_decode($jsonStr, true);
                if (is_array($decoded)) {
                    $context = $decoded;
                    $url     = $decoded['url'] ?? ($decoded['request']['url'] ?? null);
                    $userId  = $decoded['userId'] ?? ($decoded['user_id'] ?? null);
                } else {
                    // JSON invalide : afficher brut dans le contexte
                    $context = ['_raw' => mb_substr($jsonStr, 0, 500)];
                }
            } else {
                $message = $rawBody;
            }

            // Extraire la stack trace
            $stackStart = strpos($rawBody, "\n[stacktrace]");
            if ($stackStart !== false) {
                $stackRaw = trim(substr($rawBody, $stackStart + strlen("\n[stacktrace]")));
                // Nettoyer "#0 /path..." en lignes lisibles
                $stack = $stackRaw;
            }

            // Nettoyer le message des lignes de stack trace inline
            $message = trim(preg_replace('/\n\[stacktrace\].*/s', '', $message));
            $message = trim(preg_replace('/\{.*\}$/s', '', $message));

            // Simplifier les chemins dans le message
            $message = preg_replace('|/.+/vendor/|', 'vendor/', $message);
            $message = preg_replace('|/.+/app/|', 'app/', $message);

            $entries[] = [
                'datetime'  => $datetime,
                'env'       => $env,
                'level'     => $level,
                'message'   => $message ?: '(aucun message)',
                'context'   => $context,
                'stack'     => $stack,
                'url'       => $url,
                'user_id'   => $userId,
                'id'        => md5($datetime . $message),
            ];
        }

        // Ordre chronologique inversé (plus récent en premier)
        $entries = array_reverse($entries);

        // Filtre par niveau
        if ($this->levelFilter !== 'all') {
            $entries = array_filter($entries, fn ($e) => $e['level'] === $this->levelFilter);
        }

        // Filtre par recherche
        if ($this->search !== '') {
            $q       = strtolower($this->search);
            $entries = array_filter($entries, function ($e) use ($q) {
                return str_contains(strtolower($e['message']), $q)
                    || str_contains(strtolower($e['url'] ?? ''), $q);
            });
        }

        return array_values(array_slice($entries, 0, $this->limit));
    }

    public function getLogStats(): array
    {
        $all = $this->getAllLevelCounts();
        return $all;
    }

    private function getAllLevelCounts(): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            return ['error' => 0, 'warning' => 0, 'info' => 0, 'debug' => 0];
        }

        $maxBytes = 512 * 1024;
        $size     = filesize($logPath);
        $offset   = max(0, $size - $maxBytes);

        $fp      = fopen($logPath, 'rb');
        fseek($fp, $offset);
        $content = fread($fp, $maxBytes);
        fclose($fp);

        return [
            'error'   => substr_count(strtolower($content), '].error:'),
            'warning' => substr_count(strtolower($content), '].warning:'),
            'info'    => substr_count(strtolower($content), '].info:'),
            'debug'   => substr_count(strtolower($content), '].debug:'),
        ];
    }

    public function getLogFileInfo(): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            return ['exists' => false, 'size' => 0, 'modified' => null];
        }
        return [
            'exists'   => true,
            'size'     => filesize($logPath),
            'modified' => filemtime($logPath),
        ];
    }

    public function clearLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            file_put_contents($logPath, '');
        }

        Notification::make()
            ->title('Logs effacés')
            ->body('Le fichier de log a été vidé.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear_logs')
                ->label('Vider les logs')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Vider le fichier de logs ?')
                ->modalDescription('Cette action est irréversible. Tous les logs seront supprimés.')
                ->modalSubmitActionLabel('Oui, vider')
                ->action(fn () => $this->clearLogs()),

            Action::make('refresh')
                ->label('Actualiser')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => null), // Livewire re-render
        ];
    }
}
