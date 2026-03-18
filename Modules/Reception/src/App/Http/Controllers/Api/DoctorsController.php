<?php

namespace Modules\Reception\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Users\App\Models\User;

class DoctorsController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = User::query()
            ->whereNotNull('agent_id')
            ->whereHas('roles', function ($r) {
                $r->where('label', 'medecins'); // ✅ ICI
            })
            ->with([
                'agent:id,matricule,statut,personne_id',
                'agent.personne:id,nom,prenom',
            ])
            ->orderByDesc('id');

        if ($q !== '') {
            $like = '%' . preg_replace('/\s+/', '%', $q) . '%';

            $query->where(function ($sub) use ($like) {
                $sub->whereHas('agent', function ($a) use ($like) {
                    $a->where('matricule', 'like', $like)
                      ->orWhereHas('personne', function ($p) use ($like) {
                          $p->where('nom', 'like', $like)
                            ->orWhere('prenom', 'like', $like);
                      });
                });
            });
        }

        $users = $query->get();

        $data = $users->map(function ($u) {
            $agent = $u->agent;
            $p = $agent?->personne;

            $fullName = trim(($p?->prenom ?? '') . ' ' . ($p?->nom ?? ''));
            $display = $fullName !== ''
                ? ('Dr ' . $fullName)
                : ('Dr ' . ($agent?->matricule ?? ('Agent #' . ($agent?->id ?? 'N/A'))));

            return [
                'agent_id' => $agent?->id,
                'matricule' => $agent?->matricule,
                'statut' => $agent?->statut,
                'display' => $display,
            ];
        })->values();

        return response()->json([
            'message' => 'Médecins',
            'data' => $data,
        ]);
    }
}
