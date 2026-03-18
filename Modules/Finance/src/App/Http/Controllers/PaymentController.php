<?php

namespace Modules\Finance\App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Gate;

use Modules\Finance\App\Models\FinanceSession;
use Modules\Finance\App\Models\FinancePayment;
use Modules\Finance\App\Models\FinanceAudit;
use Modules\Finance\App\Services\FinanceSourceAdapter;
use Modules\Finance\App\Services\FactureOfficielleService;

class PaymentController extends Controller
{
    public function __construct(
        private FinanceSourceAdapter $adapter,
        private FactureOfficielleService $factureOfficielleService
    ) {}

    /**
     * ✅ Helper auth (évite null selon contexte sanctum)
     */
    private function authUser(Request $request)
    {
        return $request->user() ?: auth('sanctum')->user();
    }

    public function index(Request $request)
    {
        $q = FinancePayment::query()->orderByDesc('id');

        if ($request->filled('session_id')) {
            $q->where('session_id', $request->integer('session_id'));
        }
        if ($request->filled('table_source')) {
            $q->where('table_source', (string) $request->string('table_source'));
        }
        if ($request->filled('source_id')) {
            $q->where('source_id', $request->integer('source_id'));
        }
        if ($request->filled('statut')) {
            $q->where('statut', (string) $request->string('statut'));
        }

        return response()->json(['paiements' => $q->paginate(20)]);
    }

