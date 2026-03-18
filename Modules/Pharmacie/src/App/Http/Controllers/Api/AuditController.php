<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Models\PharmacieAudit;
use Carbon\Carbon;

class AuditController extends Controller
{
    /**
     * Historique global
     */
    public function index(Request $request): JsonResponse
    {
        $query = PharmacieAudit::with('user')
            ->orderBy('created_at', 'desc');

        // Filtrer par type d'entité
        if ($request->has('type')) {
            $query->where('auditable_type', 'like', '%' . $request->type . '%');
        }

        // Filtrer par utilisateur
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtrer par événement
        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        // Filtrer par date
        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('created_at', [
                $request->date_debut,
                $request->date_fin
            ]);
        }

        $audits = $query->paginate(50);

        return response()->json([
            'success' => true,
            'message' => 'Historique des actions',
            'data' => $audits
        ], 200);
    }

    /**
     * Historique d'une entité spécifique
     */
    public function show(string $type, int $id): JsonResponse
    {
        $className = 'Modules\\Pharmacie\\App\\Models\\' . ucfirst($type);

        if (!class_exists($className)) {
            return response()->json([
                'success' => false,
                'message' => 'Type d\'entité invalide',
                'data' => null
            ], 404);
        }

        $audits = PharmacieAudit::where('auditable_type', $className)
            ->where('auditable_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Historique de l\'entité',
            'data' => $audits
        ], 200);
    }

    /**
     * Statistiques d'audit
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_actions' => PharmacieAudit::count(),
            'actions_aujourd_hui' => PharmacieAudit::whereDate('created_at', today())->count(),
            'actions_par_type' => PharmacieAudit::selectRaw('
                    SUBSTRING_INDEX(auditable_type, "\\\\", -1) as type,
                    COUNT(*) as count
                ')
                ->groupBy('auditable_type')
                ->get()
                ->pluck('count', 'type'),
            'actions_par_event' => PharmacieAudit::selectRaw('event, COUNT(*) as count')
                ->groupBy('event')
                ->get()
                ->pluck('count', 'event'),
            'utilisateurs_actifs' => PharmacieAudit::distinct('user_id')
                ->whereNotNull('user_id')
                ->count('user_id'),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Statistiques d\'audit',
            'data' => $stats
        ], 200);
    }

    /**
     * Actions récentes
     */
    public function recent(int $limit = 20): JsonResponse
    {
        $audits = PharmacieAudit::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'user' => $audit->user ? $audit->user->name : 'Système',
                    'action' => $this->translateEvent($audit->event),
                    'entite' => class_basename($audit->auditable_type),
                    'entite_id' => $audit->auditable_id,
                    'date' => $audit->created_at->diffForHumans(),
                    'date_complete' => $audit->created_at->format('d/m/Y H:i:s'),
                    'ip' => $audit->ip_address,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Actions récentes',
            'data' => $audits
        ], 200);
    }

    /**
     * Export audit en CSV
     */
    public function export(Request $request)
    {
        $query = PharmacieAudit::with('user')->orderBy('created_at', 'desc');

        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('created_at', [
                $request->date_debut,
                $request->date_fin
            ]);
        }

        $audits = $query->get();

        $filename = 'audit_pharmacie_' . now()->format('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'r+');

        // Headers CSV
        fputcsv($handle, [
            'ID',
            'Utilisateur',
            'Action',
            'Entité',
            'Entité ID',
            'Date',
            'IP',
            'Anciennes valeurs',
            'Nouvelles valeurs'
        ]);

        // Données
        foreach ($audits as $audit) {
            fputcsv($handle, [
                $audit->id,
                $audit->user ? $audit->user->name : 'Système',
                $this->translateEvent($audit->event),
                class_basename($audit->auditable_type),
                $audit->auditable_id,
                $audit->created_at->format('d/m/Y H:i:s'),
                $audit->ip_address,
                json_encode($audit->old_values),
                json_encode($audit->new_values),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Traduction des événements
     */
    private function translateEvent(string $event): string
    {
        return match ($event) {
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            default => $event,
        };
    }
}