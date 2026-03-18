<?php

namespace App\Console\Commands\Pharmacie;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class NettoyerExports extends Command
{
    protected $signature = 'pharmacie:nettoyer-exports 
                            {--days=30 : Nombre de jours à conserver}
                            {--dry-run : Simuler sans supprimer}';

    protected $description = 'Supprimer les fichiers exports anciens (commandes et réceptions)';

    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $limit = now()->subDays($days)->timestamp;

        $this->info("🔄 Nettoyage des exports de plus de {$days} jours...");
        
        if ($dryRun) {
            $this->warn('⚠️  MODE SIMULATION (aucun fichier ne sera supprimé)');
        }

        $paths = [
            'exports/commandes',
            'exports/receptions',
        ];

        $totalDeleted = 0;
        $totalSize = 0;

        foreach ($paths as $path) {
            if (!Storage::exists($path)) {
                $this->line("📁 Création du dossier: {$path}");
                Storage::makeDirectory($path);
                continue;
            }

            $files = Storage::files($path);
            $deletedInPath = 0;
            
            foreach ($files as $file) {
                $lastModified = Storage::lastModified($file);
                
                if ($lastModified < $limit) {
                    $size = Storage::size($file);
                    $totalSize += $size;
                    
                    if (!$dryRun) {
                        Storage::delete($file);
                    }
                    
                    $deletedInPath++;
                    $totalDeleted++;
                    
                    $this->line("  🗑️  " . basename($file) . " (" . $this->formatBytes($size) . ")");
                }
            }
            
            if ($deletedInPath > 0) {
                $this->info("✅ {$deletedInPath} fichier(s) dans {$path}");
            }
        }

        $this->newLine();
        if ($totalDeleted > 0) {
            $message = $dryRun ? 'SERAIENT supprimés' : 'supprimés';
            $this->info("✅ {$totalDeleted} fichier(s) {$message} (Total: " . $this->formatBytes($totalSize) . ")");
        } else {
            $this->info("✅ Aucun fichier à supprimer");
        }

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}