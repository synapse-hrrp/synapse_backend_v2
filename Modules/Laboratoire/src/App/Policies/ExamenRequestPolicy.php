<?php

namespace Modules\Laboratoire\App\Policies;

use Modules\Users\App\Models\User;
use Modules\Laboratoire\App\Models\ExamenRequest;

class ExamenRequestPolicy
{
    /**
     * Créer une demande d'examen — Réception uniquement
     */
    public function create(User $user): bool
    {
        return $user->hasRole('reception')
            || $user->hasRole('admin');
    }

    /**
     * Voir la worklist — Laborantin + Admin
     */
    public function viewWorklist(User $user): bool
    {
        return $user->hasRole('laborantin')
            || $user->hasRole('admin');
    }

    /**
     * Voir une demande spécifique
     */
    public function view(User $user, ExamenRequest $examenRequest): bool
    {
        return $user->hasRole('laborantin')
            || $user->hasRole('reception')
            || $user->hasRole('medecin')
            || $user->hasRole('admin');
    }

    /**
     * Annuler une demande — Réception + Admin
     */
    public function cancel(User $user, ExamenRequest $examenRequest): bool
    {
        // Seulement si encore en pending_payment
        return ($user->hasRole('reception') || $user->hasRole('admin'))
            && $examenRequest->status === 'pending_payment';
    }
}