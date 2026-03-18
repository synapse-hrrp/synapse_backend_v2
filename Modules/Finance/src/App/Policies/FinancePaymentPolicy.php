<?php

namespace Modules\Finance\App\Policies;

use Modules\Finance\App\Models\FinancePayment;
use Modules\Finance\App\Models\FinanceSession;
use App\Models\User;

class FinancePaymentPolicy
{
    /**
     * Peut annuler un paiement ?
     * Règles:
     * - user doit avoir permission caisse.reglement.validate (déjà géré par routes)
     * - session caisse ouverte doit exister pour ce user + poste
     * - paiement doit appartenir à la session courante
     * - OPTION : soit l'encaisseur annule, soit un superviseur
     */
    public function cancel(User $user, FinancePayment $payment, ?FinanceSession $currentSession = null): bool
    {
        // Déjà annulé => non
        if ($payment->statut === FinancePayment::STATUS_ANNULE) {
            return false;
        }

        // Si on n'a pas la session courante, on ne peut pas vérifier => refuse
        if (!$currentSession) {
            return false;
        }

        // Doit être la session courante
        if ((int) $payment->session_id !== (int) $currentSession->id) {
            return false;
        }

        // OPTION: autoriser superviseur, sinon seulement l'encaisseur
        // -> si tu as spatie permissions/roles:
        if (method_exists($user, 'hasRole') && $user->hasRole('superviseur_caisse')) {
            return true;
        }

        // Sinon encaisseur uniquement
        return (int) $payment->encaisse_par_user_id === (int) $user->id;
    }

    /**
     * Peut voir les paiements ? (optionnel)
     */
    public function view(User $user): bool
    {
        if (method_exists($user, 'can')) {
            return $user->can('caisse.reglement.view');
        }
        return true;
    }
}