    public function store(Request $request)
    {
        $poste = (string) $request->header('X-Workstation');
        abort_if(!$poste, 422, 'Header X-Workstation obligatoire');

        $data = $request->validate([
            'module_source' => 'required|string|in:reception,pharmacie',
            'table_source'  => 'required|string|max:80',
            'source_id'     => 'required|integer|min:1',
            'montant'       => 'required|numeric|min:0.01',
            'mode'          => 'required|string|in:cash,momo,card,virement,cheque',
            'reference'     => 'nullable|string|max:100',
        ]);

        // ✅ utilisateur connecté = encaisseur
        $user = $this->authUser($request);
        abort_if(!$user, 401, 'Utilisateur non authentifié.');
        $userId = (int) $user->id;

        // ✅ Table autorisée par module (sécurité)
        $map = [
            'reception' => 't_billing_requests',
            'pharmacie' => 'ventes',
        ];

        abort_if(
            $data['table_source'] !== $map[$data['module_source']],
            422,
            "table_source invalide pour {$data['module_source']}."
        );

        $payload = DB::transaction(function () use ($data, $userId, $poste) {

            $session = FinanceSession::sessionOuverte($userId, $poste);
            abort_if(!$session, 409, 'Session Finance non ouverte.');

            // ✅ Scope: si module_scope est défini => restriction
            if (!empty($session->module_scope)) {
                abort_if(
                    $session->module_scope !== $data['module_source'],
                    403,
                    "Session limitée au module '{$session->module_scope}'."
                );
            }

            // 1) Lock source
            $source = $this->adapter->verrouiller($data['table_source'], (int) $data['source_id']);
            abort_if(!$source, 404, 'Source introuvable.');

            // 2) Interdire si source annulée
            abort_if(
                $this->adapter->sourceEstAnnulee($data['module_source'], $source),
                409,
                'Paiement interdit: source annulée.'
            );

            // 3) Total dû
            $totalDu = (float) $this->adapter->montantDu($data['module_source'], $source);

            // 4) Total déjà payé (fiable)
            $dejaPaye = (float) FinancePayment::query()
                ->where('table_source', $data['table_source'])
                ->where('source_id', (int) $data['source_id'])
                ->where('statut', FinancePayment::STATUS_VALIDE)
                ->sum('montant');

            $reste = round(max(0, $totalDu - $dejaPaye), 2);

            abort_if($reste <= 0, 409, "Déjà soldé. total={$totalDu}, payé={$dejaPaye}.");
            abort_if((float) $data['montant'] > $reste + 0.01, 409, "Anti-surpaiement: reste à payer = {$reste}.");

            // 5) Créer paiement
            $paiement = FinancePayment::create([
                'session_id'           => $session->id,
                'encaisse_par_user_id' => $userId,
                'module_source'        => $data['module_source'],
                'table_source'         => $data['table_source'],
                'source_id'            => (int) $data['source_id'],
                'montant'              => (float) $data['montant'],
                'mode'                 => $data['mode'],
                'reference'            => $data['reference'] ?? null,
                'statut'               => FinancePayment::STATUS_VALIDE,
            ]);

            // 6) Appliquer état source (montant_paye + statut)
            $nouveauPaye = round($dejaPaye + (float) $data['montant'], 2);

            $this->adapter->appliquerEtat(
                $data['module_source'],
                $data['table_source'],
                (int) $data['source_id'],
                $nouveauPaye,
                $totalDu
            );

            // 7) Agrégats session
            $session->increment('nb_paiements', 1);
            $session->increment('total_montant', (float) $data['montant']);

            // 8) Audit
            FinanceAudit::log(
                'PAIEMENT_CREE',
                $userId,
                $session->id,
                $paiement->id,
                $data['table_source'],
                (int) $data['source_id'],
                [
                    'montant'      => (float) $data['montant'],
                    'mode'         => $data['mode'],
                    'reference'    => $data['reference'] ?? null,
                    'total_du'     => $totalDu,
                    'deja_paye'    => $dejaPaye,
                    'nouveau_paye' => $nouveauPaye,
                    'reste'        => round(max(0, $totalDu - $nouveauPaye), 2),
                    'module_scope' => $session->module_scope,
                ]
            );

            return [
                'paiement'      => $paiement,
                'user_id'       => $userId,
                'module_source' => (string) $data['module_source'],
                'table_source'  => (string) $data['table_source'],
                'source_id'     => (int) $data['source_id'],
                'nouveau_paye'  => (float) $nouveauPaye,
                'total_du'      => (float) $totalDu,
            ];
        });

        // ✅ afterCommit = facture_officielle + auto-validation pharmacie si soldée
        DB::afterCommit(function () use ($payload) {

            $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : null;
            $module = (string) ($payload['module_source'] ?? '');
            $table  = (string) ($payload['table_source'] ?? '');
            $id     = (int) ($payload['source_id'] ?? 0);
            $paye   = (float) ($payload['nouveau_paye'] ?? 0);
            $total  = (float) ($payload['total_du'] ?? 0);

            $estSolde = ($paye + 0.0001) >= $total;

            // ✅ A) créer facture officielle UNIQUEMENT si soldée
            if ($estSolde && $id > 0 && $table !== '' && $module !== '') {
                try {
                    $this->factureOfficielleService->createIfNotExists(
                        $module,
                        $table,
                        $id,
                        $total,
                        null,
                        null,
                        null
                    );
                } catch (\Throwable $e) {
                    FinanceAudit::log(
                        'FACTURE_OFFICIELLE_CREATION_ECHEC',
                        $userId,
                        null,
                        null,
                        $table,
                        $id,
                        ['error' => $e->getMessage()]
                    );
                }
            }

            // ✅ B) auto-validation Pharmacie UNIQUEMENT si soldée
            if ($module !== 'pharmacie') return;
            if (!$estSolde) return;
            if ($table !== 'ventes') return;

            $baseUrl = rtrim((string) config('finance.internal_base_url'), '/');
            $key     = (string) config('finance.internal_key');
            $timeout = (int) config('finance.internal_timeout', 5);

            if ($key === '') return;

            $url = $baseUrl . '/api/v1/pharmacie/internal/ventes/' . $id . '/valider';

            try {
                $http = Http::timeout($timeout)
                    ->withHeaders([
                        'X-Finance-Key' => $key,
                        'Accept' => 'application/json',
                    ])
                    ->post($url);

                if (!$http->successful()) {
                    FinanceAudit::log(
                        'AUTO_VALIDATION_PHARMACIE_ECHEC',
                        $userId,
                        null,
                        null,
                        'ventes',
                        $id,
                        [
                            'url' => $url,
                            'http_status' => $http->status(),
                            'body' => $http->json(),
                        ]
                    );
                } else {
                    FinanceAudit::log(
                        'AUTO_VALIDATION_PHARMACIE_OK',
                        $userId,
                        null,
                        null,
                        'ventes',
                        $id,
                        [
                            'url' => $url,
                            'http_status' => $http->status(),
                        ]
                    );
                }
            } catch (\Throwable $e) {
                FinanceAudit::log(
                    'AUTO_VALIDATION_PHARMACIE_EXCEPTION',
                    $userId,
                    null,
                    null,
                    'ventes',
                    $id,
                    [
                        'url' => $url,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        });

        return response()->json(['paiement' => $payload['paiement']], 201);
    }

    public function annuler(Request $request, int $id)
    {
        $poste = (string) $request->header('X-Workstation');
        abort_if(!$poste, 422, 'Header X-Workstation obligatoire');

        $data = $request->validate([
            'raison_annulation' => 'required|string|min:3|max:500',
        ]);

        $user = $this->authUser($request);
        abort_if(!$user, 401, 'Utilisateur non authentifié.');
        $userId = (int) $user->id;

        return DB::transaction(function () use ($id, $data, $userId, $poste, $user) {

            $session = FinanceSession::sessionOuverte($userId, $poste);
            abort_if(!$session, 409, 'Session Finance non ouverte.');

            $paiement = FinancePayment::query()->lockForUpdate()->findOrFail($id);
            abort_if($paiement->statut === FinancePayment::STATUS_ANNULE, 409, 'Paiement déjà annulé.');

            // ✅ Policy/Gate: autoriser annulation uniquement si session courante + droits (encaisseur/superviseur)
            abort_unless(
                Gate::allows('finance.payment.cancel', [$paiement, $session]),
                403,
                "Annulation interdite: session non courante ou droits insuffisants."
            );

            if (!empty($session->module_scope)) {
                abort_if(
                    $session->module_scope !== $paiement->module_source,
                    403,
                    "Session limitée au module '{$session->module_scope}'."
                );
            }

            $source = $this->adapter->verrouiller($paiement->table_source, (int) $paiement->source_id);
            abort_if(!$source, 404, 'Source introuvable.');

            $totalDu = (float) $this->adapter->montantDu($paiement->module_source, $source);

            $paiement->update([
                'statut'             => FinancePayment::STATUS_ANNULE,
                'raison_annulation'  => $data['raison_annulation'],
                'annule_le'          => now(),
                'annule_par_user_id' => $userId,
            ]);

            $nouveauPaye = (float) FinancePayment::query()
                ->where('table_source', $paiement->table_source)
                ->where('source_id', (int) $paiement->source_id)
                ->where('statut', FinancePayment::STATUS_VALIDE)
                ->sum('montant');

            $this->adapter->appliquerEtat(
                $paiement->module_source,
                $paiement->table_source,
                (int) $paiement->source_id,
                round($nouveauPaye, 2),
                $totalDu
            );

            $session->decrement('nb_paiements', 1);
            $session->decrement('total_montant', (float) $paiement->montant);

            FinanceAudit::log(
                'PAIEMENT_ANNULE',
                $userId,
                $session->id,
                $paiement->id,
                $paiement->table_source,
                (int) $paiement->source_id,
                [
                    'raison_annulation' => $data['raison_annulation'],
                    'montant'           => (float) $paiement->montant,
                    'total_du'          => $totalDu,
                    'nouveau_paye'      => (float) $nouveauPaye,
                    'reste'             => round(max(0, $totalDu - (float) $nouveauPaye), 2),
                    'module_scope'      => $session->module_scope,
                ]
            );

            return response()->json(['paiement' => $paiement->fresh()]);
        });
    }
}