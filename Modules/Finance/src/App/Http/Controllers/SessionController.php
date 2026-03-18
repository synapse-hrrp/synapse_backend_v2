<?php

namespace Modules\Finance\App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\App\Models\FinanceSession;
use Modules\Finance\App\Models\FinanceAudit;

class SessionController extends Controller
{
    public function current(Request $request)
    {
        $poste = (string) $request->header('X-Workstation');
        abort_if(!$poste, 422, 'Header X-Workstation obligatoire');

        $session = FinanceSession::sessionOuverte((int)$request->user()->id, $poste);

        return response()->json(['session' => $session]);
    }

    public function open(Request $request)
    {
        $poste = (string) $request->header('X-Workstation');
        abort_if(!$poste, 422, 'Header X-Workstation obligatoire');

        // ✅ module_scope optionnel
        // - null => session globale (réservée au caissier général)
        $data = $request->validate([
            'module_scope' => 'nullable|string|in:reception,pharmacie',
        ]);

        $user   = $request->user();
        $userId = (int) $user->id;

        // ✅ si pas de module_scope => il faut être "caissier général"
        if (empty($data['module_scope'])) {
            abort_if(
                !$user->can('caisse.report.global'),
                403,
                "Session globale interdite (permission requise: caisse.report.global)."
            );
        }

        return DB::transaction(function () use ($userId, $poste, $data) {

            if (FinanceSession::sessionOuverte($userId, $poste)) {
                abort(409, 'Une session est déjà ouverte sur ce poste.');
            }

            $session = FinanceSession::create([
                'user_id'       => $userId,
                'poste'         => $poste,
                'module_scope'  => $data['module_scope'] ?? null, // ✅ null => globale
                'ouverte_le'    => now(),
                'cle_ouverture' => FinanceSession::cleOuverture($userId, $poste),
                'nb_paiements'  => 0,
                'total_montant' => 0,
            ]);

            FinanceAudit::log(
                'SESSION_OUVERTE',
                $userId,
                $session->id,
                null,
                null,
                null,
                [
                    'poste' => $poste,
                    'module_scope' => $session->module_scope, // null si global
                ]
            );

            return response()->json(['session' => $session], 201);
        });
    }

    public function close(Request $request)
    {
        $poste = (string) $request->header('X-Workstation');
        abort_if(!$poste, 422, 'Header X-Workstation obligatoire');

        $userId = (int) $request->user()->id;

        return DB::transaction(function () use ($userId, $poste) {

            $session = FinanceSession::sessionOuverte($userId, $poste);
            abort_if(!$session, 404, 'Aucune session ouverte.');

            $session->update([
                'fermee_le'     => now(),
                'cle_ouverture' => null,
            ]);

            FinanceAudit::log(
                'SESSION_FERMEE',
                $userId,
                $session->id,
                null,
                null,
                null,
                [
                    'poste' => $poste,
                    'module_scope' => $session->module_scope,
                ]
            );

            return response()->json(['session' => $session->fresh()]);
        });
    }
}
